<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Migration;

final class MigrationEndListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle($event): void
    {
         $migration = Migration::query()->orderBy('id', 'desc')->first();

         if ($migration && empty($migration->module)) {
             $migration->module = config('app.module.name');
             $migration->save();
         }
    }
}
