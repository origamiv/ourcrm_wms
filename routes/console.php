<?php

declare(strict_types=1);

use App\Events\AutomateEvent;
use App\Models\Creator;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

//Artisan::command('inspire', function (): void {
//    /** @var ClosureCommand $this */
//    $this->comment(Inspiring::quote());
//})->purpose('Display an inspiring quote');

$taskName = 'chats:posting';
Schedule::command($taskName)
    ->everyMinute()
//    ->before(function() use ($monitor) { $monitor->ping(['state' => 'run']); })
//    ->onSuccess(function() use ($monitor) { $monitor->ping(['state' => 'complete']); })
//    ->onFailure(function() use ($monitor) { $monitor->ping(['state' => 'fail']); })
    ->withoutOverlapping();

if (Schema::hasTable('messenger.creators')) {
    $creators = Creator::query()->where('status', 1)->get();
    foreach ($creators as $creator) {
        if (!empty($creator->cron)) {
            Schedule::command('chats:create', ['id' => $creator->id])->cron($creator->cron);
        } elseif (!empty($creator->date_create)) {
            Schedule::command('chats:create', ['id' => $creator->id])->nextRunDate($creator->date_create);
        }
    }
}


//if (Schema::hasTable('messenger.automates')) {
//    $automates = \App\Models\Automate::query()->where('status', 1)->get();
//    foreach ($automates as $automate) {
//        if (!empty($automate->cron)) {
//            Schedule::command('automate:start', ['id' => $automate->id])->cron($automate->cron);
//        }
//    }
//}
Schedule::command('automate:start 9')->everyMinute();
Schedule::command('automate:start 11')->everyMinute();
Schedule::command('automate:start 12')->everyMinute();
//Schedule::command('automate:start 8')->everyMinute();

//Schedule::command('db:connections', ['count_idle' => 60])->everyMinute();
