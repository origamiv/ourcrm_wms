<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\OpenAIAssistantHelper;
use App\Models\AIModel;
use App\Models\Assistant;
use App\Models\AssistantChat;
use App\Models\AssistantDialog;
use GuzzleHttp\Client;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
class ProcessTelegramUpdate implements ShouldQueue
{
    use Queueable;
    public string $message;
    public string $login;

    public function __construct(string $message, string $login)
    {
        $this->message = $message;
        $this->login = $login;
        $this->onQueue('UpdateUserStatus');
        dump($this->message);
    }

    public function handle()
    {
        $message=str_replace("'",'"',$this->message);
        $r=json_decode($message,true);
        dump($r);
        return true;
    }
}
