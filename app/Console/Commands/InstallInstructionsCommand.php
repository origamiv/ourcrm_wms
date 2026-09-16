<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Instruction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

final class InstallInstructionsCommand extends Command
{
    protected $signature = 'instructions:install {--path=docs/help/instructions : Каталог Markdown-инструкций}';
    protected $description = 'Установить общие инструкции WMS';

    public function handle(): int
    {
        $root = base_path((string) $this->option('path'));
        if (! is_dir($root)) {
            $this->error('Каталог инструкций не найден: '.$root);
            return self::FAILURE;
        }

        $sections = Instruction::SECTIONS;
        $installed = 0;
        foreach ($sections as $section) {
            $files = glob($root.'/'.$section.'/*.md') ?: [];
            sort($files);
            foreach ($files as $order => $file) {
                $shortname = pathinfo($file, PATHINFO_FILENAME);
                $name = $this->title($shortname, (string) file_get_contents($file));
                $path = 'instructions/shared/'.$section.'/'.$shortname.'.md';
                Storage::disk('local')->put($path, (string) file_get_contents($file));
                $storedPath = Storage::disk('local')->path($path);
                @chmod($storedPath, 0644);
                $directory = dirname($storedPath);
                while ($directory !== dirname(Storage::disk('local')->path('')) && str_starts_with($directory, Storage::disk('local')->path(''))) {
                    @chmod($directory, 0755);
                    $directory = dirname($directory);
                }

                $instruction = Instruction::withTrashed()->where('tenant_id', null)->where('shortname', $shortname)->first();
                if (! $instruction) {
                    $instruction = new Instruction();
                }
                $instruction->forceFill([
                    'tenant_id' => null,
                    'name' => $name,
                    'shortname' => $shortname,
                    'section_key' => $section,
                    'content_type' => 'markdown',
                    'storage_disk' => 'local',
                    'storage_path' => $path,
                    'original_filename' => $shortname.'.md',
                    'mime_type' => 'text/markdown',
                    'size' => filesize($file) ?: 0,
                    'sort_order' => $order,
                    'status' => 1,
                    'deleted_at' => null,
                ])->save();
                $installed++;
            }
        }

        $this->info("Установлено инструкций: {$installed}");
        return self::SUCCESS;
    }

    private function title(string $shortname, string $content): string
    {
        if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
            return trim($matches[1]);
        }
        return mb_convert_case(str_replace('_', ' ', $shortname), MB_CASE_TITLE, 'UTF-8');
    }
}
