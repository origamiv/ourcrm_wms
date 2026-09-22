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
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class MarketplaceCatalogSyncService
{
    public function sync(IntegrationWebhook $webhook, IntegrationData $data, string $marketplace, ?callable $progress = null, ?string $ozonLastId = null, int $initialProcessed = 0, ?callable $checkpoint = null, mixed $cursor = null, bool $singlePage = false): IntegrationData
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
        $autoCreate = app(TenantFeatureService::class)->enabled($tenant, TenantFeatureService::AUTO_CREATE_MARKETPLACE_GOODS);
        $maps = $this->goodMaps($tenant);
        $processed = $initialProcessed;
        $total = $initialProcessed;
        $consume = function (array $items) use (&$maps, &$processed, &$total, $autoCreate, $marketplace, $progress, $tenant, $webhook): void {
            $total = max($total, $processed + count($items));
            if ($progress) {
                $progress($total, $processed);
            }
            foreach ($items as $item) {
                $this->saveItem($webhook, $tenant, $marketplace, $item, $autoCreate, $maps);
                $processed++;
            }
            if ($progress) {
                $progress($total, $processed);
            }
        };

        $nextCursor = match ($marketplace) {
            'wildberries' => $this->syncWildberries($account, $consume, $cursor, $singlePage),
            'ozon' => $this->syncOzon($account, $consume, $cursor ?? $ozonLastId, $checkpoint, $singlePage),
            'yandex_market' => $this->syncYandexMarket($account, $consume, $cursor, $singlePage),
            default => throw new InvalidArgumentException('Неподдерживаемый маркетплейс: '.$marketplace),
        };

        $data->forceFill(['data' => ['marketplace' => $marketplace, 'processed' => $processed, 'auto_create' => $autoCreate, 'next_cursor' => $nextCursor], 'status_processing' => 1, 'status' => 1])->save();

        return $data;
    }

    private function saveItem(IntegrationWebhook $webhook, string $tenant, string $marketplace, array $item, bool $autoCreate, array &$maps): void
    {
        DB::transaction(function () use ($webhook, $tenant, $marketplace, $item, $autoCreate, &$maps): void {
            $row = GoodMarketplace::withTrashed()->where('tenant_id', $tenant)->where('webhook_id', $webhook->id)->where('external_id', $item['external_id'])->lockForUpdate()->first();
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
    }

    private function syncWildberries(ClientAccount $account, callable $consume, ?array $cursor = null, bool $singlePage = false): ?array
    {
        do {
            $previousCursor = $cursor;
            $payload = ['settings' => ['sort' => ['ascending' => true], 'cursor' => ['limit' => 100], 'filter' => ['withPhoto' => -1]]];
            if ($cursor) {
                $payload['settings']['cursor'] = [...$cursor, 'limit' => 100];
            }
            $response = $this->request($account)->post('https://content-api.wildberries.ru/content/v2/get/cards/list', $payload)->throw()->json();
            $items = [];
            foreach ((array) ($response['cards'] ?? []) as $card) {
                foreach ((array) ($card['sizes'] ?? []) as $size) {
                    $id = (string) ($size['chrtID'] ?? $size['chrtId'] ?? '');
                    if ($id === '') {
                        continue;
                    }
                    $items[] = ['external_id' => $id, 'external_sku' => $id, 'offer_id' => (string) ($card['vendorCode'] ?? ''), 'name' => (string) ($card['title'] ?? '').(! empty($size['techSize']) ? ' / '.$size['techSize'] : ''), 'barcodes' => array_values(array_filter(array_map('strval', (array) ($size['skus'] ?? [])))), 'status' => 1, 'raw_data' => ['card' => $card, 'size' => $size]];
                }
            }
            $consume($items);
            $cursor = $response['cursor'] ?? null;
            if (empty($response['cards']) || (int) ($cursor['total'] ?? count($response['cards'])) < 100 || empty($cursor['updatedAt']) || empty($cursor['nmID'])) {
                return null;
            }
            $cursor = ['updatedAt' => $cursor['updatedAt'], 'nmID' => $cursor['nmID']];
            if ($cursor === $previousCursor) {
                throw new RuntimeException('Wildberries вернул повторный курсор каталога.');
            }
        } while (! $singlePage);

        return $cursor;
    }

    private function syncOzon(ClientAccount $account, callable $consume, ?string $lastId = null, ?callable $checkpoint = null, bool $singlePage = false): ?string
    {
        do {
            $previousCursor = $lastId;
            $payload = ['filter' => ['visibility' => 'ALL'], 'limit' => 100];
            if ($lastId) {
                $payload['last_id'] = $lastId;
            }
            try {
                $response = $this->request($account, true)->post('https://api-seller.ozon.ru/v4/product/info/attributes', $payload)->throw()->json();
            } catch (RequestException $exception) {
                if ($lastId !== null && $exception->response->status() === 404
                    && (int) $exception->response->json('code') === 5
                    && $exception->response->json('message') === 'item not found') {
                    if ($checkpoint) {
                        $checkpoint(null);
                    }

                    return null;
                }

                throw $exception;
            }
            $sourceItems = $response['result'] ?? $response['items'] ?? null;
            if (! is_array($sourceItems)) {
                throw new RuntimeException('Ozon вернул некорректную страницу каталога.');
            }
            $pageItems = $this->normalizeOzonPage((array) $response);
            $consume($pageItems);
            $lastId = trim((string) ($response['last_id'] ?? '')) ?: null;
            if (count($sourceItems) < $payload['limit']) {
                $lastId = null;
            }
            if ($lastId !== null && $lastId === $previousCursor) {
                throw new RuntimeException('Ozon вернул повторный курсор каталога.');
            }
            if ($checkpoint) {
                $checkpoint($lastId);
            }
        } while ($lastId && ! $singlePage);

        return $lastId;
    }

    private function syncYandexMarket(ClientAccount $account, callable $consume, ?string $cursor = null, bool $singlePage = false): ?string
    {
        $credentials = (array) ($account->src['credentials'] ?? []);
        $businessId = trim((string) ($credentials['business_id'] ?? ''));
        abort_if($businessId === '' || trim((string) $account->token) === '', 422, 'Для аккаунта Яндекс Маркета не заполнены Api-Key и business_id.');

        do {
            $previousCursor = $cursor;
            $url = 'https://api.partner.market.yandex.ru/businesses/'.rawurlencode($businessId).'/offer-mappings?limit=200';
            if ($cursor !== null) {
                $url .= '&page_token='.rawurlencode($cursor);
            }

            $response = $this->request($account, false, true)->post($url, ['archived' => false])->throw()->json();

            if (($response['status'] ?? null) !== 'OK') {
                throw new RuntimeException('Яндекс Маркет вернул некорректный статус ответа.');
            }

            $consume($this->normalizeYandexPage((array) $response));
            $cursor = trim((string) ($response['result']['paging']['nextPageToken'] ?? '')) ?: null;
            if ($cursor !== null && $cursor === $previousCursor) {
                throw new RuntimeException('Яндекс Маркет вернул повторный курсор каталога.');
            }
        } while ($cursor !== null && ! $singlePage);

        return $cursor;
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
        $marketplace = $ozon ? 'Ozon' : ($yandex ? 'Яндекс Маркет' : 'Wildberries');
        $request = Http::acceptJson()
            ->retry([1000, 3000, 10000, 30000], 0, function (Throwable $exception) use ($marketplace): bool {
                $context = [
                    'marketplace' => $marketplace,
                    'exception' => $exception::class,
                    'error' => $exception->getMessage(),
                ];
                if ($exception instanceof RequestException) {
                    $response = $exception->response;
                    $context['http_status'] = $response->status();
                    $context['retry_after'] = $response->header('Retry-After');
                    $context['request_id'] = $response->header('X-Request-ID')
                        ?? $response->header('X-Request-Id')
                        ?? $response->header('Request-Id');
                    $context['response_body'] = mb_substr($response->body(), 0, 1000);
                }
                Log::warning('Повтор сетевого запроса маркетплейса', $context);

                if ($exception instanceof ConnectionException) {
                    return true;
                }

                return $exception instanceof RequestException
                    && in_array($exception->response->status(), [408, 425, 429, 500, 502, 503, 504], true);
            })
            ->connectTimeout($isWildberries ? 5 : ($ozon ? 40 : 10))
            ->timeout($isWildberries ? 60 : ($ozon ? 180 : 90));
        $request = $request->withOptions([
            'on_stats' => function (TransferStats $stats) use ($marketplace): void {
                $handlerStats = $stats->getHandlerStats();
                $response = $stats->getResponse();
                $context = [
                    'marketplace' => $marketplace,
                    'url' => (string) $stats->getEffectiveUri(),
                    'local_ip' => $handlerStats['local_ip'] ?? null,
                    'remote_ip' => $handlerStats['primary_ip'] ?? null,
                    'http_status' => $response?->getStatusCode(),
                    'retry_after' => $response?->getHeaderLine('Retry-After') ?: null,
                    'request_id' => $response?->getHeaderLine('X-Request-ID')
                        ?: ($response?->getHeaderLine('X-Request-Id') ?: ($response?->getHeaderLine('Request-Id') ?: null)),
                    'curl_errno' => $handlerStats['errno'] ?? null,
                    'curl_error' => $handlerStats['error'] ?? null,
                    'dns_ms' => isset($handlerStats['namelookup_time']) ? round((float) $handlerStats['namelookup_time'] * 1000, 1) : null,
                    'connect_ms' => isset($handlerStats['connect_time']) ? round((float) $handlerStats['connect_time'] * 1000, 1) : null,
                    'tls_ms' => isset($handlerStats['appconnect_time']) ? round((float) $handlerStats['appconnect_time'] * 1000, 1) : null,
                    'first_byte_ms' => isset($handlerStats['starttransfer_time']) ? round((float) $handlerStats['starttransfer_time'] * 1000, 1) : null,
                    'total_ms' => round($stats->getTransferTime() * 1000, 1),
                ];

                Log::log($stats->hasResponse() ? 'info' : 'warning', 'Сетевой запрос маркетплейса', $context);
            },
        ]);
        if ($yandex) {
            return $request->withHeaders(['Api-Key' => (string) $account->token, 'Content-Type' => 'application/json']);
        }
        if ($ozon) {
            $credentials = (array) ($account->src['credentials'] ?? []);

            return $request->withHeaders(['Client-Id' => (string) ($credentials['key2'] ?? ''), 'Api-Key' => (string) $account->token]);
        }

        return $request->withToken((string) $account->token)->withHeaders(['X-Client-Secret' => (string) config('wms.wildberries_client_secret')]);
    }

    private function goodMaps(string $tenant): array
    {
        $maps = ['article' => [], 'barcode' => []];
        foreach (Good::query()->visibleTo($tenant)->where('is_category', '!=', 1)->select(['id', 'barcodes', 'articul'])->lazyById(1000) as $good) {
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
