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

class CommandsJob implements ShouldQueue
{
    use Queueable;

    public array $params;

    /**
     * Create a new job instance.
     */
    public function __construct(array $params)
    {
        $this->onQueue('sendmessages');
        $this->params = $params;
    }

}
