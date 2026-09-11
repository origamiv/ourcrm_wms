<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void { DB::table("wms.task_types")->where("shortname", "shipping")->update(["icon" => "/design/task-types/shipping.png"]); }
    public function down(): void {}
};
