<?php

declare(strict_types=1);

namespace App\Console;

use App\Jobs\ImportRunCoordinatorJob;
use App\Models\CellGood;
use App\Models\ImportRun;
use App\Models\ImportRunStage;
use App\Services\EntityChangeRecorder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PDO;
use RuntimeException;
use Throwable;

final class ImportTswmsCommand extends Command
{
    protected $signature = 'wms:import:tswms {--tenant= : Тенант WMS} {--tswms-client-id= : ID клиента TSWMS} {--dry-run} {--only=*} {--entity-groups= : Группы сущностей (references,goods,orders,tasks)} {--with-dependencies : Автоматически добавить зависимые сущности} {--inline : Выполнить этап непосредственно внутри job} {--count-only : Вернуть количество строк task_goods} {--goods-count-only : Вернуть количество строк товаров} {--goods-offset=0 : Смещение строк товаров} {--goods-limit=0 : Ограичение строк товаров} {--task-goods-offset=0 : Смещение строк task_goods} {--task-goods-limit=0 : Ограничение строк task_goods}';

    protected $description = 'Импортирует данные выбранного клиента TSWMS в тенант WMS';

    private const ENTITY_GROUPS = [
        'references' => ['clients', 'services', 'warehouses', 'task_stages', 'users', 'accounts', 'webhooks', 'documents'],
        'goods' => ['goods'],
        'orders' => ['orders', 'order_goods', 'order_histories', 'shipments', 'order_statuses', 'order_sources', 'order_cancel_statuses', 'logistic_companies', 'shipment_statuses'],
        'tasks' => ['tasks', 'task_goods', 'acceptances', 'cell_goods'],
    ];

    /**
     * Карта зависимостей между сущностями
     */
    private const ENTITY_DEPENDENCIES = [
        'accounts' => ['clients'],
        'webhooks' => ['clients', 'accounts'], 
        'documents' => ['clients'],
        'orders' => ['clients', 'warehouses', 'webhooks', 'services'],
        'order_goods' => ['orders', 'goods'],
        'order_histories' => ['orders'],
        'shipments' => ['orders'],
        'tasks' => ['clients', 'warehouses', 'task_stages', 'users'],
        'task_goods' => ['tasks', 'goods'],
        'acceptances' => ['tasks', 'clients', 'warehouses'],
        'cell_goods' => ['tasks', 'goods', 'warehouses'],
    ];

    private string $tenant;

    private int $sourceClientId;

    private string $sourceDatabase = '';

    private $source;

    private $billing;

    private array $maps = [];

    private int $created = 0;

    private int $updated = 0;

    private int $sourceRecords = 0;

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
            $only = $this->resolveEntitiesFromOptions();
            $activeQuery = ImportRun::query()
                ->where('tenant_id', $this->tenant)
                ->whereIn('status', ['queued', 'running']);
            $sourceClientId = (int) ($this->option('tswms-client-id') ?: 0);
            if ($sourceClientId > 0) {
                $activeQuery->where('source_client_id', $sourceClientId);
            }
            $active = $activeQuery->exists();
            $importData = [
                'tenant_id' => $this->tenant,
                'project' => 'tswms',
                'name' => $only === [] ? 'Полный импорт' : implode(', ', $only),
                'source_client_id' => (int) ($this->option('tswms-client-id') ?: 0) ?: null,
                'status' => $active ? 'failed' : 'queued',
                'total_stages' => $only === [] ? count(ImportRun::STAGE_NAMES) : count($only),
                'total_chunks' => 0,
                'options' => [
                    'only' => $only,
                    'dry_run' => (bool) $this->option('dry-run'),
                ],
                'started_at' => now(),
            ];
            if ($active) {
                $importData['error_class'] = RuntimeException::class;
                $importData['error_message'] = 'Импорт не запущен: для этого тенанта уже выполняется другой импорт TSWMS.';
                $importData['finished_at'] = now();
                ImportRun::query()->create($importData);
                $this->components->error('Импорт не запущен: для этого тенанта уже выполняется другой импорт TSWMS.');

                return self::FAILURE;
            }
            $import = ImportRun::query()->create($importData);
            $stageKeys = $only !== [] ? array_values(array_intersect(array_keys(ImportRun::STAGE_NAMES), $only)) : array_keys(ImportRun::STAGE_NAMES);
            foreach ($stageKeys as $number => $stageKey) {
                ImportRunStage::query()->create([
                    'import_run_id' => $import->id,
                    'stage_number' => $number + 1,
                    'stage_key' => $stageKey,
                    'name' => ImportRun::STAGE_NAMES[$stageKey],
                    'total_chunks' => $stageKey === 'task_goods' ? 0 : 1,
                ]);
            }
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
        if ($this->option('goods-count-only')) {
            $count = $this->source->getSchemaBuilder()->hasTable('tswms-goods')
                ? $this->source->table('tswms-goods')->count()
                : 0;
            $this->line('TSWMS_GOODS_COUNT:'.$count);

            return self::SUCCESS;
        }
        if ($this->option('count-only')) {
            $count = $this->source->getSchemaBuilder()->hasTable('tswms-tasks-goods')
                ? $this->source->table('tswms-tasks-goods')->count()
                : 0;
            $this->line('TSWMS_TASK_GOODS_COUNT:'.$count);

            return self::SUCCESS;
        }
        $only = $this->resolveEntitiesFromOptions();
        
