<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Good;
use App\Models\GoodCard;
use App\Models\GoodType;
use App\Models\GoodUnit;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class GoodService
{
    public function save(User $actor, array $data, ?string $id = null, bool $delete = false): array
    {
        return DB::transaction(function () use ($actor, $data, $id, $delete) {
            $tenant = $actor->tenant_id;
            $sync = app(EntitySyncService::class);
            $sync->prepareWrite($tenant, Good::class, $id, true);
            $sync->checkpoint($tenant);
            DB::table('public.sync_state')->where('tenant_id', $tenant)->lockForUpdate()->firstOrFail();
            $actor = User::findOrFail($actor->id);
            abort_unless($actor->tenant_id === $tenant && app(AccessService::class)->isAdmin($actor), 403);
            $row = $id ? Good::withTrashed()->visibleTo($tenant)->findOrFail($id) : new Good;
            $oldParent = $row->parent_id;
            if ($id) {
                $current = $sync->current(Good::class, $tenant, $id);
                if (! hash_equals($current['version'], $data['version'])) {
                    throw new HttpResponseException(response()->json(['message' => 'Запись уже изменена. Загрузите актуальные данные.', 'current' => $current], 409));
                }
                abort_if($row->trashed(), 422, 'Товар уже удалён.');
            }
            if ($delete) {
                abort_if(Good::where('parent_id', $id)->exists(), 422, 'У записи есть дочерние товары. Сначала перенесите или удалите их.');
                $row->delete();
            } else {
                foreach (['parent_id' => Good::class, 'goodcard_id' => GoodCard::class, 'type_good' => GoodType::class, 'type_unit' => GoodUnit::class] as $field => $model) {
                    $value = array_key_exists($field, $data) ? $data[$field] : $row->{$field};
                    $related = $model::visibleTo($tenant)->whereKey($value);
                    if ($field === 'goodcard_id') {
                        $related->where('good_id', $id ?? '0');
                    }
                    if ($value !== null && ! $related->exists()) {
                        throw ValidationException::withMessages([$field => 'Выберите доступную запись своей организации.']);
                    }
                }
                $parent = array_key_exists('parent_id', $data) ? $data['parent_id'] : $row->parent_id;
                $seen = $id ? [$id => true] : [];
                while ($parent !== null) {
                    if (isset($seen[(string) $parent])) {
                        throw ValidationException::withMessages(['parent_id' => 'Родительская связь не может образовывать цикл.']);
                    }
                    $seen[(string) $parent] = true;
                    $parent = Good::whereKey($parent)->value('parent_id');
                }
                $fields = array_diff(config('sync.entities.goods.fields'), ['id', 'tenant_id', 'created_at', 'updated_at', 'deleted_at', 'level', 'has_children']);
                if ($id && array_key_exists('is_category', $data) && (int) $data['is_category'] !== 1 && Good::where('parent_id', $id)->exists()) {
                    throw ValidationException::withMessages(['is_category' => 'Запись с дочерними товарами является категорией.']);
                }
                $row->forceFill(array_intersect_key($data, array_flip($fields)));
                if (! $id) {
                    $row->tenant_id = $tenant;
                    $row->level = 0;
                }
                $row->save();
            }

            $recorder = app(EntityChangeRecorder::class);
            if (! $recorder->hasDatabaseTrigger($row->getTable())) {
                $affected = $this->hierarchyIds((string) $row->id, $oldParent, $row->parent_id);
                $recorder->publishMany(Good::class, array_values(array_diff($affected, [(string) $row->id])));
            }

            return $sync->current(Good::class, $tenant, $row->id);
        }, 3);
    }

    /** @return list<string> */
    private function hierarchyIds(string $id, mixed $oldParent, mixed $newParent): array
    {
        $rows = DB::select(<<<'SQL'
WITH RECURSIVE tree AS (
    SELECT id FROM goods.goods WHERE id::text = ?
    UNION ALL
    SELECT child.id FROM goods.goods child JOIN tree parent ON child.parent_id = parent.id
)
SELECT id::text AS id FROM tree
SQL, [$id]);
        $ids = array_map(static fn (object $row): string => (string) $row->id, $rows);
        foreach ([$oldParent, $newParent] as $parent) {
            if ($parent !== null) {
                $ids[] = (string) $parent;
            }
        }

        return array_values(array_unique($ids));
    }
}
