<?php

declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class ExportTableCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'export:table {table} {module?}';

    /**
     * The console command description.
     */
    protected $description = 'Экспорт таблицы БД в файл';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $module = $this->argument('module') ?: 'Main';
        $table = $this->argument('table');
        $fn = "{$table}.csv";

        $this->info(now()->toDateTimeString().' старт');

        $r = DB::table(mb_strtolower($module).'.'.$table)->select('*')->get();

        foreach ($r as $index => $item) {
            $str = '';

            // $arItem=(array)$item;

            if ($index === 0) {
                $fields = array_keys((array) $item);
                foreach ($fields as $k => $v) {
                    if ($k !== 0) {
                        $str .= ';';
                    }

                    if ($v === null) {
                        $v = '';
                    }

                    $str .= $v;
                }

                $itog[] = $str;
                $str = '';
            }

            $arItem = array_values((array) $item);

            foreach ($arItem as $k => $v) {
                $v = str_replace("\n", '', $v);
                $v = str_replace("\r", '', $v);

                // $v=addslashes($v);

                if ($k !== 0) {
                    $str .= ';';
                }
                if ($v === null) {
                    $v = '';
                }
                $str = "{$str}\"{$v}\"";
            }
            $itog[] = $str;
        }
        $file = implode("\r\n", $itog);

        if (! file_exists('data//')) {
            mkdir('data//', 0777, true);
        }

        file_put_contents("data//{$fn}", $file);

        $this->info(now()->toDateTimeString().' завершение');

        return self::SUCCESS;
    }
}
