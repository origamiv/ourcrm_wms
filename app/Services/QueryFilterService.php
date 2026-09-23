<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

final class QueryFilterService
{
    /**
     * @param  array<string, string>  $fields
     */
    public function apply(Builder $query, ?string $encoded, array $fields): Builder
    {
        if ($encoded === null || $encoded === '') {
            return $query;
        }
        $rules = json_decode($encoded, true);
        if (! is_array($rules)) {
            throw ValidationException::withMessages(['filter' => 'Некорректный формат фильтра.']);
        }
        app(FilterPresetService::class)->validateRules($rules);
        $this->node($query, $rules, $fields, 'and');

        return $query;
    }

    /**
     * @param  array<string, string>  $fields
     */
    private function node(Builder $query, array $node, array $fields, string $boolean): void
    {
        if (isset($node['rules'])) {
            $method = $boolean === 'or' ? 'orWhere' : 'where';
            $query->{$method}(function (Builder $nested) use ($node, $fields): void {
                foreach ($node['rules'] as $index => $child) {
                    $this->node($nested, $child, $fields, $index === 0 ? 'and' : (string) ($node['glue'] ?? 'and'));
                }
            });

            return;
        }

        $field = (string) $node['field'];
        if (! isset($fields[$field])) {
            throw ValidationException::withMessages(['filter' => "Поле {$field} недоступно для фильтрации на этом экране."]);
        }
        $expression = $fields[$field];
        $method = $boolean === 'or' ? 'orWhereRaw' : 'whereRaw';
        $type = (string) ($node['type'] ?? 'text');
        if (isset($node['includes']) && is_array($node['includes'])) {
            $values = array_values($node['includes']);
            if ($values === []) {
                $query->{$method}('false');

                return;
            }
            $placeholders = implode(', ', array_fill(0, count($values), '?'));
            $query->{$method}("({$expression}) IN ({$placeholders})", $values);

            return;
        }

        $filter = (string) $node['filter'];
        $value = $node['value'] ?? null;
        if (in_array($filter, ['between', 'notBetween'], true)) {
            $from = is_array($value) ? ($value['start'] ?? $value[0] ?? null) : null;
            $to = is_array($value) ? ($value['end'] ?? $value[1] ?? null) : null;
            $operator = $filter === 'between' ? 'BETWEEN' : 'NOT BETWEEN';
            $query->{$method}("({$expression}) {$operator} ? AND ?", [$from, $to]);

            return;
        }

        if ($type === 'text') {
            $left = "LOWER(COALESCE(({$expression})::text, ''))";
            $needle = mb_strtolower((string) $value);
            [$operator, $binding] = match ($filter) {
                'contains' => ['LIKE', "%{$needle}%"],
                'notContains' => ['NOT LIKE', "%{$needle}%"],
                'beginsWith' => ['LIKE', "{$needle}%"],
                'notBeginsWith' => ['NOT LIKE', "{$needle}%"],
                'endsWith' => ['LIKE', "%{$needle}"],
                'notEndsWith' => ['NOT LIKE', "%{$needle}"],
                'notEqual' => ['<>', $needle],
                default => ['=', $needle],
            };
            $query->{$method}("{$left} {$operator} ?", [$binding]);

            return;
        }

        $operator = match ($filter) {
            'greater' => '>',
            'greaterOrEqual' => '>=',
            'less' => '<',
            'lessOrEqual' => '<=',
            'notEqual' => '<>',
            default => '=',
        };
        $query->{$method}("({$expression}) {$operator} ?", [$value]);
    }
}
