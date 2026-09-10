<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class DadataService
{
    public function suggest(string $type, string $query): array
    {
        abort_unless(in_array($type, ['party', 'bank'], true), 404);
        $token = config('dadata.token');
        abort_unless(is_string($token) && trim($token) !== '', 503, 'Подсказки DaData пока не настроены. Реквизиты можно заполнить вручную.');
        try {
            $response = Http::acceptJson()->withToken($token, 'Token')->connectTimeout(3)->timeout(8)
                ->post('https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/'.$type, ['query' => $query, 'count' => 5]);
        } catch (ConnectionException) {
            abort(502, 'DaData не отвечает. Повторите поиск позже или заполните реквизиты вручную.');
        }
        abort_unless($response->successful(), 502, 'Подсказки DaData временно недоступны. Реквизиты можно заполнить вручную.');
        $suggestions = $response->json('suggestions');
        abort_unless(is_array($suggestions), 502, 'Не удалось получить подсказки DaData.');

        return array_values(array_map(fn (array $item) => $this->map($type, $item), array_slice(array_filter($suggestions, fn ($item) => is_array($item) && is_array($item['data'] ?? null) && is_string($item['value'] ?? null)), 0, 5)));
    }

    private function map(string $type, array $item): array
    {
        $data = $item['data'];
        $text = static fn (mixed $value): string => is_string($value) ? $value : '';
        if ($type === 'bank') {
            $fields = ['bank' => $text(data_get($data, 'name.payment') ?: $item['value']), 'bik' => $text($data['bic'] ?? null), 'korr_schet' => $text($data['correspondent_account'] ?? null)];
            $detail = 'БИК '.$fields['bik'];
        } else {
            $fields = [
                'name' => $text(data_get($data, 'name.short_with_opf') ?: $item['value']),
                'shortname' => $text(data_get($data, 'name.short') ?: data_get($data, 'name.short_with_opf') ?: $item['value']),
                'fullname' => $text(data_get($data, 'name.full_with_opf') ?: $item['unrestricted_value'] ?? $item['value']),
                'inn' => $text($data['inn'] ?? null), 'kpp' => $text($data['kpp'] ?? null), 'ogrn' => $text($data['ogrn'] ?? null),
                'director_fio' => $text(data_get($data, 'management.name')), 'director_position' => $text(data_get($data, 'management.post')),
                'src' => ['opf' => $text(data_get($data, 'opf.short') ?: data_get($data, 'opf.full')), 'legal_address' => $text(data_get($data, 'address.unrestricted_value') ?: data_get($data, 'address.value'))],
            ];
            foreach (['phone' => 'phones.0.value', 'email' => 'emails.0.value'] as $field => $path) {
                if ($value = $text(data_get($data, $path))) {
                    $fields[$field] = $value;
                }
            }
            $detail = implode(' · ', array_filter(['ИНН '.$fields['inn'], $fields['kpp'] ? 'КПП '.$fields['kpp'] : '', $fields['src']['legal_address']]));
        }

        return ['value' => $item['value'], 'detail' => $detail, 'fields' => $fields];
    }
}
