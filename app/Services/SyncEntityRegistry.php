<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

final class SyncEntityRegistry
{
    public function resolve(string $alias, User $actor): array
    {
        $definition = config('sync.entities.'.$alias);
        abort_unless(is_array($definition), 404, 'Неизвестная сущность.');
        [$policy, $method] = $definition['authorize'];
        abort_unless(app($policy)->{$method}($actor), 403, 'Недостаточно прав.');

        return $definition;
    }
}
