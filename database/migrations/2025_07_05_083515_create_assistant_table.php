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
        Schema::create('messenger.assistants', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->comment('Название');
            $table->string('shortname')->nullable()->comment('Короткое');
            $table->text('prompt')->nullable()->comment('Промпт');
            $table->text('data')->nullable()->comment('Данные');
            $table->text('func')->nullable()->comment('Функция');
            $table->string('ext_id')->nullable()->comment('ID ассистента');
            $table->string('ext_model')->nullable()->comment('Модель');
            $table->jsonb('options')->nullable()->comment('Опции');
            $table->integer('status')->nullable()->comment('Статус');
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
        Schema::dropIfExists('messenger.assistants');
    }
};
