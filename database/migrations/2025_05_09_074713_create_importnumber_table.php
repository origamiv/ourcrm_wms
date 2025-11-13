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
        Schema::create('messenger.import_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('numbers')->nullable()->comment('numbers');
            $table->integer('status')->nullable()->comment('status');
            $table->string('code')->nullable()->comment('code');
            $table->string('pass2fa')->nullable()->comment('pass2fa');
            $table->timestamps();
            $table->softDeletes();
        });

        // Artisan::call('db:seed',['class'=>"database\\seeders\\ImportNumberSeeder"]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chats.import_numbers');
    }
};
