<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AssistantChatUpdated;
use App\Events\AutomateEvent;
use App\Helpers\AssistantChatHelper;
use Illuminate\Contracts\Queue\ShouldQueue;

class AutomateListener
{
    private AutomateEvent $event;
    public function handle(AutomateEvent $event): void
    {
        $options=!empty($event->automate->options) ? json_decode($event->automate->options, true) : null;
        if (empty($options)) { return ;}
        $class=$options['class'];
        $task=new $class($event->automate, $event->message);

        $task->handle();
    }
}
