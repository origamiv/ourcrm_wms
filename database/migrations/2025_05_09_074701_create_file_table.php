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
        Schema::create('messenger.files', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->comment('Название');
            $table->string('shortname')->nullable()->comment('Короткое название');
            $table->integer('account_id')->nullable()->comment('Аккаунт');
            $table->integer('channel_id')->nullable()->comment('Канал');
            $table->integer('message_id')->nullable()->comment('Сообщение');
            $table->integer('cnt')->nullable()->comment('Кол-во скачиваний');
            $table->string('path')->nullable()->comment('Путь  ');
            $table->integer('status')->nullable()->comment('Статус');
            $table->json('src')->nullable()->comment('исходник сообщения');
            $table->timestamps();
            $table->softDeletes();
        });

        // Artisan::call('db:seed',['class'=>"database\\seeders\\FileSeeder"]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chats.files');
    }
};
