<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Instruction;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
use League\CommonMark\CommonMarkConverter;

final class InstructionPdfService
{
    public function render(Instruction $instruction): string
    {
        if ($instruction->content_type === 'pdf') {
            return Storage::disk($instruction->storage_disk)->get($instruction->storage_path);
        }

        $content = $this->content($instruction);
        $html = $instruction->content_type === 'markdown'
            ? (new CommonMarkConverter)->convert($content)->getContent()
            : ($instruction->content_type === 'html' ? $content : '<p>Эта инструкция доступна в видеоформате.</p>');

        $html = $this->localizeImages($html);
        $options = new Options;
        $options->setDefaultFont('DejaVu Sans');
        $options->setIsRemoteEnabled(false);
        $options->setIsPhpEnabled(false);
        $options->setIsJavascriptEnabled(false);
        $options->setChroot(public_path());

        $pdf = new Dompdf($options);
        $pdf->setPaper('A4', 'portrait');
        $pdf->loadHtml(view('pdf.instruction', ['instruction' => $instruction, 'html' => $html])->render(), 'UTF-8');
        $pdf->render();

        return $pdf->output();
    }

    private function content(Instruction $instruction): string
    {
        return Storage::disk($instruction->storage_disk)->get($instruction->storage_path);
    }

    private function localizeImages(string $html): string
    {
        return preg_replace_callback('/(<img\b[^>]*\bsrc=["\'])([^"\']+)(["\'])/i', function (array $matches): string {
            $src = $matches[2];
            if (str_starts_with($src, '/')) {
                $path = public_path(ltrim(parse_url($src, PHP_URL_PATH) ?: '', '/'));
                if (is_file($path)) {
                    return $matches[1].$path.$matches[3];
                }
            }

            return $matches[0];
        }, $html) ?? $html;
    }
}
