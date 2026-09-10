<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;

final class DataExportController
{
    public function pdf(Request $request): \Illuminate\Http\Response
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'columns' => ['required', 'array', 'max:100'],
            'columns.*.key' => ['required', 'string', 'max:100'],
            'columns.*.label' => ['required', 'string', 'max:255'],
            'rows' => ['array', 'max:10000'],
        ]);
        $escape = static fn (mixed $value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $head = implode('', array_map(fn (array $column): string => '<th>'.$escape($column['label']).'</th>', $data['columns']));
        $body = implode('', array_map(fn (array $row): string => '<tr>'.implode('', array_map(fn (array $column): string => '<td>'.$escape($row[$column['key']] ?? '').'</td>', $data['columns'])).'</tr>', $data['rows'] ?? []));
        $html = '<!doctype html><html><head><meta charset="utf-8"><style>@page{margin:70px 36px 52px}body{font-family:DejaVu Sans,sans-serif;color:#17212b;font-size:10px}header{position:fixed;top:-45px;left:0;right:0;border-bottom:2px solid #1e892f;padding-bottom:8px;font-size:18px;font-weight:bold}footer{position:fixed;bottom:-32px;left:0;right:0;border-top:1px solid #d7dce3;padding-top:8px;color:#667085;font-size:9px}table{width:100%;border-collapse:collapse;margin-top:8px}th{background:#e1f3e7;color:#17212b;font-weight:bold}th,td{border:1px solid #d7dce3;padding:6px;text-align:left;vertical-align:top}</style></head><body><header>'.$escape($data['title']).'</header><footer>WMS · '.now()->format('d.m.Y H:i').'</footer><table><thead><tr>'.$head.'</tr></thead><tbody>'.$body.'</tbody></table></body></html>';
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->setDefaultFont('DejaVu Sans');
        $pdf = new Dompdf($options);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4', 'landscape');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$escape($data['title']).'.pdf"',
        ]);
    }
}
