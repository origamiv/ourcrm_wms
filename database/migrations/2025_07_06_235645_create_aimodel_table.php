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
        Schema::create('messenger.ai_models', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->comment('Название');
            $table->string('shortname')->nullable()->comment('Короткое');
            $table->float('price_prompt')->nullable()->comment('Цена промпта за 1000 токенов');
            $table->float('price_complete')->nullable()->comment('Цена завершения за 1000 токенов');
            $table->jsonb('options')->nullable()->comment('Опции');
            $table->integer('status')->nullable()->comment('Статус');
            $table->timestamps();
            $table->softDeletes();
        });

        App\Models\AIModel::query()->create([
            'name' => 'gpt-4o-mini',
            'shortname' => 'gpt-4o-mini',
            'price_prompt' => 0.02,
            'price_complete' => 0.1,
            'status' => 1,
            'created_at' => now(),
        ]);

        // Artisan::call('db:seed',['class'=>"database\\seeders\\AIModelSeeder"]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('messenger.ai_models');
    }
};
