<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserFilterPreset;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class FilterPresetService
{
    private const FILTERS = [
        'greater', 'less', 'greaterOrEqual', 'lessOrEqual', 'equal', 'notEqual',
        'contains', 'notContains', 'beginsWith', 'notBeginsWith', 'endsWith',
        'notEndsWith', 'between', 'notBetween',
    ];

    public function save(User $user, array $data, ?UserFilterPreset $preset = null): UserFilterPreset
    {
        $screenKey = $preset?->screen_key ?? (string) $data['screen_key'];
        $this->validateRules($data['rules']);

        $duplicate = UserFilterPreset::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('user_id', $user->id)
            ->where('screen_key', $screenKey)
            ->whereRaw('lower(name) = lower(?)', [trim((string) $data['name'])])
            ->when($preset, fn ($query) => $query->whereKeyNot($preset->id))
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['name' => 'Фильтр с таким названием уже существует на этом экране.']);
        }

        if ($preset === null) {
            $count = UserFilterPreset::query()
                ->where('tenant_id', $user->tenant_id)
                ->where('user_id', $user->id)
                ->where('screen_key', $screenKey)
                ->count();
            if ($count >= 30) {
                throw ValidationException::withMessages(['name' => 'На одном экране можно сохранить не более 30 фильтров.']);
            }
            $preset = new UserFilterPreset;
        }

        $preset->forceFill([
            'tenant_id' => (string) $user->tenant_id,
            'user_id' => $user->id,
            'screen_key' => $screenKey,
            'name' => trim((string) $data['name']),
            'rules' => $data['rules'],
            'is_active' => (bool) $data['is_active'],
        ])->save();

        return $preset->refresh();
    }

    public function owned(User $user, string $id): UserFilterPreset
    {
        return UserFilterPreset::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('user_id', $user->id)
            ->findOrFail($id);
    }

    public function delete(User $user, string $id): void
    {
        DB::transaction(fn () => $this->owned($user, $id)->delete());
    }

    public function validateRules(array $rules): void
    {
        $encoded = json_encode($rules, JSON_THROW_ON_ERROR);
        if (mb_strlen($encoded) > 65_536) {
            throw ValidationException::withMessages(['rules' => 'Размер фильтра не должен превышать 64 КБ.']);
        }

        $count = 0;
        $this->validateNode($rules, 1, $count);
        if ($count === 0) {
            throw ValidationException::withMessages(['rules' => 'Добавьте хотя бы одно условие фильтра.']);
        }
    }

    private function validateNode(array $node, int $depth, int &$count): void
    {
        if ($depth > 5) {
            throw ValidationException::withMessages(['rules' => 'Допустимо не более пяти уровней группировки.']);
        }
        if (isset($node['rules'])) {
            if (! is_array($node['rules']) || ! in_array($node['glue'] ?? null, ['and', 'or'], true)) {
                throw ValidationException::withMessages(['rules' => 'Некорректная группа условий.']);
            }
            foreach ($node['rules'] as $child) {
                if (! is_array($child)) {
                    throw ValidationException::withMessages(['rules' => 'Некорректное условие фильтра.']);
                }
                $this->validateNode($child, $depth + 1, $count);
            }

            return;
        }

        $field = $node['field'] ?? null;
        $type = $node['type'] ?? null;
        $filter = $node['filter'] ?? null;
        if (! is_string($field) || preg_match('/^[a-z_][a-z0-9_.]*$/', $field) !== 1
            || ! in_array($type, ['text', 'number', 'date', 'tuple'], true)
            || (! isset($node['includes']) && ! in_array($filter, self::FILTERS, true))) {
            throw ValidationException::withMessages(['rules' => 'Некорректное условие фильтра.']);
        }
        $count++;
        if ($count > 50) {
            throw ValidationException::withMessages(['rules' => 'Допустимо не более 50 условий.']);
        }
    }
}
