<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\IntegrationWebhook;

final class IntegrationBuilder
{
    public const SCHEDULE_HOURS = [1, 2, 4, 6, 12, 24];

    public static function hasSchema(IntegrationWebhook $webhook): bool
    {
        $builder = ($webhook->params ?? [])['builder'] ?? null;

        return is_array($builder)
            && ($builder['version'] ?? null) === 1
            && is_array($builder['nodes'] ?? null);
    }

    public static function hasCatalog(IntegrationWebhook $webhook): bool
    {
        $builder = ($webhook->params ?? [])['builder'] ?? null;
        if (! self::hasSchema($webhook)) {
            return true; // Старые интеграции продолжают работать без изменений.
        }

        foreach ($builder['nodes'] ?? [] as $node) {
            if (($node['type'] ?? null) === 'catalog_sync') {
                return true;
            }
        }

        return false;
    }

    public static function scheduleHours(IntegrationWebhook $webhook): int
    {
        if (! self::hasSchema($webhook)) {
            return 4;
        }

        foreach (($webhook->params ?? [])['builder']['nodes'] ?? [] as $node) {
            if (($node['type'] ?? null) !== 'catalog_sync') {
                continue;
            }
            $hours = $node['settings']['schedule_hours'] ?? null;

            return in_array($hours, self::SCHEDULE_HOURS, true) ? $hours : 4;
        }

        return 4;
    }

    public static function isDue(IntegrationWebhook $webhook): bool
    {
        return self::hasCatalog($webhook)
            && ($webhook->dat_last_run === null
                || $webhook->dat_last_run->lessThanOrEqualTo(now()->subHours(self::scheduleHours($webhook))));
    }
}
