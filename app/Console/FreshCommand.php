<?php

declare(strict_types=1);

namespace App\Console;

use App\Models\Migration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class FreshCommand extends Command
{
    protected $name = 'project:fresh';

    /**
     * Описание команды
     */
    protected $description = 'Переустановка БД';

    public function handle(): void
    {
        $is_root = 1;
        $module = module();

        $this->info('Начало установки');
        // $modules = json_decode(file_get_contents(public_path('../modules_available.json')), true);

        if ($is_root == 1) {
            Schema::dropSchema($module);
            Schema::createSchema($module);
        }

        Migration::query()->where('module', $module)->forceDelete();
        $this->call('migrate');

        // $this->call('db:seed');

        // $this->call('work:dates');
        // $this->call('work:import_executors');
        // $this->call('work:import_order_executors');

        $this->info('Окончание установки');
    }
}
