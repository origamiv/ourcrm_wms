<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AssistantChatUpdated;
use App\Helpers\AssistantChatHelper;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleAssistantChatUpdate implements ShouldQueue
{
    protected AssistantChatHelper $helper;

    public function __construct(AssistantChatHelper $helper)
    {
        $this->helper = $helper;
    }

    public function handle(AssistantChatUpdated $event): void
    {
        $this->helper->handleDirectionUpdate($event->chat);
    }
}
