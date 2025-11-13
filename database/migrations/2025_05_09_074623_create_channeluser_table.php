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
        Schema::create('messenger.channel_users', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable()->comment('ID польз у нас');
            $table->integer('channel_id')->nullable()->comment('ID канала у нас');
            $table->string('mess_user_id')->nullable()->comment('ID польз в мессенджере');
            $table->string('mess_channel')->nullable()->comment('ID канала в мессенджере');
            $table->json('src')->nullable()->comment('src');
            $table->integer('account_id')->nullable()->comment('account_id');
            $table->timestamps();
            $table->softDeletes();
        });

        // Artisan::call('db:seed',['class'=>"database\\seeders\\ChannelUserSeeder"]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chats.channel_users');
    }
};
