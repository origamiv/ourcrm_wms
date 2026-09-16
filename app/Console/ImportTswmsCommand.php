<?php

declare(strict_types=1);

namespace App\Console;

use App\Jobs\ImportRunCoordinatorJob;
use App\Models\ImportRun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;
use RuntimeException;
use Throwable;

final class ImportTswmsCommand extends Command
{
    protected $signature = 'wms:import:tswms {--tenant= : Тенант WMS} {--tswms-client-id= : ID клиента TSWMS} {--dry-run} {--only=*} {--inline : Выполнить этап непосредственно внутри job} {--count-only : Вернуть количество строк task_goods} {--task-goods-offset=0 : Смещение строк task_goods} {--task-goods-limit=0 : Ограничение строк task_goods}';

    protected $description = 'Импортирует данные выбранного клиента TSWMS в тенант WMS';

    private string $tenant;

    private int $sourceClientId;

    private string $sourceDatabase = '';

    private $source;

    private $billing;

    private array $maps = [];

    private int $created = 0;

    private int $updated = 0;

    public function handle(): int
    {
        $this->tenant = (string) ($this->option('tenant') ?: '');
        $this->sourceClientId = (int) ($this->option('tswms-client-id') ?: 0);
        if ($this->tenant === '') {
            $this->components->error('Нужно указать --tenant.');

            return self::FAILURE;
        }
        if (! DB::table('public.tenants')->where('id', $this->tenant)->exists()) {
            $this->components->error('Тенант WMS не найден.');

            return self::FAILURE;
        }
        if (! $this->option('inline')) {
            $only = array_values(array_filter($this->option('only')));
            $activeQuery = ImportRun::query()
                ->where('tenant_id', $this->tenant)
                ->whereIn('status', ['queued', 'running']);
            $sourceClientId = (int) ($this->option('tswms-client-id') ?: 0);
            if ($sourceClientId > 0) {
                $activeQuery->where('source_client_id', $sourceClientId);
            }
            $active = $activeQuery->exists();
            if ($active) {
                $this->components->error('Для этого тенанта уже выполняется импорт TSWMS.');

                return self::FAILURE;
            }
            $import = ImportRun::query()->create([
                'tenant_id' => $this->tenant,
                'project' => 'tswms',
                'name' => $only === [] ? 'Полный импорт' : implode(', ', $only),
                'source_client_id' => (int) ($this->option('tswms-client-id') ?: 0) ?: null,
                'status' => 'queued',
                'total_stages' => $only === [] ? 13 : count($only),
                'total_chunks' => 0,
                'options' => [
                    'only' => $only,
                    'dry_run' => (bool) $this->option('dry-run'),
                ],
            ]);
            ImportRunCoordinatorJob::dispatch($import->id)->onConnection('redis')->onQueue('imports');
            $this->components->info("Импорт TSWMS #{$import->id} поставлен в очередь imports.");

            return self::SUCCESS;
        }
        try {
            [$billing, $clientConfig] = $this->configureSource();
            $this->billing = $billing;
            if ($this->sourceClientId < 1) {
                $this->sourceClientId = $this->sourceClientIdFromConfig($clientConfig) ?: 0;
            }
            if ($this->sourceClientId > 0) {
                $client = $billing->table('clients')->where('id', $this->sourceClientId)->first();
                if (! $client) {
                    throw new RuntimeException("Клиент TSWMS #{$this->sourceClientId} не найден в billing-БД.");
                }
                $clientConfig = $this->resolveClientPlaceholders($clientConfig, $client);
            }
        } catch (Throwable $exception) {
            $this->components->error('Не удалось подключиться к billing-БД TSWMS: '.$exception->getMessage());

            return self::FAILURE;
        }
        $this->sourceDatabase = (string) ($clientConfig['database'] ?? '');
        if ($this->sourceDatabase === '') {
            $this->components->error('У клиента TSWMS не указана клиентская база.');

            return self::FAILURE;
        }
        config(['database.connections.tswms_client' => $this->mysqlConfig($clientConfig)]);
        DB::purge('tswms_client');
        $this->source = DB::connection('tswms_client');
        if ($this->option('count-only')) {
            $count = $this->source->getSchemaBuilder()->hasTable('tswms-tasks-goods')
                ? $this->source->table('tswms-tasks-goods')->count()
                : 0;
            $this->line('TSWMS_TASK_GOODS_COUNT:'.$count);

            return self::SUCCESS;
        }
        $only = array_filter($this->option('only'));
        $this->runStep('clients', fn () => $this->importPartners(), $only);
        $this->runStep('accounts', fn () => $this->importAccounts(), $only);
        $this->runStep('webhooks', fn () => $this->importWebhooks(), $only);
        $this->runStep('goods', fn () => $this->importGoods(), $only);
        $this->runStep('warehouses', fn () => $this->importWarehouses(), $only);
        $this->runStep('services', fn () => $this->importServices(), $only);
        $this->runStep('documents', fn () => $this->importDocuments(), $only);
        $this->runStep('task_stages', fn () => $this->importTaskStages(), $only);
        $this->runStep('users', fn () => $this->importImportedUsers(), $only);
        $this->runStep('tasks', fn () => $this->importTasks(), $only);
        $this->runStep('task_goods', fn () => $this->importTaskGoods(), $only);
        $this->runStep('acceptances', fn () => $this->importAcceptances(), $only);
        $this->runStep('cell_goods', fn () => $this->importCellGoods(), $only);
        $this->info("Импорт завершён: создано {$this->created}, обновлено {$this->updated}.");
        $this->line('IMPORT_RECORDS:'.($this->created + $this->updated));

        return self::SUCCESS;
    }

