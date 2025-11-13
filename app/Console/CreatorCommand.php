<?php

declare(strict_types=1);

namespace App\Console;

use App\Jobs\ProcessTelegramUpdate;
use App\Models\Creator;
use App\Models\Posting;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\Installer\Models\Feature;
use Modules\Installer\Models\Module;

final class CreatorCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'chats:create {id}';

    /**
     * The console command description.
     */
    protected $description = 'Создает сообщения по расписанию';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {

        $this->info(now()->toDateTimeString() . ' Старт');

        ProcessTelegramUpdate::dispatch('message', 'login');

        dd(333);
        $id = $this->argument('id');
        if (empty($id)) {
            return;
        }

        $creator = Creator::query()->find($id);
        $this->createPost($creator->prompt);

        $this->info(now()->toDateTimeString() . ' завершение');
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
            ],
            ]
        ]);

        $post->status = 1;
        $post->save();
    }

    /**
     * @return void
     */
    public function createPost($prompt): void
    {
        $api_key = 'sk-aitunnel-SFQteh0Zj0BS7q8TBYvk2Q9OaDxTKeHK';
        //$api_key = 'sk-aitunnel-xxx'; // Ключ из нашего сервиса
        $api_url = 'https://api.aitunnel.ru/v1/chat/completions';

//        $prompt = 'Напиши пост для телеграмм канала "Вениамин - архитектор порядка в бизнесе".
//        Обьем поста 500 символов, стиль общения делово, живой.
//        Тема поста - Почему нельзя просто нанять подрядчика чтобы в бизнесе все наладилось. Выдай текст в формате HTML для телеграмм, только текст, без дополнительной информации ';

        $data_chat = [
            'model' => 'deepseek-r1',
            'max_tokens' => 50000, // Старайтесь указывать для более точного расчёта цены
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ];

        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data_chat));

        $json = json_decode(curl_exec($ch), true);
        $r = $json['choices'][0]['message']['content'];

        Posting::query()->create([
            'message' => $r,
            'account_id' => 33,
            'channel_id' => 4112,
            'date_send' => Carbon::now()->addMinutes(10)->toDateTimeString(),
            'status' => 0
        ]);
    }
}
