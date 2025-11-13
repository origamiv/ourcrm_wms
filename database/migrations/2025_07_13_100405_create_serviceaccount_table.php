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
        Schema::create('messenger.service_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->comment('Название');
            $table->string('shortname')->nullable()->comment('Короткое название');
            $table->integer('service_id')->nullable()->comment('Сервис');
            $table->string('login')->nullable()->comment('Логин');
            $table->string('password')->nullable()->comment('Пароль');
            $table->string('token')->nullable()->comment('Токен');
            $table->json('options')->nullable()->comment('Параметры');
            $table->integer('status')->nullable()->comment('Статус');
            $table->double('balance')->nullable()->comment('Баланс');
            $table->integer('cnt')->nullable()->comment('Количество');
            $table->timestamps();
            $table->softDeletes();
        });

        // Artisan::call('db:seed',['class'=>"database\\seeders\\ServiceAccountSeeder"]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('messenger.service_accounts');
    }
};