    private function configureSource(): array
    {
        $projectTenant = DB::table('public.tenant_projects as tp')
            ->join('public.projects as p', 'p.id', '=', 'tp.project_id')
            ->where('tp.tenant_id', $this->tenant)
            ->whereNull('tp.deleted_at')
            ->where('tp.status', 1)->where('p.status', 1)
            ->whereRaw("lower(replace(replace(coalesce(p.name,''), '-', ''), '_', '')) = 'tswms'")
            ->select('tp.options', 'p.name')->first();
        if (! $projectTenant) {
            throw new RuntimeException('Активный проект TS-WMS для указанного тенанта не найден.');
        }
        $options = is_string($projectTenant->options) ? json_decode($projectTenant->options, true) : (array) $projectTenant->options;
        if ($this->sourceClientId < 1 && preg_match('/(?:^|[^0-9])(\d+)(?:$|[^0-9])/', (string) $projectTenant->name, $match)) {
            $this->sourceClientId = (int) $match[1];
        }
        $billingConfig = (array) ($options['billing'] ?? []);
        $clientConfig = (array) ($options['client'] ?? []);
        if ($clientConfig === []) {
            foreach ($options as $key => $value) {
                if (preg_match('/^client(?:_|-)?(\\d+)$/i', (string) $key, $match)) {
                    $clientConfig = (array) $value;
                    if ($this->sourceClientId < 1) {
                        $this->sourceClientId = (int) $match[1];
                    }
                    break;
                }
            }
        }
        if ($billingConfig === [] || $clientConfig === []) {
            throw new RuntimeException('В options проекта должны быть блоки billing и client.');
        }
        config(['database.connections.tswms' => $this->mysqlConfig($billingConfig)]);
        $this->components->info('Источник: '.$projectTenant->name.'; подключения billing/client загружены из tenant_projects.options.');

        return [DB::connection('tswms'), $clientConfig];
    }

