<?php

declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ImportTswmsCommand extends Command
{
    protected $signature = 'wms:import:tswms {--tenant= : Тенант WMS} {--tswms-client-id= : ID клиента TSWMS} {--dry-run} {--only=*}';
    protected $description = 'Импортирует данные выбранного клиента TSWMS в тенант WMS';

    private string $tenant;
    private int $sourceClientId;
    private string $sourceDatabase = '';
    private $source;
    private array $maps = [];
    private int $created = 0;
    private int $updated = 0;

    public function handle(): int
    {
        $this->tenant = (string) ($this->option('tenant') ?: '');
        $this->sourceClientId = (int) ($this->option('tswms-client-id') ?: 0);
        if ($this->tenant === '' || $this->sourceClientId < 1) {
            $this->components->error('Нужно указать --tenant и --tswms-client-id.');
            return self::FAILURE;
        }
        if (! DB::table('public.tenants')->where('id', $this->tenant)->exists()) {
            $this->components->error('Тенант WMS не найден.');
            return self::FAILURE;
        }
        $this->configureSource();
        try {
            $billing = DB::connection('tswms');
            $client = $billing->table('clients')->where('id', $this->sourceClientId)->first();
        } catch (\Throwable $exception) {
            $this->components->error('Не удалось подключиться к billing-БД TSWMS: '.$exception->getMessage());
            return self::FAILURE;
        }
        if (! $client) {
            $this->components->error("Клиент TSWMS #{$this->sourceClientId} не найден.");
            return self::FAILURE;
        }
        $this->sourceDatabase = (string) ($client->{'sql-db'} ?? '');
        if ($this->sourceDatabase === '') {
            $this->components->error('У клиента TSWMS не указана клиентская база.');
            return self::FAILURE;
        }
        config(['database.connections.tswms_client' => [
            'driver' => 'mysql', 'host' => $client->{'sql-server'} ?: config('database.connections.tswms.host'),
            'port' => 3306, 'database' => $this->sourceDatabase, 'username' => $client->{'sql-user'}, 'password' => $client->{'sql-password'}, 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'prefix' => '',
        ]]);
        DB::purge('tswms_client');
        $this->source = DB::connection('tswms_client');
        $only = array_filter($this->option('only'));
        $this->runStep('clients', fn () => $this->importPartners());
        $this->runStep('goods', fn () => $this->importGoods(), $only);
        $this->runStep('warehouses', fn () => $this->importWarehouses(), $only);
        $this->runStep('services', fn () => $this->importServices(), $only);
        $this->runStep('documents', fn () => $this->importDocuments(), $only);
        $this->info("Импорт завершён: создано {$this->created}, обновлено {$this->updated}.");
        return self::SUCCESS;
    }

    private function configureSource(): void
    {
        $path = base_path('../tswms/laravel-tswms/.env');
        $env = [];
        foreach (@file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            if (str_starts_with(trim($line), '#') || ! str_contains($line, '=')) continue;
            [$key, $value] = explode('=', $line, 2);
            $env[trim($key)] = trim(trim($value), "\"'");
        }
        config(['database.connections.tswms' => ['driver'=>'mysql','host'=>$env['DB_HOST'] ?? '127.0.0.1','port'=>$env['DB_PORT'] ?? 3306,'database'=>$env['DB_DATABASE'] ?? 'tswms_billing','username'=>$env['DB_USERNAME'] ?? 'root','password'=>$env['DB_PASSWORD'] ?? '','charset'=>'utf8mb4','collation'=>'utf8mb4_unicode_ci','prefix'=>'']]);
    }

    private function runStep(string $name, callable $callback, array $only = []): void
    {
        if ($only !== [] && ! in_array($name, $only, true)) return;
        $this->components->task($name, function () use ($callback): void { if (! $this->option('dry-run')) $callback(); });
    }

    private function importPartners(): void
    {
        foreach ($this->source->table('tswms-partners')->get() as $row) {
            $partnerId = (string) $row->id;
            $clientId = $this->mapped('tswms-partners', $partnerId, 'App\\Models\\Client');
            $data = ['name'=>(string)($row->name ?? 'Партнёр #'.$row->id), 'shortname'=>'tswms_partner_'.$row->id, 'status'=>(int)($row->active ?? 1), 'tenant_id'=>$this->tenant, 'updated_at'=>now(), 'created_at'=>now()];
            if ($clientId) { DB::table('clients.clients')->where('id',$clientId)->update($data); $this->updated++; } else { $clientId=(string)DB::table('clients.clients')->insertGetId($data); $this->created++; }
            $this->remember('tswms-partners',$partnerId,'App\\Models\\Client',$clientId,$row);
            $company = array_filter(['name'=>$row->name ?? null,'shortname'=>'tswms_company_'.$row->id,'fullname'=>$row->{'org-name'} ?? null,'inn'=>$row->inn ?? null,'ogrn'=>$row->ogrn ?? null,'phone'=>$row->{'org-phone'} ?? null,'address_reg'=>$row->{'legal-addr'} ?? null,'director_position'=>$row->{'director-role'} ?? null,'director_fio'=>trim(($row->{'director-lastname'} ?? '').' '.($row->{'director-firstname'} ?? '').' '.($row->{'director-patronymic'} ?? '')),'bank'=>$row->{'bank-name'} ?? null,'bik'=>$row->{'bank-bik'} ?? null,'rasch_schet'=>$row->{'bank-account'} ?? null,'korr_schet'=>$row->{'correspondent-account'} ?? null,'client_id'=>$clientId,'status'=>1,'tenant_id'=>$this->tenant,'src'=>json_encode(['source_system'=>'tswms','source_id'=>$partnerId], JSON_UNESCAPED_UNICODE),'created_at'=>now(),'updated_at'=>now()]);
            if (count(array_filter($company, fn($v)=>$v !== null && $v !== '')) > 4) $this->upsert('clients.companies',$company,'tswms-partners',$partnerId,'App\\Models\\ClientCompany','client_id');
        }
    }

    private function importGoods(): void { if (! $this->source->getSchemaBuilder()->hasTable('tswms-goods')) return; foreach ($this->source->table('tswms-goods')->get() as $row) { $id=(string)$row->id; $barcodes=array_values(array_filter([(string)($row->{'barcode-good'}??'')])); $articles=array_values(array_filter([(string)($row->article??'')])); $data=['name'=>(string)($row->name??'Товар #'.$id),'shortname'=>'tswms_good_'.$id,'barcodes'=>json_encode($barcodes),'articul'=>json_encode($articles),'status'=>1,'tenant_id'=>$this->tenant,'is_from_external'=>1,'src'=>json_encode(['source_system'=>'tswms','source_id'=>$id,'partner_id'=>$row->partner??null],JSON_UNESCAPED_UNICODE),'created_at'=>now(),'updated_at'=>now()]; $this->upsert('goods.goods',$data,'tswms-goods',$id,'App\\Models\\Good'); } }
    private function importWarehouses(): void { if (! $this->source->getSchemaBuilder()->hasTable('tswms-places')) return; $warehouseId=DB::table('wms.warehouses')->where('tenant_id',$this->tenant)->orderBy('id')->value('id') ?: DB::table('wms.warehouses')->insertGetId(['name'=>'Основной','shortname'=>'tswms_main_'.$this->sourceClientId,'status'=>1,'tenant_id'=>$this->tenant,'created_at'=>now(),'updated_at'=>now()]); $zoneId=DB::table('wms.zones')->where('tenant_id',$this->tenant)->orderBy('id')->value('id') ?: DB::table('wms.zones')->insertGetId(['name'=>'Основная','shortname'=>'tswms_main_'.$this->sourceClientId,'status'=>1,'tenant_id'=>$this->tenant,'created_at'=>now(),'updated_at'=>now()]); foreach($this->source->table('tswms-places')->get() as $row){$this->upsert('wms.cells',['warehouse_id'=>$warehouseId,'zone_id'=>$zoneId,'name'=>(string)($row->name??'Ячейка #'.$row->id),'shortname'=>'tswms_cell_'.$row->id,'row'=>(int)($row->row??0),'level'=>(int)($row->level??0),'number'=>(int)($row->number??$row->id),'status'=>1,'tenant_id'=>$this->tenant,'created_at'=>now(),'updated_at'=>now()],'tswms-places',(string)$row->id,'App\\Models\\Cell');} }
    private function importServices(): void { if (! $this->source->getSchemaBuilder()->hasTable('tswms-directories-ff-services')) return; foreach($this->source->table('tswms-directories-ff-services')->get() as $row){$this->upsert('wms.services_ff',['name'=>(string)($row->name??'Услуга #'.$row->id),'shortname'=>'tswms_service_'.$row->id,'status'=>(int)($row->active??1),'price'=>(float)($row->{'default-price'}??0),'tenant_id'=>$this->tenant,'is_visible'=>1,'created_at'=>now(),'updated_at'=>now()],'tswms-directories-ff-services',(string)$row->id,'App\\Models\\ServiceFf');} }
    private function importDocuments(): void { if (! $this->source->getSchemaBuilder()->hasTable('tswms-documents')) return; $this->warn('Документы обнаружены; перенос строк и сторон будет добавлен после проверки структуры исходных таблиц.'); }

    private function upsert(string $table,array $data,string $sourceTable,string $sourceId,string $entity,string $lookup=''): void { $target=$this->mapped($sourceTable,$sourceId,$entity); if($target){DB::table($table)->where('id',$target)->update($data);$this->updated++;}else{$target=(string)DB::table($table)->insertGetId($data);$this->created++;} $this->remember($sourceTable,$sourceId,$entity,$target,(object)$data); }
    private function mapped(string $table,string $id,string $entity): ?string { return DB::table('wms.tswms_import_mappings')->where(['source_system'=>'tswms','source_client_id'=>$this->sourceClientId,'source_table'=>$table,'source_id'=>$id,'target_entity'=>$entity])->value('target_id'); }
    private function remember(string $table,string $id,string $entity,string $target,$row): void { DB::table('wms.tswms_import_mappings')->updateOrInsert(['source_system'=>'tswms','source_client_id'=>$this->sourceClientId,'source_table'=>$table,'source_id'=>$id,'target_entity'=>$entity],['target_id'=>$target,'source_database'=>$this->sourceDatabase,'source_hash'=>hash('sha256',json_encode($row,JSON_UNESCAPED_UNICODE)),'updated_at'=>now(),'created_at'=>now()]); }
}
