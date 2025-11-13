<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\AssistantChatUpdated;
use App\Events\AssistantMessageDirectionCommandEvent;
use App\Events\AssistantMessageDirectionInEvent;
use App\Events\AssistantMessageDirectionOutEvent;
use App\Events\AutomateAssistantEvent;
use App\Events\AutomateEvent;
use App\Events\MessageAddedEvent;
use App\Listeners\AsistantAnswerListener;
use App\Listeners\AutomateAssistantListener;
use App\Listeners\AutomateListener;
use App\Listeners\HandleAssistantChatUpdate;
use App\Listeners\MessageAddedListener;
use App\Listeners\MigrationEndListener;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;


final class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        TransactionCommitted::class => [
            MigrationEndListener::class,
        ],
        MigrationsEnded::class => [
            MigrationEndListener::class,
        ],
//        AutomateAssistantEvent::class => [
//            AutomateAssistantListener::class,
//        ],
//        AutomateEvent::class=>[
//            AutomateListener::class,
//        ],
//        AssistantMessageDirectionInEvent::class => [
//            AsistantAnswerListener::class,
//        ],
//        AssistantMessageDirectionOutEvent::class => [
//            AsistantAnswerListener::class,
//        ],
//        AssistantMessageDirectionCommandEvent::class => [
//            AsistantAnswerListener::class,
//        ],


//        MessageAddedEvent::class=>[
//            MessageAddedListener::class,
//        ],
//        AssistantMessageDirectionInEvent::class=>[
//            AsistantAnswerListener::class
//        ]
//        AssistantChatUpdated::class => [
//            HandleAssistantChatUpdate::class,
//        ],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