        // Автоматическое добавление зависимостей, если включена опция
        if ($this->option('with-dependencies')) {
            $only = $this->addDependencies($only);
        }
        
        // Валидация зависимостей между сущностями
        $this->validateDependencies($only);
        
        // Уровень 1: Базовые справочники (без зависимостей)
        $this->runStep('clients', fn () => $this->importPartners(), $only);
        $this->runStep('services', fn () => $this->importServices(), $only);
        $this->runStep('warehouses', fn () => $this->importWarehouses(), $only);
        $this->runStep('task_stages', fn () => $this->importTaskStages(), $only);
        $this->runStep('users', fn () => $this->importImportedUsers(), $only);
        $this->runStep('goods', fn () => $this->importGoods(), $only);
        
        // Уровень 2: Интеграционные сущности (зависят от clients)
        $this->runStep('accounts', fn () => $this->importAccounts(), $only);
        $this->runStep('webhooks', fn () => $this->importWebhooks(), $only);
        $this->runStep('documents', fn () => $this->importDocuments(), $only);
        // Уровень 4: Операционные данные
        $this->runStep('tasks', fn () => $this->importTasks(), $only);
        // Уровень 3: Справочники заказов (создаются через orders)
        $this->runStep('order_statuses', fn () => $this->importOrderReference('tswms-orders-status', 'wms.order_statuses'), $only);
        $this->runStep('order_sources', fn () => $this->importOrderReference('tswms-orders-source', 'wms.order_sources'), $only);
        $this->runStep('order_cancel_statuses', fn () => $this->importOrderReference('directories_order_cancel_statuses', 'wms.order_cancel_statuses'), $only);
        $this->runStep('logistic_companies', fn () => $this->importOrderReference('logistic_companies', 'wms.logistic_companies'), $only);
        $this->runStep('shipment_statuses', fn () => $this->importOrderReference('tswms-shipments-statuses', 'wms.shipment_statuses'), $only);
        
        // Продолжение уровня 4: Операционные данные
        $this->runStep('orders', fn () => $this->importOrders(), $only);
        
