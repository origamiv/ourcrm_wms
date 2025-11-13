<?php

// app/Events/MessageAdded.php

namespace App\Events;

use App\Models\Message;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageAddedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public int $message_id) {}
}

