<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('DummyModuleLower.DummyTableLower', function (Blueprint $table) {
            $table->id();
//{$fields}
            $table->timestamps();
            $table->softDeletes();
        });

        // Artisan::call('db:seed',['class'=>"database\\seeders\\{{ model }}Seeder"]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('DummyModuleLower.DummyTableLower');
    }
};
