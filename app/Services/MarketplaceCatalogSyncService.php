<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ClientAccount;
use App\Models\Good;
use App\Models\GoodCard;
use App\Models\GoodMarketplace;
use App\Models\IntegrationData;
use App\Models\IntegrationWebhook;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class MarketplaceCatalogSyncService
{
    public function sync(IntegrationWebhook $webhook, IntegrationData $data, string $marketplace): IntegrationData
    {
        $tenant = (string) $webhook->tenant_id;
        $accountId = (int) ($webhook->params['account_id'] ?? 0);
        $account = ClientAccount::query()->where('tenant_id', $tenant)->findOrFail($accountId);
        $items = $marketplace === 'wildberries' ? $this->loadWildberries($account) : $this->loadOzon($account);
        $autoCreate = app(TenantFeatureService::class)->enabled($tenant, TenantFeatureService::AUTO_CREATE_MARKETPLACE_GOODS);
        $maps = $this->goodMaps($tenant);
        $processed = 0;

        foreach ($items as $item) {
            DB::transaction(function () use ($webhook, $tenant, $marketplace, $item, $autoCreate, &$maps): void {
                $row = GoodMarketplace::query()->where('tenant_id', $tenant)->where('webhook_id', $webhook->id)->where('external_id', $item['external_id'])->lockForUpdate()->first();
                $wasExisting = $row !== null;
                if (! $row) {
                    $row = new GoodMarketplace;
                    $row->forceFill(['tenant_id' => $tenant, 'webhook_id' => $webhook->id, 'integration_id' => $webhook->id, 'marketplace' => $marketplace, 'external_id' => $item['external_id']]);
                }
                $row->forceFill(['external_sku' => $item['external_sku'], 'offer_id' => $item['offer_id'], 'name' => $item['name'], 'barcodes' => $item['barcodes'], 'status' => $item['status'], 'raw_data' => $item['raw_data'], 'synced_at' => now()]);
                if (! $row->good_id) {
                    $goodId = $this->resolveGood($maps, $item);
                    $created = false;
                    if (! $goodId && $autoCreate) {
                        $goodId = $this->createGood($tenant, $item);
                        $this->addToMaps($maps, $item, $goodId);
                        $created = true;
                    }
                    if ($goodId) {
                        $row->good_id = $goodId;
                        $row->match_type = $created ? 'created' : 'auto';
                        $row->matched_at = now();
                    }
                }
                if ($wasExisting && $row->good_id && $row->match_type === 'created') {
                    $row->match_type = 'auto';
                }
                $row->save();
            }, 3);
            $processed++;
        }

        $data->forceFill(['data' => ['marketplace' => $marketplace, 'processed' => $processed, 'auto_create' => $autoCreate], 'status_processing' => 1, 'status' => 1])->save();

        return $data;
    }

    private function loadWildberries(ClientAccount $account): array
    {
        $items = [];
        $cursor = null;
        do {
            $payload = ['settings' => ['sort' => ['ascending' => true], 'cursor' => ['limit' => 100], 'filter' => ['withPhoto' => -1]]];
            if ($cursor) {
                $payload['settings']['cursor'] = [...$cursor, 'limit' => 100];
            }
            $response = $this->request($account)->post('https://content-api.wildberries.ru/content/v2/get/cards/list', $payload)->throw()->json();
            foreach ((array) ($response['cards'] ?? []) as $card) {
                foreach ((array) ($card['sizes'] ?? []) as $size) {
                    $id = (string) ($size['chrtID'] ?? $size['chrtId'] ?? '');
                    if ($id === '') {
                        continue;
                    }
                    $items[] = ['external_id' => $id, 'external_sku' => $id, 'offer_id' => (string) ($card['vendorCode'] ?? ''), 'name' => (string) ($card['title'] ?? '').(! empty($size['techSize']) ? ' / '.$size['techSize'] : ''), 'barcodes' => array_values(array_filter(array_map('strval', (array) ($size['skus'] ?? [])))), 'status' => 1, 'raw_data' => ['card' => $card, 'size' => $size]];
                }
            }
            $cursor = $response['cursor'] ?? null;
        } while ($cursor && ! empty($cursor['updatedAt']) && ! empty($cursor['nmID']));

        return $items;
    }

    private function loadOzon(ClientAccount $account): array
    {
        $items = [];
        $lastId = null;
        do {
            $payload = ['filter' => ['visibility' => 'ALL'], 'limit' => 500];
            if ($lastId) {
                $payload['last_id'] = $lastId;
            }
            $response = $this->request($account, true)->post('https://api-seller.ozon.ru/v4/product/info/attributes', $payload)->throw()->json();
            $pageItems = $this->normalizeOzonPage((array) $response);
            array_push($items, ...$pageItems);
            $lastId = $response['last_id'] ?? null;
        } while ($lastId && $pageItems !== []);

        return $items;
    }

    private function normalizeOzonPage(array $response): array
    {
        $items = [];
        $sourceItems = (array) ($response['result'] ?? $response['items'] ?? []);
        foreach ($sourceItems as $item) {
            $offer = (string) ($item['offer_id'] ?? '');
            $id = (string) ($item['id'] ?? $item['sku'] ?? $offer);
            if ($id === '') {
                continue;
            }
            $barcodes = array_values(array_unique(array_filter(array_map('strval', array_merge(
                (array) ($item['barcodes'] ?? []),
                [(string) ($item['barcode'] ?? '')],
            )))));
            $items[] = [
                'external_id' => $id,
                'external_sku' => (string) ($item['sku'] ?? $id),
                'offer_id' => $offer,
                'name' => (string) ($item['name'] ?? $offer),
                'barcodes' => $barcodes,
                'status' => ! empty($item['is_archived']) ? 0 : 1,
                'raw_data' => $item,
            ];
        }

        return $items;
    }

    private function request(ClientAccount $account, bool $ozon = false): PendingRequest
    {
        $request = Http::retry(3, 1000)->timeout(45)->acceptJson();
        if ($ozon) {
            $credentials = (array) ($account->src['credentials'] ?? []);

            return $request->withHeaders(['Client-Id' => (string) ($credentials['key2'] ?? ''), 'Api-Key' => (string) $account->token]);
        }

        return $request->withToken((string) $account->token)->withHeaders(['X-Client-Secret' => (string) config('wms.wildberries_client_secret')]);
    }

    private function goodMaps(string $tenant): array
    {
        $maps = ['article' => [], 'barcode' => []];
        foreach (Good::query()->where('tenant_id', $tenant)->get(['id', 'barcodes', 'articul']) as $good) {
            foreach ((array) $good->articul as $value) {
                $key = mb_strtolower(trim((string) $value));
                if ($key !== '') {
                    $maps['article'][$key] ??= (int) $good->id;
                }
            }
            foreach ((array) $good->barcodes as $value) {
                $key = mb_strtolower(trim((string) $value));
                if ($key !== '') {
                    $maps['barcode'][$key] ??= (int) $good->id;
                }
            }
        }

        return $maps;
    }

    private function resolveGood(array $maps, array $item): ?int
    {
        $offer = mb_strtolower(trim((string) $item['offer_id']));
        if ($offer !== '' && isset($maps['article'][$offer])) {
            return $maps['article'][$offer];
        }
        foreach ($item['barcodes'] as $barcode) {
            $key = mb_strtolower(trim((string) $barcode));
            if ($key !== '' && isset($maps['barcode'][$key])) {
                return $maps['barcode'][$key];
            }
        }

        return null;
    }

    private function addToMaps(array &$maps, array $item, int $goodId): void
    {
        $offer = mb_strtolower(trim((string) $item['offer_id']));
        if ($offer !== '') {
            $maps['article'][$offer] = $goodId;
        }
        foreach ($item['barcodes'] as $barcode) {
            $key = mb_strtolower(trim((string) $barcode));
            if ($key !== '') {
                $maps['barcode'][$key] = $goodId;
            }
        }
    }

    private function createGood(string $tenant, array $item): int
    {
        $card = new GoodCard;
        $card->forceFill(['name' => $item['name'], 'status' => 1, 'tenant_id' => $tenant])->save();
        $good = new Good;
        $good->forceFill(['name' => $item['name'], 'shortname' => 'mp_'.Str::lower(Str::random(20)), 'code' => (string) $item['external_id'], 'barcodes' => $item['barcodes'], 'articul' => array_values(array_filter([(string) $item['offer_id']])), 'is_from_external' => 1, 'is_category' => 2, 'status' => 1, 'level' => 0, 'goodcard_id' => $card->id, 'tenant_id' => $tenant])->save();
        $card->forceFill(['good_id' => $good->id])->save();

        return (int) $good->id;
    }
}
