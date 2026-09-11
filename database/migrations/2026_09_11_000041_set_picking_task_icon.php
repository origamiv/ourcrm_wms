<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void { DB::table("wms.task_types")->where("shortname", "picking")->update(["icon" => "/design/task-types/picking.png"]); }
    public function down(): void {}
};
