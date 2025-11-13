<?php

// app/Events/MessageAdded.php

namespace App\Events;

use App\Models\AssistantChat;
use App\Models\Automate;
use App\Models\Message;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssistantMessageDirectionInEvent
{
    use Dispatchable, SerializesModels;

    public AssistantChat $assistantChat;
    public Automate $automate;
    public Message $message;

    public function __construct(Message $message, AssistantChat $assistantChat, Automate $automate) {
        $this->assistantChat = $assistantChat;
        $this->automate = $automate;
        $this->message = $message;
    }
}

