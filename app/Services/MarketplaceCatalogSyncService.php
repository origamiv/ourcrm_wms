<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ClientAccount;
use App\Models\Good;
use App\Models\GoodCard;
use App\Models\GoodMarketplace;
use App\Models\IntegrationData;
use App\Models\IntegrationWebhook;
use GuzzleHttp\TransferStats;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

final class MarketplaceCatalogSyncService
{
    public function sync(IntegrationWebhook $webhook, IntegrationData $data, string $marketplace, ?callable $progress = null): IntegrationData
    {
        $tenant = (string) $webhook->tenant_id;
        $accountId = (int) ($webhook->params['account_id'] ?? 0);
        $account = ClientAccount::query()->where('tenant_id', $tenant)->findOrFail($accountId);
        if ((int) $account->status !== 1) {
            throw new RuntimeException('Аккаунт маркетплейса отключён.');
        }
        if ($webhook->client_id !== null && (int) $account->client_id !== (int) $webhook->client_id) {
            throw new RuntimeException('Аккаунт маркетплейса не принадлежит клиенту интеграции.');
        }
        $items = match ($marketplace) {
            'wildberries' => $this->loadWildberries($account),
            'ozon' => $this->loadOzon($account),
            'yandex_market' => $this->loadYandexMarket($webhook, $account),
            default => throw new InvalidArgumentException('Неподдерживаемый маркетплейс: '.$marketplace),
        };
        $autoCreate = app(TenantFeatureService::class)->enabled($tenant, TenantFeatureService::AUTO_CREATE_MARKETPLACE_GOODS);
        $maps = $this->goodMaps($tenant);
        $processed = 0;
        if ($progress) {
            $progress(count($items), 0);
        }

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
            if ($progress) {
                $progress(count($items), $processed);
            }
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
            try {
                $response = $this->request($account, true)->post('https://api-seller.ozon.ru/v4/product/info/attributes', $payload)->throw()->json();
            } catch (RequestException $exception) {
                if ($lastId !== null && $exception->response->status() === 404) {
                    break;
                }

                throw $exception;
            }
            $pageItems = $this->normalizeOzonPage((array) $response);
            array_push($items, ...$pageItems);
            $lastId = $response['last_id'] ?? null;
        } while ($lastId && $pageItems !== []);

