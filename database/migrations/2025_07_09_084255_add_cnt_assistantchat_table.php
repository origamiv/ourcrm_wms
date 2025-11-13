<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('messenger.assistant_chats', function (Blueprint $table) {
            $table->integer('cnt')->nullable()->default(0)->comment('количество диалогов');
        });

        // Artisan::call('db:seed',['class'=>"database\\seeders\\AssistantChatSeeder"]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
};
