<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Document;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class DocumentPdfService
{
    public function data(Document $document): array
    {
        if (! $document->executor_id || ! $document->customer_id) {
            throw ValidationException::withMessages(['parties' => 'Укажите и сохраните Исполнителя и Заказчика в карточке документа.']);
        }
        $parties = app(DocumentPartiesService::class)->resolve($document, ['executor_id' => $document->executor_id, 'customer_id' => $document->customer_id]);
        $print = $document->docType?->settings['print'] ?? null;
        $templates = ['invoice' => 1, 'act' => 2, 'upd' => 3, 'contract' => 4, 'agreement' => 5, 'vat_invoice' => 6];
        abort_unless(is_array($print) && isset($templates[$print['template'] ?? '']) && is_array($print['fields'] ?? null), 422, 'Настройте settings.print в типе документа.');
        $type = $templates[$print['template']];
        $rules = ['pdf' => ['required', 'array']];
        foreach ($print['fields'] as $field) {
            abort_unless(is_array($field) && preg_match('/^[a-z][a-z0-9_]{0,49}$/', $field['key'] ?? '') && in_array($field['type'] ?? '', ['text', 'textarea', 'date', 'items'], true), 422, 'Некорректное описание полей settings.print.');
            abort_unless(($field['key'] === 'items') === ($field['type'] === 'items'), 422, 'Для позиций используйте ключ items и тип items.');
            $key = 'pdf.'.$field['key'];
            $rules[$key] = [! empty($field['required']) ? 'required' : 'nullable'];
            if ($field['type'] === 'items') {
                abort_unless($field['key'] === 'items', 422, 'Поле позиций должно иметь ключ items.');
                $rules[$key] = [...$rules[$key], 'array', 'max:200'];
                $rules[$key.'.*.name'] = ['required', 'string', 'max:1000'];
                $rules[$key.'.*.unit'] = ['required', 'string', 'max:30'];
                $rules[$key.'.*.quantity'] = ['required', 'regex:/^\d{1,5}(\.\d{1,3})?$/', 'numeric', 'gt:0', 'max:10000'];
                $rules[$key.'.*.price'] = ['required', 'regex:/^\d{1,7}(\.\d{1,2})?$/', 'numeric', 'min:0', 'max:1000000'];
                $rules[$key.'.*.vat_rate'] = ['required', 'regex:/^(none|\d{1,2}(\.\d{1,2})?|100)$/'];
            } elseif ($field['type'] === 'date') {
                $rules[$key][] = 'date_format:Y-m-d';
            } else {
                $rules[$key] = [...$rules[$key], 'string', $field['type'] === 'textarea' ? 'max:20000' : 'max:2000'];
            }
        }
        $data = Validator::make(['pdf' => $document->src['pdf'] ?? []], $rules, ['required' => 'Заполните :attribute в печатных данных документа.'])->validate()['pdf'];
        $data = array_intersect_key($data, array_flip(array_column($print['fields'], 'key')));
        $items = [];
        $net = 0;
        $vat = 0;
        foreach ($data['items'] ?? [] as $item) {
            $amount = intdiv($this->scaled($item['quantity'], 3) * $this->scaled($item['price'], 2) + 500, 1000);
            $tax = $item['vat_rate'] === 'none' ? 0 : intdiv($amount * $this->scaled($item['vat_rate'], 2) + 5000, 10000);
            $items[] = [...$item, 'net' => $amount, 'vat' => $tax, 'total' => $amount + $tax];
            $net += $amount;
            $vat += $tax;
        }

        return [...$parties, 'document' => $document, 'type' => $type, 'print' => $print, 'pdf' => $data, 'items' => $items, 'net' => $net, 'vat' => $vat, 'total' => $net + $vat];
    }

    public function render(Document $document): string
    {
        $data = $this->data($document);
        $options = new Options;
        $options->setDefaultFont('DejaVu Sans');
        $options->setIsRemoteEnabled(false);
        $options->setIsPhpEnabled(false);
        $options->setIsJavascriptEnabled(false);
        $options->setChroot(resource_path('views/pdf'));
        $pdf = new Dompdf($options);
        $pdf->setPaper('A4', in_array($data['type'], [3, 6], true) ? 'landscape' : 'portrait');
        $pdf->loadHtml(view('pdf.document', $data)->render(), 'UTF-8');
        $pdf->render();

        return $pdf->output();
    }

    private function scaled(string|int|float $value, int $precision): int
    {
        [$whole, $fraction] = array_pad(explode('.', (string) $value, 2), 2, '');

        return (int) ($whole.mb_str_pad($fraction, $precision, '0'));
    }
}
