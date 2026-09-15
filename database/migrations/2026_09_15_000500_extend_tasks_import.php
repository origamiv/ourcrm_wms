<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up(): void {
  Schema::create('wms.task_stages', function(Blueprint $t){$t->id();$t->string('name');$t->string('shortname')->nullable();$t->smallInteger('status')->default(1);$t->string('icon')->nullable();$t->string('tenant_id')->nullable()->index();$t->jsonb('src')->nullable();$t->timestamps();$t->softDeletes();});
  Schema::create('wms.users', function(Blueprint $t){$t->id();$t->string('code')->nullable();$t->string('name')->nullable();$t->string('shortname')->nullable();$t->string('first_name')->nullable();$t->string('last_name')->nullable();$t->string('patronymic')->nullable();$t->string('email')->nullable();$t->string('phone')->nullable();$t->string('login')->nullable();$t->smallInteger('status')->default(1);$t->jsonb('src')->nullable();$t->string('tenant_id')->nullable()->index();$t->timestamps();$t->softDeletes();});
  Schema::table('wms.tasks', function(Blueprint $t){$t->unsignedBigInteger('task_stage_id')->nullable()->after('status_id');});
  Schema::create('wms.task_goods', function(Blueprint $t){$t->id();$t->unsignedBigInteger('task_id');$t->unsignedBigInteger('good_id')->nullable();$t->integer('shipment_box_number')->nullable();$t->integer('count_plan')->nullable();$t->integer('count_fact')->nullable();$t->text('good_comment')->nullable();$t->string('good_current_barcode')->nullable();$t->decimal('unit_price',18,4)->nullable();$t->integer('unit_price_currency_id')->nullable();$t->jsonb('src')->nullable();$t->string('tenant_id')->nullable()->index();$t->timestamps();$t->softDeletes();});
  DB::statement("CREATE INDEX wms_task_goods_task_idx ON wms.task_goods(task_id)");
 }
 public function down(): void { Schema::dropIfExists('wms.task_goods'); Schema::table('wms.tasks',fn(Blueprint $t)=>$t->dropColumn('task_stage_id')); Schema::dropIfExists('wms.users'); Schema::dropIfExists('wms.task_stages'); }
};
