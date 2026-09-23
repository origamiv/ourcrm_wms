<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Validation\ValidationException;

final class QuerySearchService
{
    /**
     * Объединяет полнотекстовый поиск по разрешённым полям с фильтром SVAR.
     *
     * @param  array<int, string>  $selectedFields
     * @param  array<int, string>  $allowedFields
     * @param  array<string, array<string, string>>  $aliases
     * @param  array<int, string>  $dateFields
     */
    public function merge(
        ?string $filter,
        ?string $search,
        array $selectedFields,
        array $allowedFields,
        string $mode = 'filter',
        array $aliases = [],
        array $dateFields = [],
    ): ?string {
        $needle = trim((string) $search);
        if ($mode !== 'filter' || mb_strlen($needle) < 3) {
            return $filter;
        }

        $fields = array_values(array_unique(array_intersect($selectedFields, $allowedFields)));
        if ($fields === []) {
            throw ValidationException::withMessages([
                'search_fields' => 'Выберите хотя бы одно доступное поле поиска.',
            ]);
        }

        $searchRules = [];
        foreach ($fields as $field) {
            $searchRules[] = [
                'field' => $field,
                'type' => 'text',
                'filter' => 'contains',
                'value' => $this->searchValue($field, $needle, $dateFields),
            ];
            foreach ($aliases[$field] ?? [] as $value => $label) {
                if (str_contains(mb_strtolower($label), mb_strtolower($needle))) {
                    $searchRules[] = [
                        'field' => $field,
                        'type' => 'text',
                        'filter' => 'equal',
                        'value' => $value,
                    ];
                }
            }
        }

        $searchNode = ['glue' => 'or', 'rules' => $searchRules];
        if ($filter === null || $filter === '') {
            return json_encode($searchNode, JSON_THROW_ON_ERROR);
        }

        $existing = json_decode($filter, true);
        if (! is_array($existing)) {
            throw ValidationException::withMessages([
                'filter' => 'Некорректный формат фильтра.',
            ]);
        }

        return json_encode([
            'glue' => 'and',
            'rules' => [$existing, $searchNode],
        ], JSON_THROW_ON_ERROR);
    }

    /** @param  array<int, string>  $dateFields */
    private function searchValue(string $field, string $needle, array $dateFields): string
    {
        if (! in_array($field, $dateFields, true)
            || preg_match('/^(\d{2})\.(\d{2})\.(\d{2}|\d{4})(?:\s+(\d{2}:\d{2}))?$/', $needle, $parts) !== 1) {
            return $needle;
        }

        $year = mb_strlen($parts[3]) === 2 ? '20'.$parts[3] : $parts[3];

        return sprintf('%s-%s-%s%s', $year, $parts[2], $parts[1], isset($parts[4]) ? ' '.$parts[4] : '');
    }
}
