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
        Schema::create('messenger.postings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->comment('Название');
            $table->string('shortname')->nullable()->comment('Короткое');
            $table->date('date_send')->nullable()->comment('Дата и время отправки');
            $table->integer('account_id')->nullable()->comment('Аккаунт');
            $table->integer('channel_id')->nullable()->comment('Канал');
            $table->integer('message_id')->nullable()->comment('ID сообщения после отправки');
            $table->mediumtext('message')->nullable()->comment('Сообщение');
            $table->integer('status')->nullable()->comment('Статус');
            $table->timestamps();
            $table->softDeletes();
        });

        // Artisan::call('db:seed',['class'=>"database\\seeders\\PostingSeeder"]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('messenger.postings');
    }
};
