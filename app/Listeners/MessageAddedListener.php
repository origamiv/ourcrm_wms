<?php

declare(strict_types=1);

// app/Listeners/MessageAddedListener.php

namespace App\Listeners;

use App\Events\AssistantMessageDirectionCommandEvent;
use App\Events\AssistantMessageDirectionInEvent;
use App\Events\AssistantMessageDirectionOutEvent;
use App\Events\AutomateAssistantEvent;
use App\Events\AutomateEvent;
use App\Events\MessageAddedEvent;
use App\Jobs\AIAnswerJob;
use App\Jobs\CommandsJob;
use App\Models\Account;
use App\Models\AssistantChat;
use App\Models\Automate;
use App\Models\Channel;
use App\Models\Message;
use GuzzleHttp\Client;

final class MessageAddedListener
{
    public function handle(MessageAddedEvent $event): void
    {

//        echo now()->toDateTimeString() . "\r\n";
        /** @var Message $message */
        $message = Message::query()->find($event->message_id);
        if (empty($message)) {
            sleep(2);
        }

        //if ($message->account_id!=25) {return ;}

        $message = Message::query()->find($event->message_id);
        if (empty($message) || (empty($message->message))) {
            return;
        }


        $account = Account::query()->find($message->account_id);
        //dump($message);

        try {
            $channel = Channel::query()->firstOrCreate([
                'account_id' => $message->account_id,
                'channel' => $message->channel,
                'messenger_id' => $message->messenger_id,
            ], [
                'account_id' => $message->account_id,
                'channel' => $message->channel,
                'messenger_id' => $message->messenger_id,
                'status' => 1,
                'type_channel' => 0,
            ]);
        } catch (\Exception $e) {
            $channel = Channel::query()->where('channel', $message->channel)->first();
        }

        if (empty($channel->type_channel) || $channel->type_channel == 0) {
            if (!empty($channel->src)) {
                $data=json_decode($channel->src, true);
                $type_data=$data['_'];
                switch ($type_data) {
                    case 'Channel': $channel->type_channel = 1; break;
                    case 'Chat': $channel->type_channel = 2; break;
                    case 'User': $channel->type_channel = 3; break;
                    default: $channel->type_channel = 0; break;
                }
                $channel->save();

                if ($channel->type_channel == 0) {
                    CommandsJob::dispatch([
                        'command' => 'fetch_channel_info',
                        'account_id' => $message->account_id,
                        'channel_id' => $channel->channel
                    ]);
                }
            }

        }

        //dump($channel, $message);

        $automate = Automate::query()
            ->where('account_id', '=', $message->account_id)
            ->where('channel_id', '=', $channel->id)
            ->whereNull('assistant_id')
            ->where('status', '=', 1)
            ->first();
        if (empty($automate)) {
            $automate = Automate::query()
                ->where('account_id', '=', $message->account_id)
                ->whereNull('channel_id')
                ->where('status', '=', 1)
                ->first();
        }

        if (empty($channel)) {
            return;
        }
        Message::query()
            ->where('account_id', '=', $message->account_id)
            ->where('channel', '=', $channel->channel)
            ->update([
                'channel_id' => $channel->id,
                'channel_name' => $channel->name,
            ]);

        $message=Message::query()->find($message->id);

//        dump($message);

        if (empty($channel->name)) {
            CommandsJob::dispatch([
                'command' => 'fetch_channel_info',
                'account_id' => $message->account_id,
                'channel_id' => $message->channel,
            ]);
        }

//        $message->channel_id = $channel->id;
//        $message->channel_name = $channel->name;
//        $message->save();

        if (!empty($automate) && empty($automate->cron)) {
            dump($message);
            if (!empty($automate) && (!empty($automate->assistant_id))) {
                event(new AutomateAssistantEvent($automate, $message, $channel));
            } elseif (!empty($automate) && (empty($automate->assistant_id))) {
                event(new AutomateEvent($automate, $message));
            }
        }
    }
}
