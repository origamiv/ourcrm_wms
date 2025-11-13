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
        Schema::create('messenger.assistant_dialogs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->comment('Название');
            $table->string('shortname')->nullable()->comment('Короткое');
            $table->integer('assistant_id')->nullable()->comment('Ассистент');
            $table->integer('account_id')->nullable()->comment('Аккаунт');
            $table->integer('channel_id')->nullable()->comment('Канал');
            $table->text('query')->nullable()->comment('Запрос');
            $table->text('answer')->nullable()->comment('Ответ');
            $table->string('direction')->nullable()->comment('направление');
            $table->string('ext_run_id')->nullable()->comment('ID запуска');
            $table->jsonb('ext_run_data')->nullable()->comment('Инфо запуска');
            $table->integer('total_tokens')->nullable()->comment('Потрачено токенов');
            $table->float('price')->nullable()->comment('Цена');
            $table->integer('status')->nullable()->comment('Статус');
            $table->timestamps();
            $table->softDeletes();
        });

        // Artisan::call('db:seed',['class'=>"database\\seeders\\AssistantDialogSeeder"]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('messenger.assistant_dialogs');
    }
};
