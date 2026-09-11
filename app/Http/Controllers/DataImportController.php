<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\FulfillmentCatalogService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

final class DataImportController
{
    public function store(Request $request, string $entity, FulfillmentCatalogService $service)
    {
        try {
            $actor = $request->user();
            if (! $actor instanceof User) {
                return response()->json(['message' => 'Требуется вход.'], 401);
            }

            return $this->storeImport($request, $entity, $service, $actor);
        } catch (\Throwable $e) {
            // Never let an import parsing/validation failure become an opaque 500.
            try { report($e); } catch (\Throwable) { /* logging must not mask the response */ }
            return response()->json(['message' => 'Импорт не выполнен: '.$e->getMessage()], 422);
        }
    }

    private function storeImport(Request $request, string $entity, FulfillmentCatalogService $service, User $actor)
    {
        abort_unless(in_array($entity, ['warehouses', 'cells', 'cell_goods', 'zones', 'marketplaces', 'delivery_services', 'type_warehouses', 'type_storage'], true), 422, 'Импорт этого раздела пока не поддерживается.');
        $file = $request->file('file');
        abort_unless($file instanceof UploadedFile && $file->isValid(), 422, 'Файл не загружен.');
        $format = strtoupper((string) $request->input('format', 'XLS'));
        $columns = collect(json_decode((string) $request->input('columns', '[]'), true) ?: [])->keyBy(fn ($item) => $this->normalize($item['label'] ?? $item['key'] ?? ''));
        $rows = $this->read($file, $format);
        $result = [];
        foreach ($rows as $row) {
            $payload = [];
            foreach ($row as $header => $value) {
                $mapped = $columns->get($this->normalize($header));
                $key = is_array($mapped) ? ($mapped['key'] ?? null) : (in_array((string) $header, config('sync.entities.'.$entity.'.fields', []), true) ? (string) $header : null);
                if ($key && !in_array($key, ['id', 'tenant_id', 'created_at', 'updated_at', 'deleted_at'], true)) $payload[$key] = $value;
            }
            if (!$payload) continue;
            if ($entity !== 'cell_goods') $payload['status'] = $payload['status'] ?? 1;
            if ($entity === 'cells' && $request->filled('warehouse_id')) {
                $payload['warehouse_id'] = (int) $request->input('warehouse_id');
            }
            // Catalog tables require a name. Spreadsheet headers are often localized
            // differently, so derive stable values when name/shortname were omitted.
            if (!isset($payload['name']) || trim((string) $payload['name']) === '') {
                if (isset($payload['shortname']) && trim((string) $payload['shortname']) !== '') {
                    $payload['name'] = $payload['shortname'];
                } elseif ($entity === 'cells' && (isset($payload['row']) || isset($payload['level']) || isset($payload['number']))) {
                    $payload['name'] = 'Ячейка '.implode('-', array_filter([
                        $payload['row'] ?? null, $payload['level'] ?? null, $payload['number'] ?? null,
                    ], static fn ($v) => $v !== null && $v !== ''));
                } else {
                    $first = collect($payload)->first(static fn ($v) => is_scalar($v) && trim((string) $v) !== '');
                    if ($first !== null) $payload['name'] = (string) $first;
                }
            }
            if ((!isset($payload['shortname']) || trim((string) $payload['shortname']) === '') && isset($payload['name'])) {
                $payload['shortname'] = $entity === 'cells'
                    && (isset($payload['row']) || isset($payload['level']) || isset($payload['number']))
                    ? 'cell_'.implode('_', array_filter([
                        $payload['row'] ?? null, $payload['level'] ?? null, $payload['number'] ?? null,
                    ], static fn ($v) => $v !== null && $v !== ''))
                    : Str::slug((string) $payload['name'], '_');
            }
            if ($entity !== 'cell_goods' && (!isset($payload['name']) || trim((string) $payload['name']) === '')) continue;
            $result[] = $service->save($actor, $entity, $payload);
        }
        return response()->json(['imported' => count($result), 'data' => $result]);
    }

    private function normalize(string $value): string { return Str::lower(preg_replace('/[\s_#№.\-]+/u', '', trim($value)) ?? ''); }

    private function read(UploadedFile $file, string $format): array
    {
        if (in_array($format, ['CSV', 'TXT'], true)) return $this->readDelimited($file->get(), $format === 'CSV' ? ';' : "\t");
        $sheet = IOFactory::load($file->getRealPath())->getActiveSheet();
        return $this->arraysToRows($sheet->toArray(null, true, true, false));
    }

    private function readDelimited(string $content, string $delimiter): array { $handle = fopen('php://temp', 'r+'); fwrite($handle, $content); rewind($handle); $rows = []; while (($row = fgetcsv($handle, 0, $delimiter)) !== false) $rows[] = $row; fclose($handle); return $this->arraysToRows($rows); }
    private function arraysToRows(array $rows): array { $headers = array_map(fn ($v) => trim((string) $v), array_shift($rows) ?: []); $headers = array_values(array_filter($headers, fn ($v) => $v !== '')); return array_values(array_filter(array_map(fn ($row) => array_combine($headers, array_pad(array_slice($row, 0, count($headers)), count($headers), '')), $rows))); }
}
