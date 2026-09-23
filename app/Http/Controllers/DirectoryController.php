<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Services\SyncEntityRegistry;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

final class DirectoryController extends BaseApiController
{
    /** Серверная страница табличного справочника. */
    #[Response(200, 'Страница справочника', type: 'array{data: list<array<string, mixed>>, meta: array<string, mixed>}')]
    public function index(Request $request, string $entity_type, SyncEntityRegistry $registry): JsonResponse
    {
        $definition = $registry->resolve($entity_type, $request->user());
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'q' => ['nullable', 'string', 'max:200'],
            'short_query' => ['nullable', 'string', 'max:200'],
            'sort' => ['nullable', 'string', 'max:64'],
            'direction' => ['nullable', 'in:asc,desc'],
            'status' => ['nullable', 'string', 'max:32'],
            'deleted' => ['nullable', 'in:active,deleted,all'],
            'record_id' => ['nullable', 'string', 'max:64'],
            'filter' => ['nullable', 'array'],
            'filter.*' => ['nullable', 'string', 'max:200'],
        ]);

        $modelClass = $definition['entity'];
        abort_unless(is_a($modelClass, Model::class, true), 500, 'Для сущности не указана модель.');

        $model = new $modelClass;
        $table = $model->getTable();
        $columns = Schema::getColumnListing($table);
        $fields = array_values(array_intersect(array_unique(array_merge(
            ['id', 'name', 'shortname', 'status', 'deleted_at'],
            $definition['fields'] ?? [],
        )), $columns));
        $query = $modelClass::query()->withTrashed()->visibleTo((string) $request->user()->tenant_id)->select($fields);

        $deleted = $validated['deleted'] ?? 'active';
        if ($deleted === 'deleted') {
            $query->whereNotNull($model->qualifyColumn('deleted_at'));
        } elseif ($deleted !== 'all') {
            $query->whereNull($model->qualifyColumn('deleted_at'));
        }

        if (($status = trim((string) ($validated['status'] ?? ''))) !== '' && in_array('status', $columns, true)) {
            $query->where($model->qualifyColumn('status'), $status);
        }
        if (($recordId = trim((string) ($validated['record_id'] ?? ''))) !== '' && in_array('id', $columns, true)) {
            $query->where($model->qualifyColumn('id'), $recordId);
        }

        foreach (($validated['filter'] ?? []) as $field => $value) {
            if (! in_array($field, $columns, true) || $value === null || $value === '') {
                continue;
            }
            $query->where($model->qualifyColumn($field), $value);
        }

        if (($term = trim((string) ($validated['q'] ?? ''))) !== '') {
            $searchable = array_values(array_intersect(['name', 'shortname', 'code', 'category', 'path'], $columns));
            $query->where(function ($search) use ($model, $columns, $searchable, $term): void {
                foreach ($searchable as $column) {
                    $search->orWhere($model->qualifyColumn($column), 'ILIKE', '%'.$term.'%');
                }
                if (in_array('articul', $columns, true)) {
                    $search->orWhereRaw($model->qualifyColumn('articul').'::text ILIKE ?', ['%'.$term.'%']);
                }
            });
        }
        if (($shortQuery = trim((string) ($validated['short_query'] ?? ''))) !== '' && in_array('shortname', $columns, true)) {
            $query->where($model->qualifyColumn('shortname'), 'ILIKE', '%'.$shortQuery.'%');
        }

        $sort = (string) ($validated['sort'] ?? 'name');
        $sort = $sort === '__name' ? 'name' : $sort;
        $sortable = array_values(array_intersect(array_unique(array_merge(['id', 'name', 'shortname', 'created_at', 'updated_at'], $definition['fields'] ?? [])), $columns));
        if (! in_array($sort, $sortable, true)) {
            $sort = in_array('name', $sortable, true) ? 'name' : 'id';
        }
        $direction = $validated['direction'] ?? 'asc';
        $query->orderBy($model->qualifyColumn($sort), $direction)->orderBy($model->qualifyColumn('id'));

        $perPage = (int) ($validated['per_page'] ?? 25);
        $page = $query->paginate($perPage, ['*'], 'page', (int) ($validated['page'] ?? 1));

        return response()->json([
            'data' => $page->items(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'from' => $page->firstItem(),
                'to' => $page->lastItem(),
            ],
        ]);
    }
}
