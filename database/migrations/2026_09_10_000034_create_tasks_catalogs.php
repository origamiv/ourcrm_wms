<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        foreach (['task_types','task_statuses','priorities'] as $table) Schema::create('wms.'.$table, function (Blueprint $t) {
            $t->id(); $t->string('name')->nullable(); $t->string('shortname')->nullable(); $t->smallInteger('status')->default(1); $t->string('icon')->nullable(); $t->string('tenant_id')->nullable()->index(); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('wms.tasks', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('shortname')->nullable(); $t->unsignedBigInteger('client_id')->nullable(); $t->unsignedBigInteger('task_type_id')->nullable(); $t->unsignedBigInteger('status_id')->nullable(); $t->timestamp('planned_at')->nullable(); $t->timestamp('started_at')->nullable(); $t->timestamp('completed_at')->nullable(); $t->text('comment')->nullable(); $t->text('internal_comment')->nullable(); $t->unsignedBigInteger('priority_id')->nullable(); $t->jsonb('src')->nullable(); $t->unsignedBigInteger('order_id')->nullable(); $t->unsignedBigInteger('warehouse_id')->nullable(); $t->integer('fact_count')->nullable(); $t->smallInteger('status')->default(1); $t->string('tenant_id')->nullable()->index(); $t->timestamps(); $t->softDeletes();
            $t->index(['tenant_id','status_id']);
        });
        if (DB::getDriverName() === 'pgsql' && DB::selectOne("select to_regclass('tasks.priorities') as t")?->t) DB::statement("insert into wms.priorities (id,name,shortname,status,icon,tenant_id,created_at,updated_at,deleted_at) select id,name,shortname,coalesce(status,1),icon,tenant_id,created_at,updated_at,deleted_at from tasks.priorities on conflict (id) do nothing");
        DB::table('wms.task_types')->insert([['id'=>1,'name'=>'Приёмка','shortname'=>'receipt','status'=>1],['id'=>2,'name'=>'Размещение','shortname'=>'putaway','status'=>1],['id'=>3,'name'=>'Комплектация','shortname'=>'picking','status'=>1],['id'=>4,'name'=>'Упаковка','shortname'=>'packing','status'=>1],['id'=>5,'name'=>'Отгрузка','shortname'=>'shipping','status'=>1],['id'=>6,'name'=>'Инвентаризация','shortname'=>'inventory','status'=>1],['id'=>7,'name'=>'Перемещение','shortname'=>'transfer','status'=>1]]);
        DB::table('wms.task_statuses')->insert([['id'=>1,'name'=>'Новая','shortname'=>'new','status'=>1],['id'=>2,'name'=>'Запланирована','shortname'=>'planned','status'=>1],['id'=>3,'name'=>'В работе','shortname'=>'in_progress','status'=>1],['id'=>4,'name'=>'Приостановлена','shortname'=>'paused','status'=>1],['id'=>5,'name'=>'Завершена','shortname'=>'completed','status'=>1],['id'=>6,'name'=>'Отменена','shortname'=>'cancelled','status'=>1]]);
        $fields = ['task_types'=>['name','shortname','status','icon','tenant_id','created_at','updated_at','deleted_at'],'task_statuses'=>['name','shortname','status','icon','tenant_id','created_at','updated_at','deleted_at'],'priorities'=>['name','shortname','status','icon','tenant_id','created_at','updated_at','deleted_at'],'tasks'=>['name','shortname','client_id','task_type_id','status_id','planned_at','started_at','completed_at','comment','internal_comment','priority_id','src','order_id','warehouse_id','fact_count','status','tenant_id','created_at','updated_at','deleted_at']];
        $models=['task_types'=>'TaskType','task_statuses'=>'TaskStatus','priorities'=>'Priority','tasks'=>'Task']; foreach ($fields as $table=>$cols) DB::statement("create trigger wms_{$table}_change after insert or update or delete on wms.{$table} for each row execute function wms.capture_entity_change('App\\Models\\{$models[$table]}', '".json_encode($cols)."')");
    }
    public function down(): void { foreach (['tasks','priorities','task_statuses','task_types'] as $t) Schema::dropIfExists('wms.'.$t); }
};
