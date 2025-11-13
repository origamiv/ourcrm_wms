<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\OpenAIAssistantHelper;
use App\Models\AIModel;
use App\Models\Assistant;
use App\Models\AssistantChat;
use App\Models\AssistantDialog;
use App\Services\CailaService;
use GuzzleHttp\Client;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AIAnswerJob implements ShouldQueue
{
    use Queueable;

    public $message;
    public $chat;
    public $direction;
    public $threadId;
    public $assistant;
    public OpenAIAssistantHelper $helper;
    public CailaService $cailaService;

    /**
     * Create a new job instance.
     */
    public function __construct($message, $chat, $direction, OpenAIAssistantHelper $helper, CailaService $cailaService)
    {
        $this->message = $message;
        $this->chat = $chat;
        $this->direction = $direction;
        $this->helper = $helper;
        $this->threadId = null;
        $this->cailaService = $cailaService;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //$this->chat = $event->assistantChat;
        $chat = $this->chat;
        $this->threadId = $chat->ext_thread_id;
        $threadId = $this->threadId;

        $assistant = Assistant::find($chat->assistant_id);
        $this->assistant = $assistant;
        if (! $assistant) {
            return;
        }

        // dd($this->threadId);

        $dialog = AssistantDialog::create([
            'assistant_chat_id' => $this->chat->id,
            'assistant_id' => $assistant->id,
            'account_id' => $this->chat->account_id,
            'channel_id' => $this->chat->channel_id,
            'query' => $this->message->message,
        ]);

        // 1. Отправляем сообщение пользователя

        $resultCmd = $this->command($this->message->message);
        $threadId = $this->threadId;
        $chat = $this->chat;

        if ($resultCmd['is_send'] === 1) {
            $this->sendQuestion($this->message->message);

            // dump($r);

            // 2. Запускаем ассистента
            $runData = $this->helper->post("threads/{$threadId}/runs",
                [
                    'assistant_id' => $assistant->ext_id,
                ]
            );
            // dump($runData);

            $runId = $runData['id'] ?? null;

            /** @var AssistantDialog ext_run_id */
            $dialog->ext_run_id = $runId;
            $dialog->status = AssistantDialog::STATUS_IN_PROCESS;
            $dialog->save();

            // 4. Получаем ответ
            $attempt = 0;
            $isOK = false;
            // dd(3433);

            // ждем сообщения с role=assistant
            do {
                sleep(5);

                $runData = $this->helper->get("threads/{$threadId}/runs/".$runId);
                $aiModel = AIModel::query()->where('name', $runData['model'])->first();

                // dump($runData);
                $dialog->ext_run_data = $runData;
                $dialog->total_tokens = $runData['usage']['total_tokens'];
                $dialog->price = $runData['usage']['prompt_tokens'] * $aiModel->price_prompt + $runData['usage']['completion_tokens'] * $aiModel->price_complete;
                $dialog->save();

                $r = $this->getAnswer($attempt);
                // dump($r);
                $isOK = $r['success'];
                if (! $isOK) {
                    $attempt = $r['attempt'];
                }
            } while ($attempt < 2 && ! $isOK);

            if ($isOK) {
                $answer = $r['content'][0]['text']['value'] ?? null;

                // dd($answer);
                $resultCmd = $this->command($answer);
                $threadId = $this->threadId;
                $chat = $this->chat;

                $dialog->answer = $answer;
                $dialog->status = AssistantDialog::STATUS_OK;
            } else {
                $dialog->status = AssistantDialog::STATUS_ERROR;
            }
            $dialog->save();

            $cnt = AssistantDialog::query()
                ->where('assistant_chat_id', $this->chat->id)
                ->count();
            $this->chat->cnt = $cnt;
            $this->chat->save();

            if ($resultCmd['is_send'] === 1) {
                $this->sendToMessenger($this->message->account_id, $this->message->channel_id, $answer);
            } elseif ($resultCmd['is_send'] === 3) {
                $this->sendToMessenger($this->message->account_id, $this->message->channel_id, $resultCmd['answer']);
            }
        } elseif ($resultCmd['is_send'] === 3) {
            $this->sendToMessenger($this->message->account_id, $this->message->channel_id, $resultCmd['answer']);
        }
    }

    public function command($message)
    {
        $message = trim($message);
        $message = mb_strtolower($message);
        $is_send = 1;
        if ($message === '/restart') {
            $this->chat->status = 2;
            $this->chat->save();

            $chatData = $this->chat->toArray();
            $chatData['status'] = 1;
            $chatData['cnt'] = 0;
            $chatData['created_at'] = now();

            // $automate->name. ' '.
            $chat = AssistantChat::query()->create($chatData);
            $this->chat = $chat;
            $data=$this->cailaService->post('/threads', [
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Здравствуйте',
                    ],
                ],
            ]);
            $ext_thread_id = $data['id'];
            $chat->ext_thread_id = $ext_thread_id;
            $chat->save();
            $this->threadId = $ext_thread_id;
            $is_send = 3;
            $answer = '----------- RESTART '.date('Y-m-d H:i:s').'-----------';
        }

        if ($message === '/end') {
            $this->chat->status = 2;
            $this->chat->save();
            $is_send = 3;
            $answer = '----------- END '.date('Y-m-d H:i:s').'-----------';
        }

        if ($message === '/endbot') {
            $this->chat->status = 2;
            $this->chat->save();
            $is_send = 2;
            $answer = '----------- ENDBOT '.date('Y-m-d H:i:s').'-----------';
        }

        $result = [
            'is_send' => $is_send,
            'answer' => $answer,
        ];

        return $result;
    }

    public function getAnswer($attempt = 0)
    {
        $attempt = $attempt + 1;
        $result = [];
        $threadId = $this->threadId;
        try {
            $messagesData = $this->helper->get("threads/{$threadId}/messages");
            dump($messagesData);
            $role = $messagesData['data'][0]['role'];
            $content = $messagesData['data'][0]['content'];

            $role1 = str_replace('assistant', '', $role);

            // dd($role, $content, $role1);

            if ($role !== $role1 && count($content) > 0) {
                $result['success'] = true;
                $result['content'] = $content;
            } else {
                $result['success'] = false;
                $result['attempt'] = $attempt;
            }
        } catch (\Exception $e) {
            $result['success'] = false;
            $result['attempt'] = $attempt;
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    public function sendQuestion($message)
    {
        $threadId = $this->threadId;
        $this->helper->post("threads/{$threadId}/messages",
            [
                'role' => 'user',
                'content' => $message,
            ]);
    }

    public function sendToMessenger($account_id, $channel_id, $answer)
    {
        // $channel = $event->message->channel_id

        $url = 'https://messenger.ourcrm.ru/api/messenger/channel/'.$channel_id.'/send';
        // $answer = "Да вы охуели!";
        // dd($answer);

        try {
            $g = new Client();
            $r = $g->post($url, [
                'multipart' => [
                    [
                        'name' => 'account_id',
                        'contents' => $account_id,
                    ], [
                        'name' => 'message',
                        'contents' => $answer,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            dump($e->getMessage());
        }
    }

}
