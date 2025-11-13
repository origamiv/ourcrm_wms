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
        Schema::create('messenger.automates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->comment('Название');
            $table->string('shortname')->nullable()->comment('Короткое');
            $table->integer('account_id')->nullable()->comment('Аккаунт');
            $table->integer('channel_id')->nullable()->comment('Канал');
            $table->integer('assistant_id')->nullable()->comment('Ассистент');
            $table->jsonb('options')->nullable()->comment('Настройки');
            $table->integer('status')->nullable()->comment('Статус');
            $table->timestamps();
            $table->softDeletes();
        });

        // Artisan::call('db:seed',['class'=>"database\\seeders\\AutomateSeeder"]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('messenger.automates');
    }
};
