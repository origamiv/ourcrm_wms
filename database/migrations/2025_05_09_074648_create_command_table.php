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
        Schema::create('messenger.commands', function (Blueprint $table) {
            $table->id();
            $table->string('command')->nullable()->comment('command');
            $table->string('shortname')->nullable()->comment('shortname');
            $table->integer('account_id')->nullable()->comment('account_id');
            $table->text('command_arguments')->nullable()->comment('command_arguments');
            $table->date('command_date')->nullable()->comment('command_date');
            $table->integer('status')->nullable()->comment('0 - новая, 1 - выполнена, 2 - возникла проблема, 3 - в процессе выполнения');
            $table->json('result')->nullable()->comment('result');
            $table->timestamps();
            $table->softDeletes();
        });

        // Artisan::call('db:seed',['class'=>"database\\seeders\\CommandSeeder"]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chats.commands');
    }
};
