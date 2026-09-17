<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Good;
use App\Models\GoodMarketplace;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

final class MarketplaceCatalogService
{
    /** @return array<string, mixed> */
    public function match(User $actor, string $id, array $data): array
    {
        return DB::transaction(function () use ($actor, $id, $data): array {
            $tenant = $actor->tenant_id;
            $sync = app(EntitySyncService::class);
            $sync->prepareWrite($tenant, GoodMarketplace::class, $id);
            $sync->checkpoint($tenant);
            DB::table('public.sync_state')->where('tenant_id', $tenant)->lockForUpdate()->firstOrFail();

            $actor = User::findOrFail($actor->id);
            abort_unless($actor->tenant_id === $tenant && app(AccessService::class)->isAdmin($actor), 403);

            $row = GoodMarketplace::withTrashed()->visibleTo($tenant)->findOrFail($id);
            abort_if($row->trashed(), 422, 'Запись уже удалена.');
            $current = $sync->current(GoodMarketplace::class, $tenant, $id);
            if (! hash_equals($current['version'], $data['version'])) {
                throw new HttpResponseException(response()->json([
                    'message' => 'Запись уже изменена. Загрузите актуальные данные.',
                    'current' => $current,
                ], 409));
            }

            $good = Good::visibleTo($tenant)->whereKey($data['good_id'])
                ->where(fn ($query) => $query->whereNull('is_category')->orWhere('is_category', '!=', 1))
                ->first();
            abort_unless($good, 422, 'Выберите доступный товар, а не категорию.');

            $row->forceFill([
                'good_id' => $good->id,
                'match_type' => 'manual',
                'matched_at' => now(),
            ])->save();

            return $sync->current(GoodMarketplace::class, $tenant, $row->id);
        }, 3);
    }
}
