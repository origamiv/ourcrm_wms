<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\AssistantChat;

class AssistantChatUpdated
{
    public AssistantChat $chat;

    public function __construct(AssistantChat $chat)
    {
        $this->chat = $chat;
    }
}
