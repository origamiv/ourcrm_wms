<?php

declare(strict_types=1);

namespace App\Console;

use App\Models\Posting;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\Installer\Models\Feature;
use Modules\Installer\Models\Module;

final class PostingCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'chats:posting';

    /**
     * The console command description.
     */
    protected $description = 'Отправляет сообщения по расписанию';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info(now()->toDateTimeString() . ' Старт');

        $client = new Client();
        $posts = Posting::query()->where('status', 0)->get();
        foreach ($posts as $post) {
            if (Carbon::now() > $post->date_send) {
                $this->sendPost($client, $post);
            }
        }
        $this->info(now()->toDateTimeString() . ' завершение');

        //
//        curl -X 'POST' \
//  'https://messenger.ourcrm.ru/api/messenger/channel/4112/send' \
//  -H 'accept: application/json' \
//  -H 'Content-Type: multipart/form-data' \
//  -F 'account_id=33' \
//  -F 'message=ffff' \
//  -F 'reply_to_message_id=0'
    }

    /**
     * @param Client $client
     * @param mixed $post
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendPost(Client $client, mixed $post): void
    {
        $response = $client->post('https://messenger.ourcrm.ru/api/messenger/channel/' . $post->channel_id . '/send', [
            //'headers' => $headers,
            'multipart' => [[
                'name' => 'account_id',
                'contents' => $post->account_id,
            ], [
                'name' => 'message',
                'contents' => $post->message,
            ],[
                'name' => 'parse_mode',
                'contents' => 'html',
            ],
            ]
        ]);

        $post->status = 1;
        $post->save();
    }


}
