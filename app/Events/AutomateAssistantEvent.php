<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\AssistantChat;
use App\Models\Automate;
use App\Models\Channel;
use App\Models\Message;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AutomateAssistantEvent
{
    use Dispatchable, SerializesModels;
    public AssistantChat $chat;
    public Automate $automate;
    public Message $message;
    public Channel $channel;

    public function __construct(Automate $automate, Message $message, Channel $channel)
    {
        $this->message = $message;
        $this->automate = $automate;
        $this->channel = $channel;
    }
}