        return $items;
    }

    private function loadYandexMarket(IntegrationWebhook $webhook, ClientAccount $account): array
    {
        $credentials = (array) ($account->src['credentials'] ?? []);
        $businessId = trim((string) ($credentials['business_id'] ?? ''));
        abort_if($businessId === '' || trim((string) $account->token) === '', 422, 'Для аккаунта Яндекс Маркета не заполнены Api-Key и business_id.');

        $params = (array) ($webhook->params ?? []);
        $cursor = trim((string) ($params['yandex_market_catalog']['next_page_token'] ?? '')) ?: null;
        $items = [];
        $resumed = $cursor !== null;

        do {
            $url = 'https://api.partner.market.yandex.ru/businesses/'.rawurlencode($businessId).'/offer-mappings?limit=200';
            if ($cursor !== null) {
                $url .= '&page_token='.rawurlencode($cursor);
            }

            try {
                $response = $this->request($account, false, true)->post($url, ['archived' => false])->throw()->json();
            } catch (RequestException $exception) {
                if ($resumed && $cursor !== null && in_array($exception->response->status(), [400, 404], true)) {
                    $cursor = null;
                    $resumed = false;
                    $this->saveYandexCursor($webhook, null);

                    continue;
                }

                throw $exception;
            }

            if (($response['status'] ?? null) !== 'OK') {
                throw new RuntimeException('Яндекс Маркет вернул некорректный статус ответа.');
            }

            array_push($items, ...$this->normalizeYandexPage((array) $response));
            $cursor = trim((string) ($response['result']['paging']['nextPageToken'] ?? '')) ?: null;
            $this->saveYandexCursor($webhook, $cursor);
            $resumed = false;
        } while ($cursor !== null);

        return $items;
    }

    private function normalizeYandexPage(array $response): array
    {
        $items = [];
        foreach ((array) ($response['result']['offerMappings'] ?? []) as $mapping) {
            $offer = (array) ($mapping['offer'] ?? []);
            $offerId = trim((string) ($offer['offerId'] ?? ''));
            if ($offerId === '') {
                continue;
            }

            $barcodes = array_values(array_unique(array_filter(array_map(
                static fn (mixed $barcode): string => trim((string) $barcode),
                (array) ($offer['barcodes'] ?? []),
            ))));
            $marketSku = trim((string) (($mapping['mapping'] ?? [])['marketSku'] ?? ''));

            $items[] = [
                'external_id' => $offerId,
                'external_sku' => $marketSku,
                'offer_id' => $offerId,
                'name' => (string) ($offer['name'] ?? $offerId),
                'barcodes' => $barcodes,
                'status' => 1,
                'raw_data' => ['offer' => $offer, 'mapping' => (array) ($mapping['mapping'] ?? [])],
            ];
        }

        return $items;
    }

    private function saveYandexCursor(IntegrationWebhook $webhook, ?string $cursor): void
    {
        $params = (array) ($webhook->params ?? []);
        if ($cursor === null) {
            unset($params['yandex_market_catalog']['next_page_token']);
            if (($params['yandex_market_catalog'] ?? []) === []) {
                unset($params['yandex_market_catalog']);
            }
        } else {
            $params['yandex_market_catalog']['next_page_token'] = $cursor;
        }
        $webhook->forceFill(['params' => $params])->saveQuietly();
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

    private function request(ClientAccount $account, bool $ozon = false, bool $yandex = false): PendingRequest
    {
        $isWildberries = ! $ozon && ! $yandex;
        $request = Http::retry(3, 1000)->timeout(45)->acceptJson();
        if ($isWildberries) {
            $egressIp = $this->egressIp();
            $request = $request->withOptions([
                'on_stats' => function (TransferStats $stats) use ($egressIp): void {
                    $handlerStats = $stats->getHandlerStats();
                    $response = $stats->getResponse();
                    $context = [
                        'url' => (string) $stats->getEffectiveUri(),
                        'egress_ip' => $egressIp,
                        'local_ip' => $handlerStats['local_ip'] ?? null,
                        'remote_ip' => $handlerStats['primary_ip'] ?? null,
                        'http_status' => $response?->getStatusCode(),
                        'dns_ms' => isset($handlerStats['namelookup_time']) ? round((float) $handlerStats['namelookup_time'] * 1000, 1) : null,
                        'connect_ms' => isset($handlerStats['connect_time']) ? round((float) $handlerStats['connect_time'] * 1000, 1) : null,
                        'total_ms' => round($stats->getTransferTime() * 1000, 1),
                    ];

                    Log::log($stats->hasResponse() ? 'info' : 'warning', 'Сетевой запрос к Wildberries', $context);
                },
            ]);
        }
        if ($yandex) {
            return $request->withHeaders(['Api-Key' => (string) $account->token, 'Content-Type' => 'application/json']);
        }
        if ($ozon) {
            $credentials = (array) ($account->src['credentials'] ?? []);

            return $request->withHeaders(['Client-Id' => (string) ($credentials['key2'] ?? ''), 'Api-Key' => (string) $account->token]);
        }

        return $request->withToken((string) $account->token)->withHeaders(['X-Client-Secret' => (string) config('wms.wildberries_client_secret')]);
    }

    private function egressIp(): ?string
    {
        return Cache::remember('wms:marketplace:wildberries:egress-ip', now()->addMinute(), function (): ?string {
            try {
                $ip = trim(Http::connectTimeout(3)->timeout(5)->get('https://api.ipify.org')->throw()->body());

                return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
            } catch (\Throwable $exception) {
                Log::warning('Не удалось определить внешний IP для Wildberries', [
                    'error' => $exception->getMessage(),
                ]);

                return null;
            }
        });
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
