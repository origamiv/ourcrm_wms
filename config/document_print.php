<?php

declare(strict_types=1);

$field = fn (string $key, string $label, string $type = 'text', bool $required = false) => compact('key', 'label', 'type', 'required');
$number = $field('number', 'Номер документа');
$basis = $field('basis', 'Основание');
$items = $field('items', 'Позиции', 'items', true);
$terms = $field('terms', 'Условия', 'textarea');

return [
    1 => ['template' => 'invoice', 'fields' => [$number, $basis, $field('due_date', 'Оплатить до', 'date'), $items, $terms]],
    2 => ['template' => 'act', 'fields' => [$number, $basis, $field('period_start', 'Начало периода', 'date'), $field('period_end', 'Конец периода', 'date'), $field('service_date', 'Дата оказания услуг', 'date'), $items, $field('result', 'Результат работ', 'textarea'), $terms]],
    3 => ['template' => 'upd', 'fields' => [$number, $basis, $field('operation_date', 'Дата передачи', 'date'), $field('shipper', 'Грузоотправитель'), $field('consignee', 'Грузополучатель'), $field('transfer_basis', 'Основание передачи'), $items, $terms]],
    4 => ['template' => 'contract', 'fields' => [$number, $field('subject', 'Предмет договора', 'textarea', true), $field('starts_at', 'Начало действия', 'date'), $field('ends_at', 'Окончание действия', 'date'), $field('items', 'Позиции', 'items'), $field('terms', 'Условия договора', 'textarea', true)]],
    5 => ['template' => 'agreement', 'fields' => [$number, $field('contract_number', 'Номер договора', 'text', true), $field('contract_date', 'Дата договора', 'date', true), $field('effective_date', 'Дата вступления в силу', 'date'), $field('changes', 'Изменения договора', 'textarea', true), $field('items', 'Позиции', 'items'), $terms]],
    6 => ['template' => 'vat_invoice', 'fields' => [$number, $field('correction_number', 'Номер исправления'), $field('correction_date', 'Дата исправления', 'date'), $field('payment_reference', 'Платёжный документ'), $field('shipper', 'Грузоотправитель'), $field('consignee', 'Грузополучатель'), $items]],
];
