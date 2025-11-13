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
        Schema::create('messenger.channels', function (Blueprint $table) {
            $table->id();
            $table->integer('account_id')->nullable()->comment('Аккаунт');
            $table->integer('bot_id')->nullable()->comment('Бот');
            $table->string('name')->nullable()->comment('Название');
            $table->string('shortname')->nullable()->comment('Короткое');
            $table->string('channel')->nullable()->comment('Название в мессенджере');
            $table->string('username')->nullable()->comment('Псевдоним');
            $table->string('phone')->nullable()->comment('Телефон');
            $table->string('fn_avatar')->nullable()->comment('Аватар');
            $table->integer('type_channel')->nullable()->comment('Тип канала');
            $table->integer('cnt')->nullable()->comment('Количество сообщений');
            $table->integer('cnt_parsed')->nullable()->comment('Количество полученных сообщений');
            $table->integer('cnt_people')->nullable()->comment('Количество участников');
            $table->integer('cnt_people_parsed')->nullable()->comment('Количество полученных участников');
            $table->integer('can_view_participants')->nullable()->comment('Можно просматривать участников');
            $table->date('date_last_message')->nullable()->comment('Время последнего сообщения');
            $table->date('date_last_check')->nullable()->comment('Время последней проверки сообщений');
            $table->integer('frequency')->nullable()->comment('Частота сообщений');
            $table->integer('status')->nullable()->comment('Статус');
            $table->jsonb('srcDialog')->nullable()->comment('Исходник');
            $table->jsonb('src')->nullable()->comment('Исходник');
            $table->integer('last_message_id')->nullable()->comment('Последнее сообщение');
            $table->integer('cnt_unread')->nullable()->comment('Число непрочитанных сообщений');
            $table->date('date_last_read')->nullable()->comment('Время прочтения сообщений');
            $table->json('last_message_src')->nullable()->comment('last_message_src');
            $table->date('tagged_at')->nullable()->comment('tagged_at');
            $table->timestamps();
            $table->softDeletes();
        });

        // Artisan::call('db:seed',['class'=>"database\\seeders\\ChannelSeeder"]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chats.channels');
    }
};
