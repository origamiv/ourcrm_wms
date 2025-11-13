<?php

declare(strict_types=1);

namespace App\Console;

use App\Events\AutomateEvent;
use App\Jobs\ProcessTelegramUpdate;
use App\Models\Automate;
use App\Models\Creator;
use App\Models\Posting;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\Installer\Models\Feature;
use Modules\Installer\Models\Module;

final class StartAutomateCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'automate:start {id}';

    /**
     * The console command description.
     */
    protected $description = 'запускает автоматизацию по расписанию';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info(now()->toDateTimeString() . ' Старт');
        $id=$this->argument('id');
        $automate=Automate::query()->where('id','=',$id)->where('status','=',1)->first();

        if (!empty($automate) && !empty($automate->params)) {
            event(new AutomateEvent($automate, null));
        }
        $this->info(now()->toDateTimeString() . ' завершение');
    }


}
