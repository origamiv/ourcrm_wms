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
        Schema::create('messenger.acc_seller', function (Blueprint $table) {
            $table->id();
            $table->string('telegram_id')->nullable()->comment('telegram_id');
            $table->string('name')->nullable()->comment('name');
            $table->string('cnt_buy')->nullable()->comment('cnt_buy');
            $table->timestamps();
            $table->softDeletes();
        });

        // Artisan::call('db:seed',['class'=>"database\\seeders\\AccSellerSeeder"]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chats.acc_seller');
    }
};
