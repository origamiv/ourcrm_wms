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
        Schema::create('messenger.send_messages', function (Blueprint $table) {
            $table->id();
            $table->integer('account_id')->nullable()->comment('account_id');
            $table->integer('channel_id')->nullable()->comment('channel_id');
            $table->text('message')->nullable()->comment('Сообщение');
            $table->json('files')->nullable()->comment('Файл');
            $table->json('response')->nullable()->comment('Ответ после отправки');
            $table->integer('status')->nullable()->comment('0 - new, 1 - sent, 2 - blocked, 3 - in progress');
            $table->timestamps();
            $table->softDeletes();
        });

        // Artisan::call('db:seed',['class'=>"database\\seeders\\SendMessageSeeder"]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chats.send_messages');
    }
};
