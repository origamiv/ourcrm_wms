<?php

namespace App\Automate;

use App\Jobs\CommandsJob;
use App\Models\Automate;
use App\Models\Channel;
use App\Models\ChannelUser;
use App\Models\Message;

class AddWriterToChat
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

          //dd($this->automate->channel_id);


          $channelFrom=Channel::query()
              ->where('account_id','=',$this->automate->account_id)
              ->where('id','=',$this->automate->channel_id)
              ->first();

          $channelUsers=ChannelUser::query()
              //->where('account_id','=',$this->automate->account_id)
              ->where('channel_id','=',$channelFrom->id)
              ->get();


         // dd($channelUsers);

          $users[]=$this->message->from_id;

          dump($users);

     //     dump($this->message);


//          CommandsJob::dispatch([
//              'command' => 'add_users_to_group',
//              'account_id' => $this->automate->account_id,
//              'group_id' => $params['group'],
//              'users' => array_values($users),
//          ]);



          echo "A"; //"Репост сообщения {$this->message->id} с канала {$channelFrom->name} на канал {$channelTo->name} выполнен.\r\n";
          return true;
      }
}
