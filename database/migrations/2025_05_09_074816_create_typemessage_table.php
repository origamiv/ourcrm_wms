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
        Schema::create('messenger.type_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->comment('Название');
            $table->string('shortname')->nullable()->comment('Короткое название');
            $table->integer('cnt')->nullable()->comment('Кол-во использований');
            $table->json('other')->nullable()->comment('Название в других сервисах ');
            $table->integer('status')->nullable()->comment('Статус');
            $table->timestamps();
            $table->softDeletes();
        });

        // Artisan::call('db:seed',['class'=>"database\\seeders\\TypeMessageSeeder"]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chats.type_messages');
    }
};
