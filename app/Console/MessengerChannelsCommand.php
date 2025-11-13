<?php

declare(strict_types=1);

namespace App\Console;


use App\Services\MailMessengerService;
use Illuminate\Console\Command;

final class MessengerChannelsCommand extends Command
{
    public $helper;
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'messenger:channels {id?}';

    /**
     * The console command description.
     */
    protected $description = 'Получает каналы из мессенжера';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info(now()->toDateTimeString().' Старт');
        $account_id=$this->argument('id') ?? null;
        $this->helper=new MailMessengerService($account_id);
        $this->helper->channels();
        $this->helper->messages();

      //  dd($this->helper);


        $this->info(now()->toDateTimeString().' завершение');
    }
}
