<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const STAGES = [
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
    ];

    public function up(): void
    {
        Schema::create('wms.import_run_stages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('import_run_id');
            $table->unsignedSmallInteger('stage_number');
            $table->string('stage_key', 64);
            $table->string('name', 255);
            $table->string('status', 20)->default('queued');
            $table->unsignedInteger('total_records')->default(0);
            $table->unsignedInteger('processed_records')->default(0);
            $table->unsignedInteger('total_chunks')->default(0);
            $table->unsignedInteger('processed_chunks')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->index(['import_run_id', 'stage_number']);
            $table->unique(['import_run_id', 'stage_key']);
        });

        if (! Schema::hasTable('wms.import_runs')) {
            return;
        }

        $runs = DB::table('wms.import_runs')->get(['id', 'options', 'status', 'current_stage', 'completed_stages']);
        foreach ($runs as $run) {
            $options = is_string($run->options) ? json_decode($run->options, true) : (array) $run->options;
            $selected = array_values(array_filter($options['only'] ?? []));
            $stageKeys = $selected !== [] ? array_values(array_intersect(array_keys(self::STAGES), $selected)) : array_keys(self::STAGES);
            foreach ($stageKeys as $index => $stageKey) {
                $number = $index + 1;
                $status = $run->status === 'failed' && $stageKey === $run->current_stage
                    ? 'failed'
                    : ($run->status === 'completed' || $number <= (int) $run->completed_stages
                        ? 'completed'
                        : ($stageKey === $run->current_stage ? 'running' : 'queued'));
                DB::table('wms.import_run_stages')->insert([
                    'import_run_id' => $run->id,
                    'stage_number' => $number,
                    'stage_key' => $stageKey,
                    'name' => self::STAGES[$stageKey],
                    'status' => $status,
                    'total_chunks' => 1,
                    'processed_chunks' => $status === 'completed' ? 1 : 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wms.import_run_stages');
    }
};
