<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class MarketplaceConcurrencyService
{
    private const SLOT_COUNT = 10;

    private const LOCK_TTL_SECONDS = 1260;

    private const LOCK_WAIT_SECONDS = 5;

    /** @return array{account: Lock, slot: ?Lock} */
    public function acquire(string $marketplace, string $tenant, int $accountId, bool $singlePage = false): array
    {
        $ttl = $singlePage ? self::LOCK_TTL_SECONDS : 21600;
        $account = Cache::store('redis')->lock($this->accountKey($tenant, $accountId), $ttl);
        $accountAcquired = false;
        try {
            $account->block(self::LOCK_WAIT_SECONDS);
            $accountAcquired = true;
            if ($singlePage) {
                return ['account' => $account, 'slot' => null];
            }
            $deadline = time() + self::LOCK_WAIT_SECONDS;
            do {
                for ($slot = 0; $slot < self::SLOT_COUNT; $slot++) {
                    $slotLock = Cache::store('redis')->lock($this->slotKey($marketplace, $slot), $ttl);
                    if ($slotLock->get()) {
                        return ['account' => $account, 'slot' => $slotLock];
                    }
                }
                sleep(5);
            } while (time() < $deadline);
        } catch (Throwable $exception) {
            if ($accountAcquired) {
                $account->release();
            }

            throw $exception;
        }

        $account->release();
        throw new LockTimeoutException('Не удалось получить слот синхронизации маркетплейса за '.self::LOCK_WAIT_SECONDS.' секунд.');
    }

    /** @param array{account: Lock, slot: ?Lock} $locks */
    public function release(array $locks): void
    {
        $locks['slot']?->release();
        $locks['account']->release();
    }

    private function accountKey(string $tenant, int $accountId): string
    {
        return 'marketplace-catalog:account:'.$tenant.':'.$accountId;
    }

    private function slotKey(string $marketplace, int $slot): string
    {
        return 'marketplace-catalog:slot:'.$marketplace.':'.$slot;
    }
}
