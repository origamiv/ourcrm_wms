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
        Schema::create('clients.doc_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('shortname')->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
        foreach ([1 => ['Счёт', 'Счёт'], 2 => ['Акт', 'Акт'], 3 => ['УПД', 'УПД'], 4 => ['Договор', 'Договор'], 5 => ['Дополнительное соглашение', 'Доп. согл.'], 6 => ['Счёт-фактура', 'Счёт-фактура']] as $id => [$name, $shortname]) {
            DB::table('clients.doc_types')->insert(['id' => $id, 'name' => $name, 'shortname' => $shortname, 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::statement("SELECT setval(pg_get_serial_sequence('clients.doc_types', 'id'), 6)");
        Schema::create('clients.documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients.clients')->restrictOnDelete();
            $table->foreignId('doc_type_id')->constrained('clients.doc_types')->restrictOnDelete();
            $table->string('name');
            $table->string('shortname')->nullable();
            $table->integer('status')->default(0);
            $table->text('comment')->nullable();
            $table->text('internal_comment')->nullable();
            $table->json('src')->nullable();
            $table->date('doc_date')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('payed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('canceled_at')->nullable();
            $table->string('tenant_id');
            $table->index(['tenant_id', 'client_id']);
            $table->index(['tenant_id', 'doc_type_id']);
        });
        DB::statement('ALTER TABLE clients.documents ADD CONSTRAINT documents_status_check CHECK (status IN (0, 1, 2, 3))');
        DB::statement('ALTER TABLE clients.doc_types ADD CONSTRAINT doc_types_status_check CHECK (status IN (1, 2))');
    }

    public function down(): void
    {
        Schema::dropIfExists('clients.documents');
        Schema::dropIfExists('clients.doc_types');
    }
};
