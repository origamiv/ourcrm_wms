<?php

namespace App\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\DirectoryListing;
use Modules\Tools\Config\ConfigModule;

class ToolsCreateModuleCommand extends Command
{
    protected $signature = 'tools:create-module';

    protected $description = 'Создает модуль с API по конфигу';
    public $replace = [];

    public function handle()
    {
        $this->info('Старт генерации API модуля');
        $disk = Storage::build([
            'driver' => 'local',
            'root' => app_path('Config/tables'),
        ]);
        $tables = $disk->files();
        //dd($tables);
        foreach ($tables as $tableFile) {
            $table=str_replace('.yaml', '', $tableFile);
            Artisan::call('tools:create-api', ['table' => $table]);
            echo Artisan::output();
        }
        $this->info('Завершение генерации модуля');
    }
}
