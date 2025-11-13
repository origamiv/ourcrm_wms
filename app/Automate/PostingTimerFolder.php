<?php

namespace App\Automate;

use App\Jobs\CommandsJob;
use App\Models\Account;
use App\Models\Automate;
use App\Models\Channel;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class PostingTimerFolder
{
    private Automate $automate;
    private ?Message $message;

    public function __construct(Automate $automate, ?Message $message)
    {
        $this->automate = $automate;
        $this->message = $message;
    }

    public function handle()
    {
        $params = json_decode($this->automate->params, true);
        $channelTo = Channel::query()
            ->where('id', '=', $params['channel_id'])
            ->first();

        $account = Account::query()
            ->where('id', '=', $this->automate->account_id)
            ->first();

//        dump($account);
//        if (!empty($account->blocked_seconds)) {
//            echo "G\r\n";
//            $date=Carbon::parse($account->updated_at)->addSeconds($account->blocked_seconds)
//                ->timezone('Europe/Moscow');
//            $pr=$date->diffInSeconds(now(), false);
//            if($pr<0) {return false;}
//        }

        $directories = Storage::disk('public')->directories('girls');
        $cnt = 0;
        $directory=$directories[2];
        //dd($directory);
        foreach ($directories as $directory)
        {
            $cnt++;
            $photos = [];
            $strFotos = '';
            $files = Storage::disk('public')->files($directory);
            foreach ($files as $file) {
                if (basename($file) != 'message.txt') {
                    //dump($fn);
                    $url = config('app.url') . '/storage/' . $file;
                    $fotos[] = $url;
                    $strFotos .= $url . "\n";
                } else {
                    $message = Storage::disk('public')->get($file);
                    //dd($message);
                }
            }
            //dd($fotos);
            //https://chats.itstaffer.ru/storage


            $sendParams = [
                'command' => 'send',
                'account_id' => $this->automate->account_id,
                'peer_id' => $channelTo->channel,
                'message' => $message,
                'photos' => $strFotos,
            ];

            dump($sendParams);

            if (empty($account->blocked_seconds)) {
                CommandsJob::dispatch($sendParams);
            } else {
                CommandsJob::dispatch($sendParams)
                    ->delay(Carbon::parse($account->updated_at)
                        ->addSeconds($account->blocked_seconds)
                        ->diffInSeconds(now())
                    );
            }


//                if (!empty($params['limit']) && $cnt >= $params['limit']) {
//                    break;
//                }
        }

        echo "T"; //"Репост сообщения {$this->message->id} с канала {$channelFrom->name} на канал {$channelTo->name} выполнен.\r\n";
        return true;
    }
}