        // Уровень 5: Связанные данные
        $this->runStep('order_goods', fn () => $this->importOrderGoods(), $only);
        $this->runStep('order_histories', fn () => $this->importOrderHistories(), $only);
        $this->runStep('shipments', fn () => $this->importShipments(), $only);
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
        $this->sourceRecords = 0;
        $this->components->task($name, function () use ($callback): void {
            if (! $this->option('dry-run')) {
                $callback();
            }
        });
        $this->line('IMPORT_SOURCE_RECORDS:'.$this->sourceRecords);
    }

    private function importPartners(): void
    {
        foreach ($this->source->table('tswms-partners')->get() as $row) {
            $partnerId = (string) $row->id;
            $data = ['name' => (string) ($row->name ?? 'Партнёр #'.$row->id), 'shortname' => 'tswms_partner_'.$row->id, 'code' => $partnerId, 'status' => (int) ($row->active ?? 1), 'tenant_id' => $this->tenant, 'src' => json_encode(['source_system' => 'tswms', 'source_id' => $partnerId, 'source_fields' => (array) $row], JSON_UNESCAPED_UNICODE), 'updated_at' => now(), 'created_at' => now()];
            $this->upsert('clients.clients', $data, 'tswms-partners', $partnerId, 'App\\Models\\Client');
            $clientId = $this->mapped('tswms-partners', $partnerId, 'App\\Models\\Client');
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
        $query = $this->source->table('tswms-goods')->orderBy('id');
        $limit = max(0, (int) $this->option('goods-limit'));
        if ($limit > 0) {
            $query->offset(max(0, (int) $this->option('goods-offset')))->limit($limit);
        }
        $rows = $query->get();
        $this->sourceRecords = $rows->count();
        $sourceIds = $rows->map(fn (object $row): string => (string) $row->id)->all();
        $mappings = DB::table('wms.tswms_import_mappings')
            ->where('source_system', 'tswms')
            ->where('source_client_id', $this->sourceClientId)
            ->where('source_table', 'tswms-goods')
            ->where('target_entity', 'App\\Models\\Good')
            ->whereIn('source_id', $sourceIds)
            ->get()
            ->keyBy('source_id');
        $inserts = [];
        $updates = [];
        $mappingRows = [];
        foreach ($rows as $row) {
            $id = (string) $row->id;
            $barcodes = array_values(array_unique(array_filter(array_merge([(string) ($row->{'barcode-good'} ?? '')], $labels[$id]['barcodes'] ?? []))));
            $articles = array_values(array_unique(array_filter(array_merge([(string) ($row->article ?? '')], $labels[$id]['articles'] ?? []))));
            $sourceData = (array) $row;
            $data = ['code' => $id, 'name' => (string) ($row->name ?? 'Товар #'.$id), 'shortname' => 'tswms_good_'.$id, 'barcodes' => json_encode($barcodes), 'articul' => json_encode($articles), 'status' => (int) ($row->active ?? 1), 'tenant_id' => $this->tenant, 'is_from_external' => 1, 'src' => json_encode(['source_system' => 'tswms', 'source_id' => $id, 'partner_id' => $row->partner ?? null, 'source_fields' => $sourceData], JSON_UNESCAPED_UNICODE), 'created_at' => now(), 'updated_at' => now()];
            $mapping = $mappings->get($id);
            $hash = $this->sourceHash((object) $data);
            if ($mapping && (string) $mapping->source_hash === $hash) {
                continue;
            }
            if ($mapping?->target_id) {
                $updates[] = ['id' => (int) $mapping->target_id, ...$data];
            } else {
                $inserts[] = $data;
            }
            $mappingRows[$id] = ['source_system' => 'tswms', 'source_client_id' => $this->sourceClientId, 'source_table' => 'tswms-goods', 'source_id' => $id, 'target_entity' => 'App\\Models\\Good', 'target_id' => $mapping?->target_id, 'source_database' => $this->sourceDatabase, 'source_hash' => $hash, 'created_at' => now(), 'updated_at' => now()];
        }
        if ($inserts === [] && $updates === []) {
            return;
        }
        DB::transaction(function () use (&$inserts, $updates, &$mappingRows): void {
            if ($inserts !== []) {
                DB::table('goods.goods')->insert($inserts);
                $goods = DB::table('goods.goods')->where('tenant_id', $this->tenant)->whereIn('shortname', array_column($inserts, 'shortname'))->pluck('id', 'shortname');
                foreach ($inserts as $data) {
                    $sourceId = (string) preg_replace('/^tswms_good_/', '', (string) $data['shortname']);
                    $targetId = $goods[$data['shortname']] ?? null;
                    if ($targetId === null) {
                        throw new RuntimeException('Не удалось определить ID созданного товара '.$sourceId.'.');
                    }
                    $mappingRows[$sourceId]['target_id'] = $targetId;
                }
            }
            if ($updates !== []) {
                $columns = array_values(array_diff(array_keys($updates[0]), ['id']));
                DB::table('goods.goods')->upsert($updates, ['id'], $columns);
            }
            $recorder = app(EntityChangeRecorder::class);
            if (! $recorder->hasDatabaseTrigger('goods.goods')) {
                $recorder->publishMany('App\\Models\\Good', array_values(array_filter(array_column($mappingRows, 'target_id'))));
            }
            DB::table('wms.tswms_import_mappings')->upsert(array_values($mappingRows), ['source_system', 'source_client_id', 'source_table', 'source_id', 'target_entity'], ['target_id', 'source_database', 'source_hash', 'updated_at']);
        });
        $this->created += count($inserts);
        $this->updated += count($updates);
    }

    private function importWarehouses(): void
    {
        $kindMap = [];
        if ($this->source->getSchemaBuilder()->hasTable('warehouse_types')) {
            foreach ($this->source->table('warehouse_types')->get() as $type) {
                $kind = DB::table('wms.kind_warehouses')->where('tenant_id', $this->tenant)->where('name', (string) $type->name)->first();
                $kindId = $kind?->id ?: $this->insertAndPublish('wms.kind_warehouses', ['name' => (string) $type->name, 'shortname' => 'tswms_kind_'.$type->id, 'status' => 1, 'tenant_id' => $this->tenant, 'created_at' => now(), 'updated_at' => now()], 'App\\Models\\KindWarehouse');
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
        $warehouseId = $warehouseMap ? (int) reset($warehouseMap) : (int) (DB::table('wms.warehouses')->where('tenant_id', $this->tenant)->orderBy('id')->value('id') ?: $this->insertAndPublish('wms.warehouses', ['name' => 'Основной', 'shortname' => 'tswms_main_'.$this->sourceClientId, 'status' => 1, 'tenant_id' => $this->tenant, 'created_at' => now(), 'updated_at' => now()], 'App\\Models\\Warehouse'));
        $zoneId = DB::table('wms.zones')->where('tenant_id', $this->tenant)->orderBy('id')->value('id') ?: $this->insertAndPublish('wms.zones', ['name' => 'Основная', 'shortname' => 'tswms_main_'.$this->sourceClientId, 'status' => 1, 'tenant_id' => $this->tenant, 'created_at' => now(), 'updated_at' => now()], 'App\\Models\\Zone');
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
            $credentials = ['key1' => $r->key1 ?? null, 'key2' => $r->key2 ?? null, 'key3' => $r->key3 ?? null];
            $credentials = $this->existingAccountCredentials($table, (string) $r->id, $credentials);
            $credentials = $this->enrichYandexCredentials($type, (string) ($r->token ?? $r->key1 ?? ''), $credentials);
            $src = ['source_system' => 'tswms', 'source_table' => $table, 'source_id' => (string) $r->id, 'source_fields' => (array) $r, 'credentials' => $credentials];
            $serviceId = $this->clientServiceId($type);
            $data = ['name' => $name, 'shortname' => $this->latinShortname($type.'_'.$r->id), 'host' => $r->host ?? null, 'login' => $r->login ?? null, 'pass' => $r->pass ?? null, 'token' => $r->token ?? ($r->key1 ?? null), 'descr' => 'Доступ TSWMS: '.$type, 'status' => (int) ($r->active ?? $r->is_active ?? 1), 'src' => json_encode($src, JSON_UNESCAPED_UNICODE), 'client_id' => (int) $client, 'service_id' => $serviceId, 'tenant_id' => $this->tenant, 'created_at' => now(), 'updated_at' => now()];
            $this->upsert('clients.accounts', $data, $table, (string) $r->id, 'App\\Models\\ClientAccount');
        }
    }

    private function clientServiceId(string $type): int
    {
        [$shortname, $name] = $this->clientServiceDefinition($type);

        $serviceId = DB::table('clients.services')
            ->where('shortname', $shortname)
            ->whereNull('deleted_at')
            ->where(fn ($query) => $query->whereNull('tenant_id')->orWhere('tenant_id', $this->tenant))
            ->orderByRaw('tenant_id IS NOT NULL')
            ->value('id');

        if ($serviceId !== null) {
            return (int) $serviceId;
        }

        return $this->insertAndPublish('clients.services', [
            'name' => $name,
            'shortname' => $shortname,
            'status' => 1,
            'tenant_id' => $this->tenant,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'App\\Models\\ClientService');
    }

    private function existingAccountCredentials(string $sourceTable, string $sourceId, array $credentials): array
    {
        $targetId = $this->mapped($sourceTable, $sourceId, 'App\\Models\\ClientAccount');
        if (! $targetId) {
            return $credentials;
        }

        $src = DB::table('clients.accounts')->where('id', $targetId)->value('src');
        $existing = is_string($src) ? json_decode($src, true) : (array) $src;

        return array_merge((array) ($existing['credentials'] ?? []), $credentials);
    }

    private function enrichYandexCredentials(string $type, string $token, array $credentials): array
    {
        if (! str_contains(mb_strtolower(trim($type)), 'yandex') || trim($token) === '') {
            return $credentials;
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders(['Api-Key' => $token])
                ->connectTimeout(5)
                ->timeout(15)
                ->get('https://api.partner.market.yandex.ru/v2/campaigns', ['limit' => 100])
                ->throw()
                ->json();

            return $this->credentialsFromYandexCampaigns($credentials, (array) $response);
        } catch (Throwable $exception) {
            $this->components->warn('Не удалось получить business_id Яндекс Маркета: '.$exception->getMessage());

            return $credentials;
        }
    }

    private function credentialsFromYandexCampaigns(array $credentials, array $response): array
    {
        $campaigns = (array) ($response['campaigns'] ?? $response['result']['campaigns'] ?? []);
        foreach ($campaigns as $campaign) {
            $campaign = (array) $campaign;
            $businessId = trim((string) (($campaign['business']['id'] ?? null) ?: ($campaign['business_id'] ?? '')));
            if ($businessId === '') {
                continue;
            }

            $credentials['business_id'] = $businessId;
            $campaignId = trim((string) ($campaign['id'] ?? $campaign['campaign_id'] ?? ''));
            if ($campaignId !== '') {
                $credentials['campaign_id'] = $campaignId;
            }
            break;
        }

        return $credentials;
    }

    /** @return array{string, string} */
    private function clientServiceDefinition(string $type): array
    {
        $normalized = mb_strtolower(trim($type));

        return match (true) {
            $normalized === 'wb', str_contains($normalized, 'wildberries') => ['wildberries', 'Wildberries'],
            str_contains($normalized, 'ozon') => ['ozon', 'Ozon'],
            str_contains($normalized, 'yandex'), $normalized === 'ym' => ['yandex_market', 'Yandex.Market'],
            default => [$this->latinShortname($normalized ?: 'integration'), trim($type) ?: 'Интеграция'],
        };
    }

    private function importWebhooks(): void
    {
        $rows = [];
        if ($this->billing && $this->billing->getSchemaBuilder()->hasTable('legasy_integrations_registry')) {
            $rows = array_merge($rows, array_map(fn ($r) => [$r, 'legasy_integrations_registry'], $this->billing->table('legasy_integrations_registry')->where('client_id', $this->sourceClientId)->where('is_deleted', 0)->get()->all()));
        } if ($this->source->getSchemaBuilder()->hasTable('tswms-integrations')) {
            $rows = array_merge($rows, array_map(fn ($r) => [$r, 'tswms-integrations'], $this->source->table('tswms-integrations')->get()->all()));
        } foreach ($rows as [$r,$table]) {
            $partner = (string) ($r->{'partner-id'} ?? $r->partner_id ?? $r->client_id ?? '');
            $client = $this->mapped('tswms-partners', $partner, 'App\\Models\\Client');
            $type = mb_strtolower((string) ($r->type ?? 'integration'));
            $sourceId = (string) $r->id;
            $account = $this->mapped($table, $sourceId, 'App\\Models\\ClientAccount');
            if (! $account) {
                $account = $this->mapped('tswms-integrations', $sourceId, 'App\\Models\\ClientAccount');
            }$service = $this->localIntegrationService($type);
            $incoming = in_array(true, [(bool) ($r->goods_active ?? $r->{'goods-active'} ?? false), (bool) ($r->fbs_active ?? $r->{'fbs-active'} ?? false)], true);
            $params = ['account_id' => $account ? (int) $account : null, 'source' => ['table' => $table, 'id' => $sourceId, 'type' => $type], 'warehouse_id' => $r->warehouse_id ?? null, 'settings' => $r->config_json ?? $r->{'config-json'} ?? null, 'source_fields' => (array) $r];
            $data = ['name' => (string) ($r->name ?? $type), 'shortname' => $this->latinShortname($type.'_'.$sourceId), 'client_id' => $client ? (int) $client : null, 'service_id' => $service, 'type_hook_id' => 2, 'rules_id' => null, 'params' => json_encode($params, JSON_UNESCAPED_UNICODE), 'status' => (int) ($r->active ?? $r->is_active ?? 1), 'cnt' => 0, 'dat_last_run' => $r->last_check_at ?? $r->{'last-check'} ?? null, 'tenant_id' => $this->tenant, 'created_at' => now(), 'updated_at' => now()];
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

        return $this->insertAndPublish('integration.services', ['name' => $name, 'shortname' => $this->latinShortname($name), 'status' => 1, 'tenant_id' => $this->tenant, 'created_at' => now(), 'updated_at' => now()], 'App\\Models\\IntegrationService');
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

    private function importOrderReference(string $sourceTable, string $targetTable): void
    {
        if (! $this->source->getSchemaBuilder()->hasTable($sourceTable)) {
            return;
        }
        foreach ($this->source->table($sourceTable)->get() as $row) {
            $id = (string) $row->id;
            $name = trim((string) ($row->name ?? $row->title ?? 'Запись #'.$id));
            $this->upsert($targetTable, [
                'name' => $name ?: 'Запись #'.$id,
                'shortname' => $this->latinShortname($name.'_'.$id),
                'code' => $id,
                'status' => (int) ($row->active ?? 1),
                'tenant_id' => $this->tenant,
                'src' => json_encode(['source_system' => 'tswms', 'source_id' => $id, 'source_fields' => (array) $row], JSON_UNESCAPED_UNICODE),
                'created_at' => now(), 'updated_at' => now(),
            ], $sourceTable, $id, 'App\\Models\\'.match ($targetTable) {
                'wms.order_statuses' => 'OrderStatus', 'wms.order_sources' => 'OrderSource', 'wms.order_cancel_statuses' => 'OrderCancelStatus', 'wms.logistic_companies' => 'LogisticCompany', default => 'ShipmentStatus',
            });
        }
    }

    private function importOrders(): void
    {
        if (! $this->source->getSchemaBuilder()->hasTable('tswms-orders')) {
            return;
        }
        $clients = $this->sourceMappings('tswms-partners', 'App\\Models\\Client');
        $warehouses = $this->sourceMappings('warehouses', 'App\\Models\\Warehouse');
        $integrations = $this->sourceMappings('tswms-integrations', 'App\\Models\\IntegrationWebhook');
        $statuses = DB::table('wms.order_statuses')->where('tenant_id', $this->tenant)->pluck('id', 'code')->all();
        $sources = DB::table('wms.order_sources')->where('tenant_id', $this->tenant)->pluck('id', 'code')->all();
        $cancels = DB::table('wms.order_cancel_statuses')->where('tenant_id', $this->tenant)->pluck('id', 'code')->all();
        $services = DB::table('wms.delivery_services')->where('tenant_id', $this->tenant)->get(['id', 'shortname'])->all();
        $serviceMap = [];
        foreach ($services as $service) {
            $serviceMap[(string) preg_replace('/^tswms_service_/', '', (string) $service->shortname)] = $service->id;
        }
        $this->source->table('tswms-orders')->orderBy('id')->chunk(1000, function ($rows) use ($clients, $warehouses, $integrations, $statuses, $sources, $cancels, $serviceMap): void {
            $batch = [];
            foreach ($rows as $row) {
                $id = (string) $row->id;
                $statusId = (string) ($row->{'status-id'} ?? $row->status_id ?? '');
                $sourceId = (string) ($row->{'source-id'} ?? $row->source_id ?? '');
                $cancelId = (string) ($row->cancel_status_id ?? '');
                $serviceId = (string) ($row->delivery_id ?? '');
                $data = [
                    'code' => $id, 'number' => $row->number ?? null, 'client_id' => $clients[(string) ($row->{'partner-id'} ?? $row->partner_id ?? '')] ?? null, 'warehouse_id' => $warehouses[(string) ($row->warehouse_id ?? '')] ?? null,
                    'delivery_service_id' => $serviceMap[$serviceId] ?? null, 'order_status_id' => $statuses[$statusId] ?? null, 'order_source_id' => $sources[$sourceId] ?? null, 'order_cancel_status_id' => $cancels[$cancelId] ?? null, 'integration_id' => $integrations[(string) ($row->{'integration-id'} ?? $row->integration_id ?? '')] ?? null,
                    'delivery_track' => $row->{'delivery-track'} ?? $row->delivery_track ?? null, 'delivery_date' => $row->{'delivery-date'} ?? $row->delivery_date ?? null, 'created_date' => $row->{'created-date'} ?? $row->created_date ?? null,
                    'currency' => $row->currency ?? null, 'goods_total_price' => $row->{'goods-total-price'} ?? $row->goods_total_price ?? null, 'goods_count' => $row->{'goods-count'} ?? $row->goods_count ?? null,
                    'comment_partner' => $row->{'comment-partner'} ?? $row->comment_partner ?? null, 'comment_internal' => $row->{'comment-internal'} ?? $row->comment_internal ?? null, 'custom' => json_encode($row->custom ?? null, JSON_UNESCAPED_UNICODE),
                    'need_imei' => (bool) ($row->{'need-imei'} ?? $row->need_imei ?? false), 'need_uin' => (bool) ($row->{'need-uin'} ?? $row->need_uin ?? false), 'need_gtin' => (bool) ($row->{'need-gtin'} ?? $row->need_gtin ?? false), 'need_sgtin' => (bool) ($row->{'need-sgtin'} ?? $row->need_sgtin ?? false), 'need_expiration' => (bool) ($row->need_expiration ?? false), 'need_gtd' => (bool) ($row->{'need-gtd'} ?? false), 'is_b2b' => (bool) ($row->{'is-b2b'} ?? false), 'is_crossborder' => (bool) ($row->is_crossborder ?? false), 'wb_supply_id' => $row->{'wb-supply-id'} ?? null,
                    'crm_party_id' => $row->crm_party_id ?? null, 'crm_party_address_id' => $row->crm_party_address_id ?? null, 'tenant_id' => $this->tenant, 'src' => json_encode(['source_system' => 'tswms', 'source_id' => $id, 'source_fields' => (array) $row], JSON_UNESCAPED_UNICODE), 'created_at' => now(), 'updated_at' => now(),
                ];
                $batch[] = $data;
                $this->sourceRecords++;
            }
            if ($batch === []) {
                return;
            }
            $this->bulkUpsert('wms.orders', $batch, 'tswms-orders', 'App\\Models\\Order');
        });
    }

    private function importOrderGoods(): void
    {
        if (! $this->source->getSchemaBuilder()->hasTable('tswms-orders-goods')) {
            return;
        }
        $orders = $this->sourceMappings('tswms-orders', 'App\\Models\\Order');
        $goods = $this->sourceMappings('tswms-goods', 'App\\Models\\Good');
        $this->source->table('tswms-orders-goods')->orderBy('id')->chunk(1000, function ($rows) use ($orders, $goods): void {
            $batch = [];
            foreach ($rows as $row) {
                $order = $orders[(string) ($row->{'order-id'} ?? $row->order_id ?? '')] ?? null;
                if (! $order) {
                    continue;
                }
                $id = (string) $row->id;
                $batch[] = ['order_id' => $order, 'good_id' => $goods[(string) ($row->{'good-id'} ?? $row->good_id ?? '')] ?? null, 'code' => $id, 'count' => (int) ($row->count ?? 0), 'price' => $row->price ?? null, 'barcode_from_integration' => $row->{'barcode-from-integration'} ?? null, 'need_marking' => (bool) ($row->{'need-marking'} ?? false), 'tenant_id' => $this->tenant, 'src' => json_encode(['source_system' => 'tswms', 'source_id' => $id, 'source_fields' => (array) $row], JSON_UNESCAPED_UNICODE), 'created_at' => now(), 'updated_at' => now()];
                $this->sourceRecords++;
            }
            $this->bulkUpsert('wms.order_goods', $batch, 'tswms-orders-goods', 'App\\Models\\OrderGood');
        });
    }

    private function importOrderHistories(): void
    {
        if (! $this->source->getSchemaBuilder()->hasTable('tswms-orders-history')) {
            return;
        }
        $orders = $this->sourceMappings('tswms-orders', 'App\\Models\\Order');
        $users = $this->sourceMappings('users', 'App\\Models\\ImportedUser');
        $this->source->table('tswms-orders-history')->orderBy('id')->chunk(1000, function ($rows) use ($orders, $users): void {
            $batch = [];
            foreach ($rows as $row) {
                $order = $orders[(string) ($row->{'order-id'} ?? $row->order_id ?? '')] ?? null;
                if (! $order) {
                    continue;
                }
                $id = (string) $row->id;
                $batch[] = ['order_id' => $order, 'code' => $id, 'action' => $row->action ?? null, 'good_code' => $row->{'good-id'} ?? null, 'value_old' => $row->{'value-old'} ?? null, 'value_new' => $row->{'value-new'} ?? null, 'event_date' => $row->date ?? null, 'user_id' => $users[(string) ($row->{'user-id'} ?? '')] ?? null, 'tenant_id' => $this->tenant, 'src' => json_encode(['source_system' => 'tswms', 'source_id' => $id, 'source_fields' => (array) $row], JSON_UNESCAPED_UNICODE), 'created_at' => now(), 'updated_at' => now()];
                $this->sourceRecords++;
            }
            $this->bulkUpsert('wms.order_histories', $batch, 'tswms-orders-history', 'App\\Models\\OrderHistory');
        });
    }

    private function importShipments(): void
    {
        if (! $this->source->getSchemaBuilder()->hasTable('tswms-shipments')) {
            return;
        }
        $clients = $this->sourceMappings('tswms-partners', 'App\\Models\\Client');
        $warehouses = $this->sourceMappings('warehouses', 'App\\Models\\Warehouse');
        $orders = $this->sourceMappings('tswms-orders', 'App\\Models\\Order');
        $statuses = DB::table('wms.shipment_statuses')->where('tenant_id', $this->tenant)->pluck('id', 'code')->all();
        $this->source->table('tswms-shipments')->orderBy('id')->chunk(1000, function ($rows) use ($clients, $warehouses, $orders, $statuses): void {
            $batch = [];
            foreach ($rows as $row) {
                $id = (string) $row->id;
                $statusId = (string) ($row->{'status-id'} ?? $row->status_id ?? '');
                $sourceOrderId = $row->order_id ?? null;
                $batch[] = ['code' => $id, 'order_id' => $sourceOrderId ? ($orders[(string) $sourceOrderId] ?? null) : null, 'client_id' => $clients[(string) ($row->{'partner-id'} ?? $row->partner_id ?? '')] ?? null, 'warehouse_id' => $warehouses[(string) ($row->warehouse_id ?? '')] ?? null, 'shipment_status_id' => $statuses[$statusId] ?? null, 'created_date' => $row->{'date-created'} ?? null, 'checked_at' => $row->{'date-checked'} ?? null, 'sent_at' => $row->{'date-send'} ?? null, 'status' => 1, 'tenant_id' => $this->tenant, 'src' => json_encode(['source_system' => 'tswms', 'source_id' => $id, 'source_fields' => (array) $row], JSON_UNESCAPED_UNICODE), 'created_at' => now(), 'updated_at' => now()];
                $this->sourceRecords++;
            }
            $this->bulkUpsert('wms.shipments', $batch, 'tswms-shipments', 'App\\Models\\Shipment');
        });
    }

    private function localOrderReference(string $targetTable, string $sourceTable, mixed $sourceId, string $entity): ?int
    {
        if ($sourceId === null || $sourceId === '') {
            return null;
        }

        return ($this->mapped($sourceTable, (string) $sourceId, $entity) ?: DB::table($targetTable)->where('tenant_id', $this->tenant)->where('code', (string) $sourceId)->value('id')) ? (int) ($this->mapped($sourceTable, (string) $sourceId, $entity) ?: DB::table($targetTable)->where('tenant_id', $this->tenant)->where('code', (string) $sourceId)->value('id')) : null;
    }

    private function localDeliveryService(mixed $sourceId): ?int
    {
        if ($sourceId === null || $sourceId === '') {
            return null;
        }

        return DB::table('wms.delivery_services')->where('tenant_id', $this->tenant)->where('code', (string) $sourceId)->value('id') ?: DB::table('wms.delivery_services')->where('tenant_id', $this->tenant)->where('shortname', 'tswms_service_'.$sourceId)->value('id');
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
            $changed = DB::table('wms.cell_goods')
                ->where('id', $mapping->target_id)
                ->where('tenant_id', $this->tenant)
                ->whereNull('deleted_at')
                ->update(['cnt' => 0, 'leave_at' => now(), 'deleted_at' => now(), 'updated_at' => now()]);
            $recorder = app(EntityChangeRecorder::class);
            if ($changed > 0 && ! $recorder->hasDatabaseTrigger('wms.cell_goods')) {
                $recorder->publishCurrent(CellGood::class, $mapping->target_id);
            }
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
        $this->sourceRecords++;
        DB::transaction(function () use ($table, $data, $sourceTable, $sourceId, $entity): void {
            $key = implode('|', ['tswms', $this->sourceClientId, $sourceTable, $sourceId, $entity]);
            DB::selectOne('select pg_advisory_xact_lock(hashtextextended(?, 0))', [$key]);
            $mapping = DB::table('wms.tswms_import_mappings')
                ->where(['source_system' => 'tswms', 'source_client_id' => $this->sourceClientId, 'source_table' => $sourceTable, 'source_id' => $sourceId, 'target_entity' => $entity])
                ->lockForUpdate()
                ->first();
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
            $recorder = app(EntityChangeRecorder::class);
            if (! $recorder->hasDatabaseTrigger($table)) {
                $recorder->publishCurrent($entity, $target);
            }
            $this->remember($sourceTable, $sourceId, $entity, $target, $row);
        });
    }

    private function bulkUpsert(string $table, array $batch, string $sourceTable, string $entity): void
    {
        if ($batch === []) {
            return;
        }
        DB::transaction(function () use ($table, $batch, $sourceTable, $entity): void {
            DB::table($table)->upsert($batch, ['tenant_id', 'code'], array_values(array_diff(array_keys($batch[0]), ['id', 'code', 'created_at'])));
            $ids = DB::table($table)->where('tenant_id', $this->tenant)->whereIn('code', array_column($batch, 'code'))->pluck('id', 'code');
            $recorder = app(EntityChangeRecorder::class);
            if (! $recorder->hasDatabaseTrigger($table)) {
                $recorder->publishMany($entity, $ids->values());
            }
            $maps = [];
            foreach ($batch as $data) {
                $maps[] = ['source_system' => 'tswms', 'source_client_id' => $this->sourceClientId, 'source_database' => $this->sourceDatabase, 'source_table' => $sourceTable, 'source_id' => $data['code'], 'target_entity' => $entity, 'target_id' => $ids[$data['code']] ?? null, 'source_hash' => $this->sourceHash((object) $data), 'created_at' => now(), 'updated_at' => now()];
            }
            DB::table('wms.tswms_import_mappings')->upsert($maps, ['source_system', 'source_client_id', 'source_table', 'source_id', 'target_entity'], ['target_id', 'source_database', 'source_hash', 'updated_at']);
        });
    }

    private function insertAndPublish(string $table, array $data, string $entity): int
    {
        return DB::transaction(function () use ($table, $data, $entity): int {
            $id = (int) DB::table($table)->insertGetId($data);
            $recorder = app(EntityChangeRecorder::class);
            if (! $recorder->hasDatabaseTrigger($table)) {
                $recorder->publishCurrent($entity, $id);
            }

            return $id;
        });
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

    private function resolveEntitiesFromOptions(): array
    {
        // Если указана опция --entity-groups, используем её
        $entityGroups = (string) ($this->option('entity-groups') ?: '');
        if ($entityGroups !== '') {
            $groups = array_filter(array_map('trim', explode(',', $entityGroups)));
            $entities = [];
            
            foreach ($groups as $group) {
                if (isset(self::ENTITY_GROUPS[$group])) {
                    $entities = array_merge($entities, self::ENTITY_GROUPS[$group]);
                } else {
                    $this->components->warn("Неизвестная группа сущностей: {$group}. Доступные группы: " . implode(', ', array_keys(self::ENTITY_GROUPS)));
                }
            }
            
            return array_unique($entities);
        }

        // Иначе используем старую опцию --only для обратной совместимости
        return array_values(array_filter($this->option('only')));
    }

    /**
     * Добавляет зависимости к списку сущностей
     */
    private function addDependencies(array $entities): array
    {
        $result = $entities;
        $added = [];
        
        foreach ($entities as $entity) {
            $dependencies = self::ENTITY_DEPENDENCIES[$entity] ?? [];
            
            foreach ($dependencies as $dependency) {
                if (!in_array($dependency, $result, true)) {
                    $result[] = $dependency;
                    $added[] = $dependency;
                }
            }
        }
        
        if (!empty($added)) {
            $this->components->info('Автоматически добавлены зависимости: ' . implode(', ', $added));
        }
        
        return $result;
    }

    /**
     * Проверяет зависимости для заданных сущностей и выдает предупреждения
     */
    private function validateDependencies(array $entities): void
    {
        $missingDependencies = [];
        
        foreach ($entities as $entity) {
            $dependencies = self::ENTITY_DEPENDENCIES[$entity] ?? [];
            
            foreach ($dependencies as $dependency) {
                if (!in_array($dependency, $entities, true)) {
                    $missingDependencies[$entity][] = $dependency;
                }
            }
        }
        
        if (!empty($missingDependencies)) {
            $this->components->warn('Обнаружены отсутствующие зависимости:');
            
            foreach ($missingDependencies as $entity => $dependencies) {
                $this->components->warn("  • {$entity} требует: " . implode(', ', $dependencies));
            }
            
            $this->components->warn('Некоторые записи могут быть пропущены из-за отсутствующих связанных данных.');
            $this->components->warn('Рекомендуется запустить импорт зависимостей перед импортом основных сущностей.');
        }
    }
}
