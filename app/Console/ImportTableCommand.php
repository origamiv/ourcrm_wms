<?php

declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class ImportTableCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'import:table {table} {module?}';

    /**
     * The console command description.
     */
    protected $description = 'Импорт таблицы БД в файл';

    public function getModels(string $path, $table): array
    {
        $out = [];
        $results = scandir($path);

        foreach ($results as $result) {
            if (in_array($result, ['.', '..', '.git', '.gitkeep'], true)) {
                continue;
            }

            $filename = "{$path}/{$result}";

            if (is_dir($filename)) {
                $out = array_merge($out, $this->getModels($filename));
            } else {
                $out[] = ucfirst(mb_substr($filename, 0, -4));
            }
        }

        foreach ($out as $k => $m) {
            $mName = str_replace('/', '\\', $m);
            $mName = ucfirst($mName);
            $out[$k] = $mName;

            /** @var Model $z */
            $z = new $mName;
            $table1 = $z->getTable();

            if ($table1 === $table) {
                return [$mName];
            }
        }

        return $out;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $table = $this->argument('table');
        $module = $this->argument('module') ?: 'Main';
        $moduleLower = mb_strtolower($module);

        $this->info("Импортирую $table в модуле $module");

        $models = $this->getModels('app/Models', "{$moduleLower}.{$table}");
        $model = ucfirst($models[0]);

        $fn = "data//{$table}.csv";

        $this->info(now()->toDateTimeString().'Импорт таблицы '.$table);

        if (file_exists($fn)) {
            DB::statement("truncate table {$moduleLower}.{$table} cascade");
            DB::statement("alter sequence {$moduleLower}.{$table} _id_seq restart with 1");

            $data = file($fn);
            $fields = explode(';', trim($data[0]));
            unset($data[0]);

            foreach ($data as $item) {
                $arr = [];
                $table_row = explode(';"', trim($item));

                foreach ($table_row as $key => $value) {
                    $k = trim(str_replace('"', '', $key));

                    $value = trim($value);
                    $lval = (! empty($value)) ? mb_strlen($value) - 1 : 0;

                    if (! empty($value) && $value[$lval] === '"') {
                        $value = mb_substr($value, 0, $lval);
                        $value = trim($value);
                    }

                    if (! empty($value) && ($value[0] === '"')) {
                        $value = trim(str_replace('"', '', $value));
                        $value = trim($value);
                    }

                    // $value = trim(str_replace('"', '', $value));

                    if (! empty($fields[$k])) {
                        $name = $fields[$k];
                        $arr[$name] = ($value !== '') ? $value : null;
                    }
                }

                // $model::query()->updateOrCreate(['id'=>$arr['id']],$arr);
                $model::query()->create($arr);
            }

            // $r = DB::select( DB::raw("SELECT max(id) as mxid FROM $moduleLower.$table"));
            // $mxid=$r[0]->mxid+1;
            // DB::statement( "alter sequence $moduleLower.$table"."_id_seq restart with ".$mxid );
            // SELECT max(id) from goods.goods;
            // alter sequence goods_id_seq restart with 1001;
        }

        $this->info(now()->toDateTimeString().' завершение');

        return self::SUCCESS;
    }
}
