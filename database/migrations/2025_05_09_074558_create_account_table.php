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
        Schema::create('messenger.accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->comment('Название');
            $table->string('shortname')->nullable()->comment('Короткое');
            $table->integer('user_id')->nullable()->comment('Пользователь');
            $table->integer('messenger_id')->nullable()->comment('Мессенджер');
            $table->string('login')->nullable()->comment('Логин');
            $table->string('code')->nullable()->comment('Код');
            $table->string('password')->nullable()->comment('Пароль');
            $table->integer('is_2fa')->nullable()->comment('Включена 2ФА');
            $table->string('pass2fa')->nullable()->comment('Пароль 2FA');
            $table->string('telegram_id')->nullable()->comment('ID в Телеграмм');
            $table->text('tas_session_status')->nullable()->comment('Статус сессии');
            $table->string('tas_session_expires')->nullable()->comment('Дата истечения сессии');
            $table->string('first_name')->nullable()->comment('Имя');
            $table->string('last_name')->nullable()->comment('Фамилия');
            $table->string('username')->nullable()->comment('Псевдоним');
            $table->jsonb('src')->nullable()->comment('Инфо об аккаунте');
            $table->jsonb('options')->nullable()->comment('Опции');
            $table->integer('slot')->nullable()->comment('Слот ');
            $table->date('last_used_at')->nullable()->comment('Время последнего использования ');
            $table->date('new_messages_last_check')->nullable()->comment('Время последней проверки сообщений');
            $table->string('phone_code_hash')->nullable()->comment('phone_code_hash');
            $table->integer('cnt')->nullable()->comment('Количество сообщений');
            $table->integer('status')->nullable()->comment('Статус');
            $table->integer('status_messenger')->nullable()->comment('Статус в мессенджере');
            $table->integer('cnt_people')->nullable()->comment('cnt_people');
            $table->string('tas_port')->nullable()->comment('Port для сессии');
            $table->integer('port')->nullable()->comment('port');
            $table->string('fn_avatar')->nullable()->comment('fn_avatar');
            $table->string('icon')->nullable()->comment('icon');
            $table->date('tagged_at')->nullable()->comment('tagged_at');
            $table->string('mode')->nullable()->comment('mode');
            $table->timestamps();
            $table->softDeletes();
        });

        // Artisan::call('db:seed',['class'=>"database\\seeders\\AccountSeeder"]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chats.accounts');
    }
};
