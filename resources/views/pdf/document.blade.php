<!doctype html>
<html lang="ru"><head><meta charset="UTF-8"><style>
@page { margin: 30px; }
body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
h1 { font-size: 18px; margin: 20px 0; }
h2 { font-size: 12px; margin-top: 20px; }
table { border-collapse: collapse; width: 100%; margin: 12px 0; }
th, td { border: 1px solid #555; padding: 6px; text-align: left; vertical-align: top; }
th { background: #eee; }
.number { text-align: right; white-space: nowrap; }
.parties td { width: 50%; }
p { line-height: 1.5; }
.terms { white-space: pre-wrap; overflow-wrap: break-word; }
.signatures { margin-top: 30px; }
.signatures td { border: 0; width: 50%; padding: 15px 0; }
</style></head><body>
@php
    $titles = [1 => 'Счёт на оплату', 2 => 'Акт', 3 => 'Универсальный передаточный документ', 4 => 'Договор', 5 => 'Дополнительное соглашение', 6 => 'Счёт-фактура'];
    $money = fn (int $cents) => number_format($cents / 100, 2, ',', ' ');
@endphp
<h1>{{ $titles[$type] }} № {{ ($pdf['number'] ?? '') ?: $document->id }}@if($document->doc_date) от {{ $document->doc_date->format('d.m.Y') }}@endif</h1>
<p>{{ $document->name }}</p>
@if(!empty($pdf['basis']))<p><strong>Основание:</strong> {{ $pdf['basis'] }}</p>@endif
<table class="parties"><tr>
@foreach(['Исполнитель' => $executor, 'Заказчик' => $customer] as $label => $party)
<td><strong>{{ $label }}</strong><p>{{ $party->fullname ?: $party->name }}</p>
@foreach(['inn' => 'ИНН', 'kpp' => 'КПП', 'ogrn' => 'ОГРН', 'bank' => 'Банк', 'bik' => 'БИК', 'rasch_schet' => 'Расчётный счёт', 'korr_schet' => 'Корреспондентский счёт', 'phone' => 'Телефон', 'email' => 'Email'] as $field => $title)
@if($party->{$field})<div>{{ $title }}: {{ $party->{$field} }}</div>@endif
@endforeach
@if(!empty($party->src['legal_address']))<div>Адрес: {{ $party->src['legal_address'] }}</div>@endif
</td>
@endforeach
</tr></table>
@if(count($items))
<h2>{{ in_array($type, [4, 5]) ? 'Стоимость и состав услуг' : 'Товары, работы, услуги' }}</h2>
<table><thead><tr><th>#</th><th>Наименование</th><th>Ед.</th><th>Кол-во</th><th>Цена без НДС, руб.</th><th>Сумма без НДС</th><th>Ставка НДС</th><th>НДС</th><th>Всего, руб.</th></tr></thead><tbody>
@foreach($items as $item)
<tr><td>{{ $loop->iteration }}</td><td>{{ $item['name'] }}</td><td>{{ $item['unit'] }}</td><td>{{ $item['quantity'] }}</td><td class="number">{{ $money((int) round((float) $item['price'] * 100)) }}</td><td class="number">{{ $money($item['net']) }}</td><td>{{ $item['vat_rate'] === 'none' ? 'Без НДС' : $item['vat_rate'].'%' }}</td><td class="number">{{ $money($item['vat']) }}</td><td class="number">{{ $money($item['total']) }}</td></tr>
@endforeach
</tbody></table>
<p class="number">Итого без НДС: {{ $money($net) }} руб.<br>НДС: {{ $money($vat) }} руб.<br><strong>Всего: {{ $money($total) }} руб.</strong></p>
@endif
@foreach($print['fields'] as $field)
@if(!in_array($field['key'], ['number', 'basis', 'items']) && !empty($pdf[$field['key']]))
<h2>{{ $field['label'] }}</h2>
@foreach(explode("\n", (string) $pdf[$field['key']]) as $paragraph)<p class="terms">{{ $paragraph }}</p>@endforeach
@endif
@endforeach
@if($document->comment)<h2>Комментарий</h2>@foreach(explode("\n", $document->comment) as $paragraph)<p>{{ $paragraph }}</p>@endforeach
@endif
@if($document->accepted_at)<p>Дата подписания: {{ $document->accepted_at->format('d.m.Y H:i') }}</p>@endif
<table class="signatures"><tr><td>Исполнитель<br><br>________________ / {{ $executor->director_fio ?: '________________' }} /</td><td>Заказчик<br><br>________________ / {{ $customer->director_fio ?: '________________' }} /</td></tr></table>
</body></html>
