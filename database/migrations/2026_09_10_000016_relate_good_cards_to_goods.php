<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("SET LOCAL lock_timeout = '5s'");
        DB::statement('ALTER TABLE goods.goods ALTER COLUMN goodcard_id DROP NOT NULL');
        Schema::table('goods.goods', fn (Blueprint $table) => $table->json('articul')->nullable());
        Schema::table('goods.good_cards', function (Blueprint $table) {
            $table->foreignId('good_id')->nullable()->constrained('goods.goods')->restrictOnDelete();
            $table->index(['tenant_id', 'good_id']);
        });
        DB::statement('UPDATE goods.goods SET goodcard_id = NULL WHERE goodcard_id = 0');
        DB::statement('UPDATE goods.good_cards c SET good_id = links.good_id FROM (SELECT c.id, min(g.id) AS good_id FROM goods.good_cards c JOIN goods.goods g ON g.goodcard_id = c.id AND g.tenant_id IS NOT DISTINCT FROM c.tenant_id GROUP BY c.id HAVING count(*) = 1) links WHERE c.id = links.id');
    }

    public function down(): void
    {
        if (DB::table('goods.goods')->whereNull('goodcard_id')->exists() || DB::table('goods.good_cards')->whereNotNull('good_id')->exists()) {
            throw new RuntimeException('Откат уничтожит связи или потребует обязательных карточек. Сначала подготовьте данные.');
        }
        Schema::table('goods.good_cards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('good_id');
        });
        Schema::table('goods.goods', fn (Blueprint $table) => $table->dropColumn('articul'));
        DB::statement('ALTER TABLE goods.goods ALTER COLUMN goodcard_id SET NOT NULL');
    }
};
