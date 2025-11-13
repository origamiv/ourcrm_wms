<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\MessageAddedEvent;
use App\Models\Message;

class MessageObserver
{
    public function created(Message $message): void
    {
       // MessageAddedEvent::dispatch($message);
    }
}
