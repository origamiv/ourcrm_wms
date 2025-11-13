<?php

declare(strict_types=1);

namespace App\Console;

use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\Installer\Models\Feature;
use Modules\Installer\Models\Module;

final class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tasks:install';

    /**
     * The console command description.
     */
    protected $description = 'Устанавливает модуль Задачи';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info(now()->toDateTimeString().' Старт');

        $module = DB::query()->select('*')->from('installer.module')->where('shortname', 'tasks')->first();

        // Module::query()->where('shortname', '=', 'tasks')->forceDelete();
        // $module = Module::query()->where('shortname', '=', 'tasks')->first();

        if (empty($module)) {
            DB::table('installer.module')->insert([
                'name' => 'Tasks',
                'shortname' => 'tasks',
                'descr' => 'Модуль для учета и планирования задач',
                'status' => 1,
                'created_at' => now()->toDateTimeString(),
            ]);

            $module = DB::query()->select('*')->from('installer.module')->where('shortname', 'tasks')->first();

            DB::query()->from('installer.feature')->where('shortname', 'like', 'tasks%')->delete();

            DB::query()->from('installer.feature')->updateOrInsert([
                'shortname' => 'tasks.pay_at_tasks',
            ], [
                'name' => 'Отправка готовых задач на оплату',
                'module' => $module->shortname,
                'module_id' => $module->id,
            ]);

            DB::query()->from('main.settings')->updateOrInsert([
                'name' => 'tasks.pay_at_tasks',
            ], [
                'name' => 'tasks.pay_at_tasks',
                'descr' => 'Отправка готовых задач на оплату',
                'val' => 1,
                'module' => mb_strtolower($module->shortname),
            ]);

            DB::query()->from('installer.feature')->updateOrInsert([
                'shortname' => 'tasks.schet_name_for_pay',
            ], [
                'name' => 'Счет для оплат за задачи',
                'module' => $module->shortname,
                'module_id' => $module->id,
            ]); // ?->addSetting($module->shortname, 'qugo_delman');

            DB::query()->from('main.settings')->updateOrInsert([
                'name' => 'tasks.schet_name_for_pay',
            ], [
                'name' => 'tasks.schet_name_for_pay',
                'descr' => 'Счет для оплат за задачи',
                'val' => 'qugo_delman',
                'module' => mb_strtolower($module->shortname),
            ]);

            // DB::query()->from('installer.feature')->updateOrInsert([
            //     'shortname' => 'Yandex.Tracker.ClientID'
            // ], [
            //     'name' => 'ClientID приложения для получения OAuth',
            //     'module' => $module->shortname,
            //     'module_id' => $module->id,
            // ]);
            //
            // DB::query()->from('main.settings')->updateOrInsert([
            //     'name' => 'tasks.sources.yatracker.client_id'
            // ], [
            //     'name' => 'tasks.sources.yatracker.client_id',
            //     'descr' => 'Счет для оплат за задачи',
            //     'val' => 'qugo_delman',
            //     'module' => strtolower($module->shortname),
            //     'module_id' => $module->id,
            // ]);
            // ?->addSetting($module->shortname, config('tasks.services.yandex.app_client_id'));
            //
            // $this->call('db:seed_ext', [
            //     'class' => 'Modules\\Tasks\\Database\\Seeders\\TasksDatabaseSeeder',
            //     'demo' => false
            // ]);

            $this->call('optimize:clear');

            // $out=`npm install`;
            // $out=`npx playwright install`;
            // Artisan::call('module:migrate-rollback',['Profit']);
            // DB::select(DB::raw("DROP SCHEMA profit CASCADE;"));
            // DB::select(DB::raw("CREATE SCHEMA profit;"));
            // Artisan::call('module:migrate-refresh',['Profit']);
            // Artisan::call('db:seed',['--class=\\Modules\\Profit\\Database\\Seeders\\ProfitDatabaseSeeder']);
            // Artisan::call('import:table',[
            //     'table'=>'preland',
            //     'model'=>'Modules\\Profit\\Models\\Preland',
            //     'module'=>'Profit'
            // ]);
        } else {
            $this->warn(now()->toDateTimeString().' Модуль был уже установлен ранее');
        }

        $this->info(now()->toDateTimeString().' завершение');
    }
}
