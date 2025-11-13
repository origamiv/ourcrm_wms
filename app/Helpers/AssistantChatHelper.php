<?php

namespace App\Helpers;

use App\Models\AssistantChat;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AssistantChatHelper
{
    public function __construct(
        protected OpenAIAssistantHelper $openai
    ) {}

    public function handleDirectionUpdate(AssistantChat $chat): void
    {
        match ($chat->direction) {
            'in' => $this->handleIncoming($chat),
            'inout' => $this->handleOutgoing($chat),
            'command' => $this->handleCommand($chat),
            default => null,
        };
    }

    protected function handleIncoming(AssistantChat $chat): void
    {
        if (!$chat->thread_id || !$chat->assistant_id || !$chat->latest_text) {
            Log::warning('[AssistantChatHelper] Недостаточно данных для отправки входящего сообщения');
            return;
        }

        $this->openai->sendUserMessage($chat->thread_id, $chat->latest_text);
        $run = $this->openai->runAssistant($chat->thread_id, $chat->assistant_id);

        $chat->run_id = $run['id'] ?? null;
        $chat->direction = 'wait';
        $chat->save();
    }

    protected function handleOutgoing(AssistantChat $chat): void
    {
        if (!$chat->thread_id || !$chat->external_chat_id) {
            Log::warning('[AssistantChatHelper] Недостаточно данных для отправки ответа');
            return;
        }

        $response = $this->openai->getLastAssistantMessage($chat->thread_id);
        if (!$response) {
            Log::warning("[AssistantChatHelper] Ответ ассистента пустой");
            return;
        }

        // Отправляем сообщение через API
        $this->sendToMessenger($chat->external_chat_id, $response);

        $chat->direction = 'out';
        $chat->save();
    }

    protected function handleCommand(AssistantChat $chat): void
    {

    }

    protected function sendToMessenger(string $chatId, string $text): void
    {
        try {
            $apiKey = env('MESSENGER_API_KEY');
            $url = 'https://messenger.ourcrm.ru/api/send';

            Http::withToken($apiKey)->post($url, [
                'chat_id' => $chatId,
                'text' => $text,
            ]);
        } catch (\Throwable $e) {
            Log::error('[Messenger Send] ' . $e->getMessage());
        }
    }
}
