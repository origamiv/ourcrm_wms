<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('wms.tasks', function(Blueprint $t): void { $t->dropForeign(['user_id']); $t->dropForeign(['created_by_user_id']); });
  DB::table('wms.tasks')->update(['user_id'=>null,'created_by_user_id'=>null]);
  Schema::table('wms.tasks', function(Blueprint $t): void { $t->foreign('user_id')->references('id')->on('wms.users')->nullOnDelete(); $t->foreign('created_by_user_id')->references('id')->on('wms.users')->nullOnDelete(); });
 }
 public function down(): void {
  Schema::table('wms.tasks', function(Blueprint $t): void { $t->dropForeign(['user_id']); $t->dropForeign(['created_by_user_id']); $t->foreign('user_id')->references('id')->on('public.users')->nullOnDelete(); $t->foreign('created_by_user_id')->references('id')->on('public.users')->nullOnDelete(); });
 }
};
