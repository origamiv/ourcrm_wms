<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AssistantMessageDirectionCommandEvent;
use App\Events\AssistantMessageDirectionInEvent;
use App\Events\AssistantMessageDirectionOutEvent;
use App\Events\AutomateAssistantEvent;
use App\Models\Account;
use App\Models\AssistantChat;
use App\Models\Message;
use App\Services\CailaService;
use GuzzleHttp\Client;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Carbon;

class AutomateAssistantListener {

    public CailaService $cailaService;

    public function __construct(CailaService $cailaService)
    {
        $this->cailaService = $cailaService;
    }

    public function handle(AutomateAssistantEvent $event): void
    {
        $automate=$event->automate;
        $channel=$event->channel;
        $message=$event->message;
        $account=Account::query()->where('id',$message->account_id)->first();

        $options=(!empty($automate->options)) ? json_decode($automate->options, true) : [];

        if(!empty($options['direction']) && ($options['direction']=='in') &&($message->napr!=1)) {return ;}
        if(!empty($options['direction']) && ($options['direction']=='out') &&($message->napr!=2)) {return ;}
        if(!empty($options['type_channel']) &&($channel->type_channel!=$options['type_channel'])) {return ;}

        if ($account->is_assistants!=1) {dump('assistant blocked in account'); return ;}

        dump($account->id, $channel->id, $message);
        $chat=AssistantChat::query()
            ->where('account_id','=',$account->id)
            ->where('channel_id','=',$channel->id)
            ->where('status', '=', 1)
            ->first();

        //dump($chat);

        if (empty($chat)) {
            $chat = AssistantChat::query()->create([
                'name' => $automate->name . ' ' . $account->name . ' ' . $channel->name,
                'account_id' => $message->account_id,
                'channel_id' => $channel->id,
                'assistant_id' => $automate->assistant_id,
                'status' => 1,
                'direction' => 'in',
                'created_at' => now(),
            ]);


            $data=$this->cailaService->post('/threads', [
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Сейчас у меня '.Carbon::now('Europe/Moscow')->toDateTimeString(),
                    ],
                ],
            ]);
            $ext_thread_id = $data['id'];

            $chat->ext_thread_id = $ext_thread_id;
            $chat->save();
        }


        if ($message->napr === Message::DIRECTION_IN && $chat->direction === 'in') {
            dump($message);
            event( new AssistantMessageDirectionInEvent($message, $chat, $automate));
        }
        if ($message->napr === Message::DIRECTION_OUT) {
            if ($chat->direction === 'wait') {
                event( new AssistantMessageDirectionOutEvent($message, $chat, $automate));
            }
            if ($chat->direction === 'command') {
                $options = json_decode($chat->options, true);
                if (!empty($chat->options) && is_array($options) && !empty($options['commands'])) {
                    if (in_array($message->message, $options['commands'])) {
                        event( new AssistantMessageDirectionCommandEvent($message, $chat, $automate));
                    }
                }
            }
        }
    }
}