    private function mysqlConfig(array $config): array
    {
        $ssl = filter_var($config['ssl'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $options = [];
        if ($ssl) {
            $ca = $config['ssl_ca'] ?? $config['ca'] ?? base_path('../tswms/laravel-tswms/storage/root.crt');
            if (! is_file((string) $ca)) {
                throw new RuntimeException('SSL включён, но CA-сертификат не найден: '.$ca);
            }
            $options[PDO::MYSQL_ATTR_SSL_CA] = $ca;
            // TSWMS uses an internal certificate whose hostname may differ from
            // the private DB address. The transport remains encrypted; hostname
            // verification is controlled by the source project's SSL settings.
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = (bool) ($config['verify_server_cert'] ?? false);
        }

        return ['driver' => 'mysql', 'host' => $config['host'] ?? '', 'port' => (int) ($config['port'] ?? 3306), 'database' => $config['database'] ?? '', 'username' => $config['username'] ?? '', 'password' => $config['password'] ?? '', 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'prefix' => '', 'options' => $options];
    }

    private function sourceClientIdFromConfig(array $config): ?int
    {
        foreach ([$config['host'] ?? '', $config['database'] ?? '', $config['username'] ?? '', $config['password'] ?? ''] as $value) {
            if (preg_match('/clients\[(\d+)\]/', (string) $value, $match)) {
                return (int) $match[1];
            }
            if (preg_match('/tswms[_-]u(\d+)/i', (string) $value, $match)) {
                return (int) $match[1];
            }
        }

        return null;
    }

    private function resolveClientPlaceholders(array $config, object $client): array
    {
        foreach ($config as $key => $value) {
            if (is_string($value)) {
                $config[$key] = preg_replace_callback('/\$\{clients\[\d+\]\.([^}]+)\}/', fn ($m) => (string) ($client->{$m[1]} ?? ''), $value);
            }
        }

        return $config;
    }

    private function runStep(string $name, callable $callback, array $only = []): void
    {
        if ($only !== [] && ! in_array($name, $only, true)) {
            return;
        }
        $this->components->task($name, function () use ($callback): void {
            if (! $this->option('dry-run')) {
                $callback();
            }
        });
    }

    private function importPartners(): void
    {
        foreach ($this->source->table('tswms-partners')->get() as $row) {
            $partnerId = (string) $row->id;
            $clientId = $this->mapped('tswms-partners', $partnerId, 'App\\Models\\Client');
            $data = ['name' => (string) ($row->name ?? 'Партнёр #'.$row->id), 'shortname' => 'tswms_partner_'.$row->id, 'status' => (int) ($row->active ?? 1), 'tenant_id' => $this->tenant, 'updated_at' => now(), 'created_at' => now()];
            if ($clientId) {
                DB::table('clients.clients')->where('id', $clientId)->update($data);
                $this->updated++;
            } else {
                $clientId = (string) DB::table('clients.clients')->insertGetId($data);
                $this->created++;
            }
            $this->remember('tswms-partners', $partnerId, 'App\\Models\\Client', $clientId, $row);
            $company = array_filter(['name' => $row->name ?? null, 'shortname' => 'tswms_company_'.$row->id, 'fullname' => $row->{'org-name'} ?? null, 'inn' => $row->inn ?? null, 'ogrn' => $row->ogrn ?? null, 'phone' => $row->{'org-phone'} ?? null, 'director_position' => $row->{'director-role'} ?? null, 'director_fio' => trim(($row->{'director-lastname'} ?? '').' '.($row->{'director-firstname'} ?? '').' '.($row->{'director-patronymic'} ?? '')), 'bank' => $row->{'bank-name'} ?? null, 'bik' => $row->{'bank-bik'} ?? null, 'rasch_schet' => $row->{'bank-account'} ?? null, 'korr_schet' => $row->{'correspondent-account'} ?? null, 'client_id' => $clientId, 'status' => 1, 'tenant_id' => $this->tenant, 'src' => json_encode(['source_system' => 'tswms', 'source_id' => $partnerId, 'legal_address' => $row->{'legal-addr'} ?? null], JSON_UNESCAPED_UNICODE), 'created_at' => now(), 'updated_at' => now()]);
            if (count(array_filter($company, fn ($v) => $v !== null && $v !== '')) > 4) {
                $this->upsert('clients.companies', $company, 'tswms-partners', $partnerId, 'App\\Models\\ClientCompany', 'client_id');
            }
        }
    }

    private function importGoods(): void
    {
        if (! $this->source->getSchemaBuilder()->hasTable('tswms-goods')) {
            return;
        }
        $labels = [];
        if ($this->source->getSchemaBuilder()->hasTable('tswms-goods-external-labels')) {
            foreach ($this->source->table('tswms-goods-external-labels')->get() as $label) {
                $goodId = (string) ($label->{'good-id'} ?? $label->good_id ?? '');
                $value = trim((string) ($label->label ?? $label->value ?? ''));
                if ($goodId === '' || $value === '') {
                    continue;
                }
                $type = mb_strtolower((string) ($label->type ?? ''));
                $labels[$goodId][$type === 'article' ? 'articles' : 'barcodes'][] = $value;
            }
        }
        foreach ($this->source->table('tswms-goods')->get() as $row) {
            $id = (string) $row->id;
            $barcodes = array_values(array_unique(array_filter(array_merge([(string) ($row->{'barcode-good'} ?? '')], $labels[$id]['barcodes'] ?? []))));
            $articles = array_values(array_unique(array_filter(array_merge([(string) ($row->article ?? '')], $labels[$id]['articles'] ?? []))));
            $sourceData = (array) $row;
            $data = ['code' => $id, 'name' => (string) ($row->name ?? 'Товар #'.$id), 'shortname' => 'tswms_good_'.$id, 'barcodes' => json_encode($barcodes), 'articul' => json_encode($articles), 'status' => (int) ($row->active ?? 1), 'tenant_id' => $this->tenant, 'is_from_external' => 1, 'src' => json_encode(['source_system' => 'tswms', 'source_id' => $id, 'partner_id' => $row->partner ?? null, 'source_fields' => $sourceData], JSON_UNESCAPED_UNICODE), 'created_at' => now(), 'updated_at' => now()];
            $this->upsert('goods.goods', $data, 'tswms-goods', $id, 'App\\Models\\Good');
        }
    }

    private function importWarehouses(): void
    {
        $kindMap = [];
        if ($this->source->getSchemaBuilder()->hasTable('warehouse_types')) {
            foreach ($this->source->table('warehouse_types')->get() as $type) {
                $kind = DB::table('wms.kind_warehouses')->where('tenant_id', $this->tenant)->where('name', (string) $type->name)->first();
                $kindId = $kind?->id ?: DB::table('wms.kind_warehouses')->insertGetId(['name' => (string) $type->name, 'shortname' => 'tswms_kind_'.$type->id, 'status' => 1, 'tenant_id' => $this->tenant, 'created_at' => now(), 'updated_at' => now()]);
                $kindMap[(string) $type->id] = $kindId;
            }
        }
        $warehouseMap = [];
        if ($this->source->getSchemaBuilder()->hasTable('warehouses')) {
            foreach ($this->source->table('warehouses')->get() as $warehouse) {
                $data = ['code' => (string) $warehouse->id, 'name' => (string) ($warehouse->name ?? 'Склад #'.$warehouse->id), 'shortname' => 'tswms_warehouse_'.$warehouse->id, 'type_warehouse_id' => null, 'kind_warehouse_id' => $kindMap[(string) ($warehouse->warehouse_type_id ?? '')] ?? null, 'address' => $warehouse->address ?? null, 'timezone' => $warehouse->timezone ?? null, 'contact_name' => $warehouse->contact_name ?? null, 'contact_phone' => $warehouse->contact_phone ?? null, 'working_hours' => $warehouse->working_hours ?? null, 'width' => (int) ($warehouse->width ?? 60), 'height' => (int) ($warehouse->height ?? 40), 'scheme_json' => isset($warehouse->scheme_json) ? json_encode($warehouse->scheme_json) : null, 'status' => (int) ($warehouse->is_active ?? 1), 'tenant_id' => $this->tenant, 'created_at' => now(), 'updated_at' => now()];
                $this->upsert('wms.warehouses', $data, 'warehouses', (string) $warehouse->id, 'App\\Models\\Warehouse');
                $warehouseMap[(string) $warehouse->id] = $this->mapped('warehouses', (string) $warehouse->id, 'App\\Models\\Warehouse');
            }
        }
        if (! $this->source->getSchemaBuilder()->hasTable('tswms-places')) {
            return;
        }
        $warehouseId = $warehouseMap ? (int) reset($warehouseMap) : (int) (DB::table('wms.warehouses')->where('tenant_id', $this->tenant)->orderBy('id')->value('id') ?: DB::table('wms.warehouses')->insertGetId(['name' => 'Основной', 'shortname' => 'tswms_main_'.$this->sourceClientId, 'status' => 1, 'tenant_id' => $this->tenant, 'created_at' => now(), 'updated_at' => now()]));
        $zoneId = DB::table('wms.zones')->where('tenant_id', $this->tenant)->orderBy('id')->value('id') ?: DB::table('wms.zones')->insertGetId(['name' => 'Основная', 'shortname' => 'tswms_main_'.$this->sourceClientId, 'status' => 1, 'tenant_id' => $this->tenant, 'created_at' => now(), 'updated_at' => now()]);
        $storageMap = ['cell' => (int) (DB::table('wms.type_storage')->where('shortname', 'cells')->value('id') ?? 0), 'box' => (int) (DB::table('wms.type_storage')->where('shortname', 'boxes')->value('id') ?? 0), 'transit' => (int) (DB::table('wms.type_storage')->where('shortname', 'transit')->value('id') ?? 0)];
        foreach ($this->source->table('tswms-places')->get() as $row) {
            $sourceWarehouse = (string) ($row->warehouse_id ?? '');
            $placeType = mb_strtolower((string) ($row->type ?? ''));
            $storageKey = str_contains($placeType, 'transit') || str_contains($placeType, 'транзит') ? 'transit' : (str_contains($placeType, 'box') || str_contains($placeType, 'короб') ? 'box' : 'cell');
            $storageId = $storageMap[$storageKey];
            $this->upsert('wms.cells', ['warehouse_id' => $warehouseMap[$sourceWarehouse] ?? $warehouseId, 'zone_id' => $zoneId, 'name' => (string) ($row->name ?? 'Ячейка #'.$row->id), 'shortname' => 'tswms_cell_'.$row->id, 'row' => (int) ($row->row ?? 0), 'level' => (int) ($row->level ?? 0), 'number' => (int) ($row->number ?? $row->id), 'priority' => (int) ($row->priority ?? 0), 'type_storage_id' => $storageId ?: null, 'status' => (int) ($row->enabled ?? 1), 'tenant_id' => $this->tenant, 'created_at' => now(), 'updated_at' => now()], 'tswms-places', (string) $row->id, 'App\\Models\\Cell');
        }
    }

    private function importServices(): void
    {
        if (! $this->source->getSchemaBuilder()->hasTable('tswms-directories-ff-services')) {
            return;
        } foreach ($this->source->table('tswms-directories-ff-services')->get() as $row) {
            $this->upsert('wms.services_ff', ['name' => (string) ($row->name ?? 'Услуга #'.$row->id), 'shortname' => 'tswms_service_'.$row->id, 'status' => (int) ($row->active ?? 1), 'price' => (float) ($row->{'default-price'} ?? 0), 'tenant_id' => $this->tenant, 'is_visible' => 1, 'created_at' => now(), 'updated_at' => now()], 'tswms-directories-ff-services', (string) $row->id, 'App\\Models\\ServiceFf');
        }
    }

    private function importAccounts(): void
    {
        $rows = [];
        if ($this->billing && $this->billing->getSchemaBuilder()->hasTable('legasy_integrations_registry')) {
            $rows = array_merge($rows, array_map(fn ($r) => [$r, 'legasy_integrations_registry'], $this->billing->table('legasy_integrations_registry')->where('client_id', $this->sourceClientId)->where('is_deleted', 0)->get()->all()));
        } if ($this->source->getSchemaBuilder()->hasTable('tswms-integrations')) {
            $rows = array_merge($rows, array_map(fn ($r) => [$r, 'tswms-integrations'], $this->source->table('tswms-integrations')->get()->all()));
        } foreach ($rows as [$r,$table]) {
            $partner = (string) ($r->{'partner-id'} ?? $r->partner_id ?? '');
            $client = $this->mapped('tswms-partners', $partner, 'App\\Models\\Client');
            if (! $client) {
                continue;
            }$type = (string) ($r->type ?? 'integration');
            $name = (string) ($r->name ?? $type);
            $src = ['source_system' => 'tswms', 'source_table' => $table, 'source_id' => (string) $r->id, 'source_fields' => (array) $r, 'credentials' => ['key1' => $r->key1 ?? null, 'key2' => $r->key2 ?? null, 'key3' => $r->key3 ?? null]];
            $serviceId = $this->mapped($table, (string) $r->id, 'App\\Models\\ClientService');
            if (! $serviceId) {
                $this->upsert('clients.services', ['name' => $name, 'shortname' => $this->latinShortname('service_'.$type.'_'.$r->id), 'status' => (int) ($r->active ?? $r->is_active ?? 1), 'tenant_id' => $this->tenant, 'created_at' => now(), 'updated_at' => now()], $table, (string) $r->id, 'App\\Models\\ClientService');
                $serviceId = $this->mapped($table, (string) $r->id, 'App\\Models\\ClientService');
            }$data = ['name' => $name, 'shortname' => $this->latinShortname($type.'_'.$r->id), 'host' => $r->host ?? null, 'login' => $r->login ?? null, 'pass' => $r->pass ?? null, 'token' => $r->token ?? ($r->key1 ?? null), 'descr' => 'Доступ TSWMS: '.$type, 'status' => (int) ($r->active ?? $r->is_active ?? 1), 'src' => json_encode($src, JSON_UNESCAPED_UNICODE), 'client_id' => (int) $client, 'service_id' => $serviceId ? (int) $serviceId : null, 'tenant_id' => $this->tenant, 'created_at' => now(), 'updated_at' => now()];
            $this->upsert('clients.accounts', $data, $table, (string) $r->id, 'App\\Models\\ClientAccount');
        }
    }

    private function importWebhooks(): void
    {
        $rows = [];
        if ($this->billing && $this->billing->getSchemaBuilder()->hasTable('legasy_integrations_registry')) {
            $rows = array_merge($rows, array_map(fn ($r) => [$r, 'legasy_integrations_registry'], $this->billing->table('legasy_integrations_registry')->where('client_id', $this->sourceClientId)->where('is_deleted', 0)->get()->all()));
        } if ($this->source->getSchemaBuilder()->hasTable('tswms-integrations')) {
            $rows = array_merge($rows, array_map(fn ($r) => [$r, 'tswms-integrations'], $this->source->table('tswms-integrations')->get()->all()));
        } foreach ($rows as [$r,$table]) {
            $type = mb_strtolower((string) ($r->type ?? 'integration'));
            $sourceId = (string) $r->id;
            $account = $this->mapped($table, $sourceId, 'App\\Models\\ClientAccount');
            if (! $account) {
                $account = $this->mapped('tswms-integrations', $sourceId, 'App\\Models\\ClientAccount');
            }$service = $this->localIntegrationService($type);
            $incoming = in_array(true, [(bool) ($r->goods_active ?? $r->{'goods-active'} ?? false), (bool) ($r->fbs_active ?? $r->{'fbs-active'} ?? false)], true);
            $params = ['account_id' => $account ? (int) $account : null, 'source' => ['table' => $table, 'id' => $sourceId, 'type' => $type], 'warehouse_id' => $r->warehouse_id ?? null, 'settings' => $r->config_json ?? $r->{'config-json'} ?? null, 'source_fields' => (array) $r];
            $data = ['name' => (string) ($r->name ?? $type), 'shortname' => $this->latinShortname($type.'_'.$sourceId), 'service_id' => $service, 'type_hook_id' => 2, 'rules_id' => null, 'params' => json_encode($params, JSON_UNESCAPED_UNICODE), 'status' => (int) ($r->active ?? $r->is_active ?? 1), 'cnt' => 0, 'dat_last_run' => $r->last_check_at ?? $r->{'last-check'} ?? null, 'tenant_id' => $this->tenant, 'created_at' => now(), 'updated_at' => now()];
            $this->upsert('integration.webhooks', $data, $table, $sourceId, 'App\\Models\\IntegrationWebhook');
        }
    }

    private function localIntegrationService(string $type): ?int
    {
        $name = str_contains($type, 'ozon') ? 'Ozon' : (str_contains($type, 'wb') || str_contains($type, 'wildberries') ? 'Wildberries' : (str_contains($type, 'yandex') ? 'Yandex Market' : $type));
        $id = DB::table('integration.services')->whereRaw('lower(name)=lower(?)', [$name])->value('id');
        if ($id) {
            return (int) $id;
        }

        return (int) DB::table('integration.services')->insertGetId(['name' => $name, 'shortname' => $this->latinShortname($name), 'status' => 1, 'tenant_id' => $this->tenant, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function latinShortname(string $value): string
    {
        $value = mb_strtolower(trim($value));
        if (function_exists('transliterator_transliterate')) {
            $value = transliterator_transliterate('Any-Latin; Latin-ASCII', $value) ?: $value;
        }$value = preg_replace('/[^a-z0-9]+/', '_', $value) ?: 'integration';

        return trim($value, '_');
    }

    private function importDocuments(): void
    {
        if (! $this->source->getSchemaBuilder()->hasTable('tswms-documents')) {
            return;
        } $this->warn('Документы обнаружены; перенос строк и сторон будет добавлен после проверки структуры исходных таблиц.');
    }

    private function importTaskStages(): void
    {
        if (! $this->source->getSchemaBuilder()->hasTable('tswms-tasks-statuses')) {
            return;
        } foreach ($this->source->table('tswms-tasks-statuses')->get() as $r) {
            $this->upsert('wms.task_stages', ['name' => (string) ($r->name ?? 'Этап #'.$r->id), 'shortname' => 'tswms_stage_'.$r->id, 'status' => (int) ($r->active ?? 1), 'icon' => $r->icon ?? null, 'tenant_id' => $this->tenant, 'src' => json_encode(['source_system' => 'tswms', 'source_id' => (string) $r->id, 'source_fields' => (array) $r], JSON_UNESCAPED_UNICODE), 'created_at' => now(), 'updated_at' => now()], 'tswms-tasks-statuses', (string) $r->id, 'App\\Models\\TaskStage');
        }
    }

    private function importImportedUsers(): void
    {
        $table = $this->source->getSchemaBuilder()->hasTable('users') ? 'users' : ($this->source->getSchemaBuilder()->hasTable('tswms-users') ? 'tswms-users' : null);
        if (! $table) {
            return;
        } foreach ($this->source->table($table)->get() as $r) {
            $first = (string) ($r->first_name ?? $r->firstname ?? '');
            $last = (string) ($r->last_name ?? $r->lastname ?? '');
            $name = trim((string) ($r->name ?? trim($last.' '.$first.' '.($r->patronymic ?? $r->middlename ?? ''))));
            $this->upsert('wms.users', ['code' => (string) $r->id, 'name' => $name ?: 'Пользователь #'.$r->id, 'shortname' => 'tswms_user_'.$r->id, 'first_name' => $first, 'last_name' => $last, 'patronymic' => $r->patronymic ?? $r->middlename ?? null, 'email' => $r->email ?? null, 'phone' => $r->phone ?? null, 'login' => $r->login ?? $r->username ?? null, 'status' => (int) ($r->active ?? $r->is_active ?? 1), 'tenant_id' => $this->tenant, 'src' => json_encode(['source_system' => 'tswms', 'source_id' => (string) $r->id, 'source_fields' => (array) $r], JSON_UNESCAPED_UNICODE), 'created_at' => now(), 'updated_at' => now()], 'users', (string) $r->id, 'App\\Models\\ImportedUser');
        }
    }

    private function importTasks(): void
    {
        if (! $this->source->getSchemaBuilder()->hasTable('tswms-tasks')) {
            return;
        } $typeNames = $this->sourceNameMap(['tswms-tasks-types'], 'name');
        $priorityNames = $this->sourceNameMap(['tswms-tasks-priority'], 'priority');
        foreach ($this->source->table('tswms-tasks')->get() as $r) {
            $id = (string) $r->id;
            $client = $this->mapped('tswms-partners', (string) ($r->{'partner-id'} ?? $r->partner_id ?? ''), 'App\\Models\\Client');
            $stage = $this->mapped('tswms-tasks-statuses', (string) ($r->{'status-id'} ?? $r->status_id ?? ''), 'App\\Models\\TaskStage');
            $typeValue = $this->sourceValue($r, ['task-type', 'task_type', 'type_id']);
            $typeName = is_numeric($typeValue) ? ($typeNames[(string) $typeValue] ?? null) : $typeValue;
            $type = is_numeric($typeValue) ? ([1 => 1, 2 => 5, 3 => 8, 4 => 9][(int) $typeValue] ?? $this->findLocalNameOnly('wms.task_types', $typeName)) : $this->findLocalNameOnly('wms.task_types', $typeName);
            $priorityValue = $this->sourceValue($r, ['priority-id', 'priority_id']);
            $priorityName = is_numeric($priorityValue) ? ($priorityNames[(string) $priorityValue] ?? null) : $priorityValue;
            $priority = $this->findLocalNameOnly('wms.priorities', $priorityName);
            if ($priority === null && is_numeric($priorityValue)) {
                $priority = [1 => 3, 2 => 4, 3 => 5, 4 => 8][(int) $priorityValue] ?? null;
            }$warehouse = $this->mapped('warehouses', (string) ($r->warehouse_id ?? $r->{'warehouse-id'} ?? ''), 'App\\Models\\Warehouse');
            $creator = $this->mappedUser($r->{'creator-id'} ?? $r->creator_id ?? null);
            $responsible = $this->mappedUser($r->{'charged-user-id'} ?? $r->user_id ?? null);
            $data = ['code' => $id, 'name' => (string) ($r->{'task-name'} ?? $r->name ?? 'Задача #'.$id), 'shortname' => 'tswms_task_'.$id, 'client_id' => $client, 'task_type_id' => $type, 'task_stage_id' => $stage, 'status_id' => 1, 'planned_at' => $r->{'date-plan'} ?? $r->planned_at ?? null, 'started_at' => $r->started_at ?? null, 'completed_at' => $r->{'date-fact'} ?? $r->completed_at ?? null, 'comment' => $r->{'client-comment'} ?? $r->comment ?? null, 'internal_comment' => $r->{'internal-comment'} ?? $r->internal_comment ?? null, 'priority_id' => $priority, 'order_id' => $r->{'created-order-id'} ?? $r->order_id ?? null, 'warehouse_id' => $warehouse, 'user_id' => $responsible, 'created_by_user_id' => $creator, 'charged_at' => $r->{'charged-date'} ?? $r->charged_at ?? null, 'fact_count' => (int) ($r->fact_count ?? 0), 'status' => (int) ($r->active ?? 1), 'tenant_id' => $this->tenant, 'src' => json_encode(['source_system' => 'tswms', 'source_id' => $id, 'source_fields' => (array) $r], JSON_UNESCAPED_UNICODE), 'created_at' => now(), 'updated_at' => now()];
            $this->upsert('wms.tasks', $data, 'tswms-tasks', $id, 'App\\Models\\Task');
        }
    }

    private function importTaskGoods(): void
    {
        if (! $this->source->getSchemaBuilder()->hasTable('tswms-tasks-goods')) {
            return;
        }
        $query = $this->source->table('tswms-tasks-goods')->orderBy('id');
        $limit = max(0, (int) $this->option('task-goods-limit'));
        if ($limit > 0) {
            $query->offset(max(0, (int) $this->option('task-goods-offset')))->limit($limit);
        }
        foreach ($query->get() as $r) {
            $task = $this->mapped('tswms-tasks', (string) ($r->{'task-id'} ?? $r->task_id ?? ''), 'App\\Models\\Task');
            if (! $task) {
                continue;
            }$good = $this->mapped('tswms-goods', (string) ($r->{'good-id'} ?? $r->good_id ?? ''), 'App\\Models\\Good');
            $id = (string) $r->id;
            $this->upsert('wms.task_goods', ['task_id' => $task, 'good_id' => $good, 'shipment_box_number' => $r->{'shipment-box-number'} ?? null, 'count_plan' => $r->{'count-plan'} ?? $r->count_plan ?? null, 'count_fact' => $r->count_fact ?? $r->{'count-fact'} ?? null, 'good_comment' => $r->{'good-comment'} ?? null, 'good_current_barcode' => $r->{'good-current-barcode'} ?? null, 'unit_price' => $r->{'unit-price'} ?? null, 'unit_price_currency_id' => $r->{'unit-price-currency-id'} ?? null, 'tenant_id' => $this->tenant, 'src' => json_encode(['source_system' => 'tswms', 'source_id' => $id, 'source_fields' => (array) $r], JSON_UNESCAPED_UNICODE), 'created_at' => now(), 'updated_at' => now()], 'tswms-tasks-goods', $id, 'App\\Models\\TaskGood');
        }
    }

    private function importAcceptances(): void
    {
        if (! $this->source->getSchemaBuilder()->hasTable('tswms-acceptances')) {
            return;
        }

        $plan = [];
        if ($this->source->getSchemaBuilder()->hasTable('tswms-tasks-goods')) {
            foreach ($this->source->table('tswms-tasks-goods')->get(['task-id', 'count-plan']) as $row) {
                $taskId = (string) ($row->{'task-id'} ?? '');
                if ($taskId !== '') {
                    $plan[$taskId] = ($plan[$taskId] ?? 0) + (int) ($row->{'count-plan'} ?? 0);
                }
            }
        }
        $fact = [];
        if ($this->source->getSchemaBuilder()->hasTable('tswms-acceptances-history')) {
            foreach ($this->source->table('tswms-acceptances-history')->get(['acceptance-id', 'good-count']) as $row) {
                $acceptanceId = (string) ($row->{'acceptance-id'} ?? '');
                if ($acceptanceId !== '') {
                    $fact[$acceptanceId] = ($fact[$acceptanceId] ?? 0) + (int) ($row->{'good-count'} ?? 0);
                }
            }
        }

        foreach ($this->source->table('tswms-acceptances')->get() as $row) {
            $sourceId = (string) $row->id;
            $taskSourceId = (string) ($row->{'task-id'} ?? $row->task_id ?? '');
            $taskId = $this->mapped('tswms-tasks', $taskSourceId, 'App\\Models\\Task');
            $clientId = $this->mapped('tswms-partners', (string) ($row->{'partner-id'} ?? $row->partner_id ?? ''), 'App\\Models\\Client');
            $warehouseId = $taskId ? DB::table('wms.tasks')->where('id', $taskId)->value('warehouse_id') : null;
            $planned = $plan[$taskSourceId] ?? 0;
            $received = $fact[$sourceId] ?? 0;
            $status = $this->acceptanceStatus($row->{'status-id'} ?? $row->status_id ?? null);
            $type = $this->acceptanceType($row->type ?? null);
            $createdAt = $row->{'date-created'} ?? $row->created_at ?? null;
            $finishedAt = $row->{'date-closed'} ?? $row->finished_at ?? null;
            $this->upsert('wms.acceptances', [
                'code' => $sourceId,
                'client_id' => $clientId,
                'warehouse_id' => $warehouseId,
                'task_id' => $taskId,
                'plan_count' => $planned,
                'fact_count' => $received,
                'progress' => $planned > 0 ? min(100, (int) round($received / $planned * 100)) : 0,
                'type_acceptance_id' => $type,
                'created_at' => $createdAt ?: now(),
                'started_at' => $createdAt,
                'finished_at' => $finishedAt,
                'status' => $status,
                'tenant_id' => $this->tenant,
                'src' => json_encode(['source_system' => 'tswms', 'source_id' => $sourceId, 'source_fields' => (array) $row], JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ], 'tswms-acceptances', $sourceId, 'App\\Models\\Acceptance');
        }
    }

    private function acceptanceStatus(mixed $status): int
    {
        return match ((int) $status) {
            3 => 1,
            4 => 2,
            2 => 3,
            default => 0,
        };
    }

    private function acceptanceType(mixed $type): int
    {
        return str_contains(mb_strtolower((string) $type), 'manual') ? 2 : 1;
    }

    private function importCellGoods(): void
    {
        if (! $this->source->getSchemaBuilder()->hasTable('tswms-goods-instances')) {
            return;
        }

        $rows = $this->source->table('tswms-goods-instances')
            ->whereNull('leaving-date')
            ->get(['id', 'place-id', 'good-id', 'count-in-instance', 'entrance-date']);
        $placements = $this->aggregateCellGoods($rows);
        $cellMappings = $this->sourceMappings('tswms-places', 'App\\Models\\Cell');
        $goodMappings = $this->sourceMappings('tswms-goods', 'App\\Models\\Good');
        $seen = [];
        $missing = 0;
        $missingExamples = [];

        foreach ($placements as $placement) {
            $sourceId = $placement['source_id'];
            $cellId = $cellMappings[(string) $placement['place_id']] ?? null;
            $goodId = $goodMappings[(string) $placement['good_id']] ?? null;
            if (! $cellId || ! $goodId) {
                $missing++;
                if (count($missingExamples) < 5) {
                    $missingExamples[] = $sourceId;
                }

                continue;
            }
            $warehouseId = DB::table('wms.cells')->where('id', $cellId)->value('warehouse_id');
            if (! $warehouseId) {
                $this->warn("Пропущено размещение {$sourceId}: у ячейки не найден склад.");

                continue;
            }

            $seen[$sourceId] = true;
            $this->upsert('wms.cell_goods', [
                'warehouse_id' => $warehouseId,
                'cell_id' => $cellId,
                'good_id' => $goodId,
                'cnt' => $placement['cnt'],
                'put_at' => $placement['put_at'],
                'leave_at' => null,
                'deleted_at' => null,
                'tenant_id' => $this->tenant,
                'src' => json_encode([
                    'source_system' => 'tswms',
                    'source_table' => 'tswms-goods-instances',
                    'source_id' => $sourceId,
                    'place_id' => $placement['place_id'],
                    'good_id' => $placement['good_id'],
                    'source_instance_ids' => $placement['source_instance_ids'],
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ], 'tswms-goods-instances', $sourceId, 'App\\Models\\CellGood');
        }
        if ($missing > 0) {
            $this->warn('Пропущено размещений без mapping: '.$missing.'. Примеры: '.implode(', ', $missingExamples).'.');
        }

        $mappings = DB::table('wms.tswms_import_mappings')
            ->where('source_system', 'tswms')
            ->where('source_client_id', $this->sourceClientId)
            ->where('source_table', 'tswms-goods-instances')
            ->where('target_entity', 'App\\Models\\CellGood')
            ->get(['target_id', 'source_id']);
        foreach ($mappings as $mapping) {
            if (isset($seen[(string) $mapping->source_id])) {
                continue;
            }
            DB::table('wms.cell_goods')
                ->where('id', $mapping->target_id)
                ->where('tenant_id', $this->tenant)
                ->whereNull('deleted_at')
                ->update(['cnt' => 0, 'leave_at' => now(), 'deleted_at' => now(), 'updated_at' => now()]);
            $this->updated++;
        }
    }

    private function sourceMappings(string $sourceTable, string $entity): array
    {
        return DB::table('wms.tswms_import_mappings')
            ->where('source_system', 'tswms')
            ->where('source_client_id', $this->sourceClientId)
            ->where('source_table', $sourceTable)
            ->where('target_entity', $entity)
            ->pluck('target_id', 'source_id')
            ->map(fn ($value): string => (string) $value)
            ->all();
    }

    private function aggregateCellGoods(iterable $rows): array
    {
        $placements = [];
        foreach ($rows as $row) {
            $placeId = (string) ($row->{'place-id'} ?? $row->place_id ?? '');
            $goodId = (string) ($row->{'good-id'} ?? $row->good_id ?? '');
            if ($placeId === '' || $goodId === '') {
                continue;
            }
            $sourceId = $placeId.':'.$goodId;
            $placements[$sourceId] ??= [
                'source_id' => $sourceId,
                'place_id' => $placeId,
                'good_id' => $goodId,
                'cnt' => 0,
                'put_at' => null,
                'source_instance_ids' => [],
            ];
            $placements[$sourceId]['cnt'] += (int) ($row->{'count-in-instance'} ?? $row->count_in_instance ?? 0);
            $entranceDate = $row->{'entrance-date'} ?? $row->entrance_date ?? null;
            if ($entranceDate !== null && ($placements[$sourceId]['put_at'] === null || (string) $entranceDate < (string) $placements[$sourceId]['put_at'])) {
                $placements[$sourceId]['put_at'] = $entranceDate;
            }
            if (isset($row->id)) {
                $placements[$sourceId]['source_instance_ids'][] = (string) $row->id;
            }
        }

        return array_values($placements);
    }

    private function sourceValue(object $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            $v = $row->{$key} ?? null;
            if ($v !== null && $v !== '') {
                return $v;
            }
        }

        return null;
    }

    private function sourceNameMap(array $tables, string $field = 'name'): array
    {
        foreach ($tables as $table) {
            if (! $this->source->getSchemaBuilder()->hasTable($table)) {
                continue;
            }$map = [];
            foreach ($this->source->table($table)->get() as $r) {
                $map[(string) $r->id] = (string) ($r->{$field} ?? $r->name ?? $r->title ?? '');
            }

            return $map;
        }

        return [];
    }

    private function findLocalNameOnly(string $table, mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        } $value = mb_strtolower(str_replace('ё', 'е', trim((string) $value)));
        $aliases = ['прием поставки' => 'приемка', 'приём поставки' => 'приемка', 'отгрузка товара' => 'отгрузка', 'обработка товара' => 'обработка', 'прочие задачи' => 'прочие задачи', 'низкий' => 'очень низкий', 'стандартный' => 'средний', 'высокий' => 'высокий', 'супер срочный' => 'критический'];
        $value = $aliases[$value] ?? $value;
        foreach (DB::table($table)->whereNull('deleted_at')->get(['id', 'name']) as $row) {
            $name = mb_strtolower(str_replace('ё', 'е', trim((string) $row->name)));
            if ($name === $value || str_contains($name, $value) || str_contains($value, $name)) {
                return (int) $row->id;
            }
        }

        return null;
    }

    private function findLocalByName(string $table, mixed $value): ?int
    {
        if ($value === null) {
            return null;
        } $q = DB::table($table)->whereNull('deleted_at');
        if (is_numeric($value) && ($id = $q->where('id', (int) $value)->value('id'))) {
            return (int) $id;
        }

        return $q->whereRaw('lower(name)=lower(?)', [(string) $value])->value('id');
    }

    private function mappedUser(mixed $id): ?int
    {
        $value = $id === null ? null : $this->mapped('users', (string) $id, 'App\\Models\\ImportedUser');

        return $value === null ? null : (int) $value;
    }

    private function upsert(string $table, array $data, string $sourceTable, string $sourceId, string $entity, string $lookup = ''): void
    {
        $mapping = DB::table('wms.tswms_import_mappings')->where(['source_system' => 'tswms', 'source_client_id' => $this->sourceClientId, 'source_table' => $sourceTable, 'source_id' => $sourceId, 'target_entity' => $entity])->first();
        $row = (object) $data;
        $hash = $this->sourceHash($row);
        if ($mapping && (string) $mapping->source_hash === $hash) {
            return;
        }
        $target = $mapping?->target_id;
        if ($target) {
            DB::table($table)->where('id', $target)->update($data);
            $this->updated++;
        } else {
            $target = (string) DB::table($table)->insertGetId($data);
            $this->created++;
        }
        $this->remember($sourceTable, $sourceId, $entity, $target, $row);
    }

    private function mapped(string $table, string $id, string $entity): ?string
    {
        return DB::table('wms.tswms_import_mappings')->where(['source_system' => 'tswms', 'source_client_id' => $this->sourceClientId, 'source_table' => $table, 'source_id' => $id, 'target_entity' => $entity])->value('target_id');
    }

    private function remember(string $table, string $id, string $entity, string $target, $row): void
    {
        DB::table('wms.tswms_import_mappings')->updateOrInsert(['source_system' => 'tswms', 'source_client_id' => $this->sourceClientId, 'source_table' => $table, 'source_id' => $id, 'target_entity' => $entity], ['target_id' => $target, 'source_database' => $this->sourceDatabase, 'source_hash' => $this->sourceHash($row), 'updated_at' => now(), 'created_at' => now()]);
    }

    private function sourceHash(object $row): string
    {
        $data = (array) $row;
        unset($data['created_at'],$data['updated_at']);

        return hash('sha256', 'v9|'.json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}
