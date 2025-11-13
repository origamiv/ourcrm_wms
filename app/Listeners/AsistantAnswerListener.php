<?php

declare(strict_types=1);

// app/Listeners/MessageAddedListener.php

namespace App\Listeners;

use App\Events\AssistantMessageDirectionCommandEvent;
use App\Events\AssistantMessageDirectionInEvent;
use App\Events\AssistantMessageDirectionOutEvent;
use App\Helpers\OpenAIAssistantHelper;
use App\Jobs\CommandsJob;
use App\Models\Account;
use App\Models\AIModel;
use App\Models\Assistant;
use App\Models\AssistantChat;
use App\Models\AssistantDialog;
use App\Models\AssistantDocument;
use App\Models\Channel;
use App\Models\Message;
use App\Services\CailaService;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;

final class AsistantAnswerListener
{
    public $threadId;

    public $chat;

    public $assistant;
    public $isRAG;
    public $automate;

    private OpenAIAssistantHelper $helper;
    public CailaService $cailaService;

    public function __construct(OpenAIAssistantHelper $helper, CailaService $cailaService)
    {
        $this->helper = $helper;
        $this->threadId = null;
        $this->cailaService = $cailaService;
        $this->isRAG = false;
    }

    public function command($message)
    {
        $answer = '';
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
            $answer = '----------- RESTART ' . date('Y-m-d H:i:s') . '-----------';
        }

        if ($message === '/end') {
            $this->chat->status = 2;
            $this->chat->save();
            $is_send = 3;
            $answer = '----------- END ' . date('Y-m-d H:i:s') . '-----------';
        }

