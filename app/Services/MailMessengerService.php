<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\Channel;
use App\Models\ChannelUser;
use App\Models\MessUser;
use DirectoryTree\ImapEngine\Mailbox;
use DirectoryTree\ImapEngine\Message;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class MailMessengerService
{
    public $mailbox;
    public $account;

    public function __construct($account_id)
    {
        $this->account=Account::query()->find($account_id);
        //$options=json_decode($this->account->options, true);
        $options=$this->account->options;
        $this->mailbox = new Mailbox($options['connection']);

        //$this->messages();
        //$this->channels();

    }

    public function channels()
    {
        $inbox = $this->mailbox->inbox();
        $messages = $inbox->messages()
            ->since(Carbon::now()->subDays(1))
            ->withHeaders()
            ->withFlags()
            ->withBody()
            ->get();

        $channels = [];
        foreach ($messages as $message) {
            $channelData = [
                'name' => $message->from()->name(),
                'shortname' => Str::transliterate($message->from()->name()),
                'channel' => $message->from()->email(),
                'account_id' => $this->account->id,
                'messenger_id' => $this->account->messenger_id,
                'status' => 1,
            ];

            $channel=Channel::query()->updateOrCreate([
                'channel' => $channelData['channel'],
                'account_id' => $this->account->id,
                'messenger_id' => $this->account->messenger_id,
            ],
                $channelData);

            //dd($channel->account());
            $messUser=MessUser::query()->updateOrCreate([
                'account_id'=>$channel->account_id,
                'messenger_id'=>$channel->account()->messenger_id,
                'peer_id'=>$channel->channel,
            ],[
                'account_id'=>$channel->account_id,
                'name'=>$channel->name,
                'peer_id'=>$channel->channel,
                'messenger_id'=>$channel->account()->messenger_id,
                'first_name'=>$channel->name,
                'status'=>1,
                'created_at'=>now(),
            ]);

            ChannelUser::query()->firstOrCreate([
                'user_id'=>$messUser->id,
                'channel_id'=>$channel->id,
            ],[
                'user_id'=>$messUser->id,
                'channel_id'=>$channel->id,
                'mess_user_id'=>$messUser->peer_id,
                'mess_channel'=>$messUser->peer_id,
                'account_id'=>$channel->account_id,
                'created_at'=>now(),
                'messenger_id'=>$this->account->messenger_id,
            ]);
        }
    }

    public function messages()
    {
        $inbox = $this->mailbox->inbox();
        $messages = $inbox->messages()
            ->since(Carbon::now()->subDays(1))
            ->withHeaders()
            ->withFlags()
            ->withBody()
            ->get();

        $channels = [];
        /** @var Message $message */
        foreach ($messages as $message) {
            //dump($message);
            $channel = Channel::query()->where('channel', $message->from()->email())->first();
            $account = $channel->account();

            $messageData = [
                'account_id' => $channel->account_id,
                'channel_id' => $channel->id,
                'channel' => $channel->channel,
                'channel_name' => $channel->name,
                'message_id' => $message->messageId(),
                'message'=>addslashes($message->html()),
                'thread'=>$message->subject(),
                'msg_date'=>$message->date()->toDateTimeString(),
                'from_id'=>$channel->id,
                'from_name'=>$channel->name,
                //'src'=>json_encode($message->toArray(), JSON_UNESCAPED_UNICODE),
                'messenger_id' => $this->account->messenger_id,
                'type_msg'=>'mail',
                'status'=>0,
                'napr'=>\App\Models\Message::DIRECTION_IN,
            ];

            dump($messageData);
            $messageExisting=\App\Models\Message::query()->updateOrCreate([
                'messenger_id' => $this->account->messenger_id,
                'account_id' => $this->account->id,
                'channel_id' => $channel->id,
                'message_id' => $message->messageId(),
            ],
                $messageData);
        }
    }

}
