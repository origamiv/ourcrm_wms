<?php

namespace App\Automate;

use App\Jobs\CommandsJob;
use App\Models\Automate;
use App\Models\Channel;
use App\Models\Message;

class PostingTimer
{
    private Automate $automate;
    private ?Message $message;

    public function __construct(Automate $automate,?Message $message)
      {
          $this->automate = $automate;
          $this->message = $message;
      }

    public function handle()
    {
        $params=json_decode($this->automate->params, true);
        $channelTo=Channel::query()
            ->where('id','=',$params['channel_id'])
            ->first();

        $sendParams=[
            'command' => 'send',
            'account_id' => $this->automate->account_id,
            'peer_id' => $channelTo->channel,
            'message' => $params['message'],
        ];
        if (!empty($params['photos'])) {$sendParams['photos'] = $params['photos'];}
        CommandsJob::dispatch($sendParams);


        echo "T"; //"Репост сообщения {$this->message->id} с канала {$channelFrom->name} на канал {$channelTo->name} выполнен.\r\n";
        return true;
    }
}
