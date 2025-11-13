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
        Schema::create('messenger.messages', function (Blueprint $table) {
            $table->id();
            $table->integer('account_id')->nullable()->comment('Аккаунт');
            $table->integer('bot_id')->nullable()->comment('Бот');
            $table->integer('messenger_id')->nullable()->comment('Мессенджер');
            $table->string('message_id')->nullable()->comment('ИД сообщения');
            $table->integer('channel_id')->nullable()->comment('Канал');
            $table->string('channel')->nullable()->comment('ИД канала в мессенджере');
            $table->string('channel_name')->nullable()->comment('Название канала');
            $table->integer('thread_id')->nullable()->comment('ID темы');
            $table->string('thread')->nullable()->comment('Тема в мессенджере');
            $table->text('message')->nullable()->comment('Сообщение');
            $table->json('files')->nullable()->comment('Сообщение');
            $table->date('msg_date')->nullable()->comment('Время сообщения');
            $table->integer('napr')->nullable()->comment('Направление');
            $table->string('from_id')->nullable()->comment('Отправитель');
            $table->string('from_name')->nullable()->comment('Имя отправителя');
            $table->jsonb('src')->nullable()->comment('Исходник');
            $table->string('type_msg')->nullable()->comment('Тип сообщения');
            $table->string('user')->nullable()->comment('Псевдоним');
            $table->string('comment')->nullable()->comment('Примечание');
            $table->integer('resend_status')->nullable()->comment('Статус пересылки');
            $table->string('phone')->nullable()->comment('Телефон');
            $table->integer('channel_id_our')->nullable()->comment('ID канала');
            $table->date('date_view')->nullable()->comment('Время чтения сообщений');
            $table->string('parent_message_id')->nullable()->comment('Связанное сообщение');
            $table->string('messenger_user_id')->nullable()->comment('Пользователь мессенджера');
            $table->integer('is_hidden_for_user')->nullable()->comment('Скрытое');
            $table->integer('status')->nullable()->comment('Статус');
            $table->integer('is_media')->nullable()->comment('Есть вложения');
            $table->integer('comments_cnt')->nullable()->comment('кол-во комментариев');
            $table->integer('replies_cnt')->nullable()->comment('кол-во ответов');
            $table->integer('type_msg_id')->nullable()->comment('тип сообщения');
            $table->integer('is_read')->nullable()->comment('Сообщение прочитано или нет');
            $table->json('parent_message_src')->nullable()->comment('parent_message_src');
            $table->integer('is_tagged')->nullable()->comment('is_tagged');
            $table->json('reaction')->nullable()->comment('reaction');
            $table->integer('edited_status')->nullable()->comment('edited_status');
            $table->integer('edited_cnt')->nullable()->comment('edited_cnt');
            $table->timestamps();
            $table->softDeletes();
        });

        // Artisan::call('db:seed',['class'=>"database\\seeders\\MessageSeeder"]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chats.messages');
    }
};