        if ($message === '/endbot') {
            $this->chat->status = 2;
            $this->chat->save();
            $is_send = 2;
            $answer = '----------- ENDBOT ' . date('Y-m-d H:i:s') . '-----------';
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
            $messagesData = $this->cailaService->get("/threads/{$threadId}/messages");
            dump($messagesData);
            $role = $messagesData['data'][0]['role'];
            $content = $messagesData['data'][0]['content'];

            $role1 = str_replace('assistant', '', $role);

            //dd($role, $content, $role1);

            if ($role !== $role1 && count($content) > 0) {
                $result['success'] = true;
                $result['content'] = $content;
            } else {
                $result['success'] = false;
                $result['attempt'] = $attempt;
            }
        } catch (Exception $e) {
            $result['success'] = false;
            $result['attempt'] = $attempt;
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    public function channel($channel_id)
    {
        return Channel::query()->find($channel_id)->channel;
    }
    public function sendQuestion($message, $context = null)
    {
        $threadId = $this->threadId;

        if (!empty($context)) {
            $sendMessage= 'Предпочтительно отвечать из контекста. '.
                'Контекст: ' . $context. ' Сообщение: '. $message;
        }
        else {
            $sendMessage = $message;
        }

        $this->helper->post("threads/{$threadId}/messages",
            [
                'role' => 'user',
                'content' => $sendMessage,
            ]);
    }

    public function runFunction($data)
    {

        /**
         * [
         * "type" => "submit_tool_outputs"
         * "submit_tool_outputs" => array:1 [
         * "tool_calls" => array:1 [
         * 0 => array:3 [
         * "id" => "call_ViQDGLm9tGc8WFbCbW88V6lP"
         * "type" => "function"
         * "function" => array:2 [
         * "name" => "send_photos"
         * "arguments" => "{}"
         * ]
         * ]
         * ]
         * ]
         * ]
         *
         */

        dump($data);
        $r = [];
        $r['success'] = true;
        $r['cancel_run'] = true;

        $type = $data['type'];
        $r['type'] = $type;
        $args = $data[$type]['tool_calls'][0];
        $r['call_id'] = $data['submit_tool_outputs']['tool_calls'][0]['id'];

        $func_name = $args['function']['name'];
        $func_args = json_decode($args['function']['arguments'], true);
        $func_args['automate'] = $this->automate;
        $func_args['assistant_chat'] = $this->chat->toArray();
        $result = (function_exists($func_name)) ? $func_name($func_args) : defaultFunc();

        $r = array_merge($r, $result);
        dump($r);
        return $r;
    }

    public function sendToMessenger($account_id, $channel_id, $answer)
    {
        $data=[
            'command' =>'send',
            'account_id' => $account_id,
            'peer_id' => $channel_id,
            'message' => $answer,
        ];
        dump($data);
        CommandsJob::dispatch($data);

        Message::query()->create([
            'account_id' => $account_id,
            'channel' => $channel_id,
            'channel_id' => Channel::query()->where('channel','=',$channel_id)->first()->id,
            'message' => $answer,
            'messenger_id' => Account::query()->find($account_id)->messenger_id,
            'napr'=>Message::DIRECTION_OUT,
            'msg_date'=>now()->toDateTimeString(),
        ]);


//        SendMessageJob::dispatch([
//            'command' =>'create_group',
//            'account_id' => $account_id,
//            'title' => 'Вязанные тапки',
//            'users'=>[
////                "@username1",
////                "+79991112233",
//                5772012875
//            ]
//        ]);


//        SendMessageJob::dispatch([
//            'command' =>'create_channel',
//            'account_id' => $account_id,
//            'title' => 'Вязанные тапки',
//            'about' =>'О премудростях вязания',
//        ]);
    }

    public function sendToMessengerAPI($account_id, $channel_id, $answer)
    {
        // $channel = $event->message->channel_id

        $url = 'http://212.74.231.121:8000/api/messenger/channel/' . $channel_id . '/send';
        //$url = 'https://messenger.ourcrm.ru/api/messenger/channel/' . $channel_id . '/send';
        // $answer = "Да вы охуели!";
        // dd($answer);

        try {
            $g = new Client();
            $r = $g->post($url, [
                'json' => [
                    'account_id' => $account_id,
                    'peer_id' => $channel_id,
                    'message' => $answer,
                ]
            ]);

//            $r = $g->post($url, [
//                'multipart' => [
//                    [
//                        'name' => 'account_id',
//                        'contents' => $account_id,
//                    ], [
//                        'name' => 'message',
//                        'contents' => $answer,
//                    ],
//                ],
//            ]);
        } catch (Exception $e) {
            dump($e->getMessage());
        }
    }

    public function handle(AssistantMessageDirectionInEvent|
                           AssistantMessageDirectionOutEvent|
                           AssistantMessageDirectionCommandEvent $event): void
    {
        $this->automate=$event->automate->toArray();
        $this->chat = $event->assistantChat;
        $chat = $this->chat;
        //if ($chat->cnt>20) {return ; }

        $this->threadId = $event->assistantChat->ext_thread_id;
        $threadId = $this->threadId;

        $assistant = Assistant::find($event->assistantChat->assistant_id);
        $this->assistant = $assistant;
        if (!$assistant) {
            return;
        }

        $documents=AssistantDocument::query()
            ->where('assistant_id','=',$assistant->id)
            ->whereNotNull('embedding')
            ->get();

        $this->isRAG = (count($documents)>0) ? true : false;

        // dd($this->threadId);

        $dialog = AssistantDialog::create([
            'assistant_chat_id' => $this->chat->id,
            'assistant_id' => $assistant->id,
            'account_id' => $this->chat->account_id,
            'channel_id' => $this->chat->channel_id,
            'query' => $event->message->message,
        ]);

        // 1. Отправляем сообщение пользователя

        /**
         *  $resultCmd['is_send'] - если получена 1, то значит можно отправить вопрос ассистенту,
         *                          если получено 2 - то значит нельзя.
         */
        $resultCmd = $this->command($event->message->message);
        $threadId = $this->threadId;
        $chat = $this->chat;

        if ($resultCmd['is_send'] === 1) {
            if ($this->isRAG) {
                $data=$this->cailaService->post('/embeddings', [
                    'model' => 'text-embedding-3-small', // или vectorizer-caila-roberta
                    'input' => $event->message->message,
                ]);

                $embeddingData = $data['data'][0]['embedding'] ?? null;
                if (!empty($embeddingData)) {
                    //dd($embeddingData);

                    $vector = '[' . implode(',', array_map(fn($v) => sprintf('%F', (float)$v), $embeddingData)) . ']';

                    $sql = "
    SELECT title, content, 1 - (embedding <=> '{$vector}') AS similarity
    FROM messenger.assistants_documents
    WHERE assistant_id = {$assistant->id}
      AND embedding IS NOT NULL
      AND deleted_at IS NULL
    ORDER BY embedding <=> '{$vector}'
    LIMIT 5
";

                    $docs = collect(DB::select($sql));

                    $context = '';
                    foreach ($docs as $doc) {
                        $context .= $doc->title . ':' . $doc->content . "\n\n";
                    }
                    $context = trim($context);
                }
                else {$context = null;}
            }
             else {$context = null;}

            $this->sendQuestion($event->message->message, $context);

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

                $runData = $this->helper->get("threads/{$threadId}/runs/" . $runId);
                $aiModel = AIModel::query()->where('name', $runData['model'])->first();

                if (!empty($runData['required_action'])) {
                    $r = $this->runFunction($runData['required_action']);
                    if ($r['cancel_run']) {
                        $this->helper->post("threads/{$threadId}/runs/" . $runId . '/cancel', []);
                    } else {
                        $this->helper->post("threads/{$threadId}/runs/" . $runId . '/submit_tool_outputs', [
                            "tool_outputs" => [
                                [
                                    "tool_call_id" => $r['call_id'],
                                    "output" => $r['answer']
                                ]
                            ]
                        ]);
                    }
                } else {
                    try {
                        $dialog->ext_run_data = $runData;
                        $dialog->total_tokens = $runData['usage']['total_tokens'];
                        $dialog->price = $runData['usage']['prompt_tokens'] * $aiModel->price_prompt + $runData['usage']['completion_tokens'] * $aiModel->price_complete;
                        $dialog->save();
                        $r = $this->getAnswer($attempt);
                    } catch (\Exception $e) {
                        $r['success']=false;
                        echo $e->getMessage();
                        dump($runData);
                    }
                }
                $isOK = $r['success'];
                if (!$isOK ?? !empty($r['attempt'])) {
                    $attempt = !empty($r['attempt']) ? $r['attempt'] : 1;
                }
            } while ($attempt < 2 && !$isOK);

            if ($isOK) {
                dump($r);
                $contents = $r['content'];
                foreach ($contents as $content) {
                    $answer = $content['text']['value'] ?? null;
                    $resultCmd = $this->command($answer);
                    if (($resultCmd['is_send'] === 1) && (!empty($answer))) {
                        $this->sendToMessenger($event->message->account_id, $this->channel($event->message->channel_id), $answer);
                    }
                }

                //$answer = $r['content'][0]['text']['value'] ?? null;

                //dump($answer);
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

            if ($resultCmd['is_send'] === 3) {
                $this->sendToMessenger($event->message->account_id, $this->channel($event->message->channel_id), $resultCmd['answer']);
            }
        } elseif ($resultCmd['is_send'] === 3) {
            $this->sendToMessenger($event->message->account_id, $this->channel($event->message->channel_id), $resultCmd['answer']);
        }
    }
}
