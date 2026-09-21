<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

final class ImportRun extends BaseModel
{
    public const STAGE_NAMES = [
        'clients' => 'Клиенты',
        'accounts' => 'Аккаунты',
        'webhooks' => 'Вебхуки',
        'goods' => 'Товары',
        'warehouses' => 'Склады',
        'services' => 'Сервисы',
        'documents' => 'Документы',
        'task_stages' => 'Этапы задач',
        'users' => 'Пользователи',
        'tasks' => 'Задачи',
        'task_goods' => 'Товары задач',
        'acceptances' => 'Приемки',
        'cell_goods' => 'Размещения',
        'order_statuses' => 'Статусы заказов',
        'order_sources' => 'Источники заказов',
        'order_cancel_statuses' => 'Статусы отмены заказов',
        'logistic_companies' => 'Логистические компании',
        'shipment_statuses' => 'Статусы отправлений',
        'orders' => 'Заказы',
        'order_goods' => 'Позиции заказов',
        'order_histories' => 'История заказов',
        'shipments' => 'Отправления',
    ];

    protected $table = 'wms.import_runs';

    protected $guarded = ['id'];

    protected $casts = [
        'options' => 'array',
        'warnings' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'source_client_id' => 'integer',
        'source_webhook_id' => 'integer',
        'source_account_id' => 'integer',
        'total_stages' => 'integer',
        'completed_stages' => 'integer',
        'total_jobs' => 'integer',
        'completed_jobs' => 'integer',
        'created_count' => 'integer',
        'updated_count' => 'integer',
        'skipped_count' => 'integer',
        'total_records' => 'integer',
        'total_chunks' => 'integer',
        'processed_records' => 'integer',
        'processed_chunks' => 'integer',
    ];

    public function stages(): HasMany
    {
        return $this->hasMany(ImportRunStage::class, 'import_run_id')->orderBy('stage_number');
    }
}
