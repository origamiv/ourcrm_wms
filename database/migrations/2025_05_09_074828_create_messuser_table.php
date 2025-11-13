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
        Schema::create('messenger.users', function (Blueprint $table) {
            $table->id();
            $table->integer('bot_id')->nullable()->comment('bot_id');
            $table->integer('account_id')->nullable()->comment('account_id');
            $table->string('name')->nullable()->comment('name');
            $table->integer('messenger_id')->nullable()->comment('messenger_id');
            $table->string('peer_id')->nullable()->comment('peer_id');
            $table->string('first_name')->nullable()->comment('first_name');
            $table->string('last_name')->nullable()->comment('last_name');
            $table->string('photo_id')->nullable()->comment('photo_id');
            $table->string('username')->nullable()->comment('username');
            $table->json('src')->nullable()->comment('src');
            $table->string('status')->nullable()->comment('Статус');
            $table->integer('activity')->nullable()->comment('Активность');
            $table->timestamps();
            $table->softDeletes();
        });

        // Artisan::call('db:seed',['class'=>"database\\seeders\\MessUserSeeder"]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chats.users');
    }
};
