<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\AssistantChat;
use App\Models\Automate;
use App\Models\Message;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AutomateEvent implements ShouldQueue
{
    use Dispatchable, SerializesModels;
    public AssistantChat $chat;
    public Automate $automate;
    public ?Message $message;

    public function __construct(Automate $automate, ?Message $message)
    {
        $this->message = $message;
        $this->automate = $automate;
    }
}
