<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Cache\Lock;
use Illuminate\Support\Facades\Cache;

final class MarketplaceConcurrencyService
{
    private const SLOT_COUNT = 10;

    private const LOCK_TTL_SECONDS = 21600;

    /** @return array{account: Lock, slot: Lock} */
    public function acquire(string $marketplace, string $tenant, int $accountId): array
    {
        $account = Cache::store('redis')->lock($this->accountKey($tenant, $accountId), self::LOCK_TTL_SECONDS);
        while (! $account->get()) {
            sleep(5);
        }

        while (true) {
            for ($slot = 0; $slot < self::SLOT_COUNT; $slot++) {
                $slotLock = Cache::store('redis')->lock($this->slotKey($marketplace, $slot), self::LOCK_TTL_SECONDS);
                if ($slotLock->get()) {
                    return ['account' => $account, 'slot' => $slotLock];
                }
            }

            sleep(5);
        }
    }

    /** @param array{account: Lock, slot: Lock} $locks */
    public function release(array $locks): void
    {
        $locks['slot']->release();
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
