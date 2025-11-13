<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messenger.assistants_documents', function (Blueprint $table) {
            $table->id();
            $table->integer('assistant_id')->nullable()->comment('ID ассистента наш');
            $table->string('name')->nullable()->comment('Файл в сторадж');
            $table->string('title')->nullable()->comment('Название');
            $table->text('content')->nullable()->comment('Контент');
            $table->vector('embedding', 1536)->nullable(); // длина под модель text-embedding-3-small
            $table->string('data_model')->nullable()->comment('Модель');
            $table->integer('status')->default(1)->comment('Статус');
            $table->timestamps();
            $table->softDeletes();
        });

        // Artisan::call('db:seed',['class'=>"database\\seeders\\AssistantSeeder"]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('messenger.assistants_documents');
    }
};
