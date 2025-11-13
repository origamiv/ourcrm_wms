<?php

namespace App\Automate;

use App\Jobs\CommandsJob;
use App\Models\Account;
use App\Models\Automate;
use App\Models\Channel;
use App\Models\Message;
use Carbon\Carbon;

class Reposting
{
    private Automate $automate;
    private Message $message;

    public function __construct(Automate $automate,Message $message)
      {
          $this->automate = $automate;
          $this->message = $message;
      }

      public function handle()
      {
          $params=json_decode($this->automate->params, true);
          $channelFrom=Channel::query()
              ->where('account_id','=',$this->message['account_id'])
              ->where('channel','=',$this->message['channel'])
              ->first();

          $account = Account::query()
              ->where('id', '=', $this->automate->account_id)
              ->first();

          $channelTo=Channel::query()
              ->where('id','=',$params['channel_id'])
              ->first();



          if (empty($account->blocked_seconds)) {
              CommandsJob::dispatch([
                  'command' => 'send',
                  'account_id' => $this->message->account_id,
                  'peer_id' => $channelTo->channel,
                  'message' => $this->message->message,
              ]);
          }
          else {
              CommandsJob::dispatch([
                  'command' => 'send',
                  'account_id' => $this->message->account_id,
                  'peer_id' => $channelTo->channel,
                  'message' => $this->message->message,
              ])
                  ->delay(Carbon::parse($account->updated_at)
                      ->addSeconds($account->blocked_seconds)
                      ->diffInSeconds(now())
                  );

          }


          echo "R"; //"Репост сообщения {$this->message->id} с канала {$channelFrom->name} на канал {$channelTo->name} выполнен.\r\n";
          return true;
      }
}
