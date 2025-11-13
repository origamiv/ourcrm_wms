<?php

declare(strict_types=1);

namespace App\Helpers;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class OpenAIAssistantHelper
{
    protected Client $client;
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $this->baseUrl = config('services.openai.proxy_url');
        $this->token = config('services.openai.token');

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
                'OpenAI-Beta' => 'assistants=v2',
            ],
        ]);
    }

    public function sendUserMessage(string $threadId, string $message): array
    {
        return $this->post("/threads/{$threadId}/messages", [
            'role' => 'user',
            'content' => $message,
        ]);
    }

    public function runAssistant(string $threadId, string $assistantId): array
    {
        return $this->post("/threads/{$threadId}/runs", [
            'assistant_id' => $assistantId,
        ]);
    }

    public function getRunStatus(string $threadId, string $runId): array
    {
        return $this->get("/threads/{$threadId}/runs/{$runId}");
    }

    public function getLastAssistantMessage(string $threadId): ?string
    {
        $response = $this->get("/threads/{$threadId}/messages");
        return $response['data'][0]['content'][0]['text']['value'] ?? null;
    }

    public function post(string $endpoint, array $data): array
    {
        dump($endpoint, $data);
        try {
            $response = $this->client->post($endpoint, ['json' => $data]);
            $body=$response->getBody();
            $content=$body->getContents();
            return json_decode($content, true);
        } catch (\Exception $e) {
            Log::error('[OpenAI POST] ' . $e->getMessage());
            dd($e);
            return ['error' => $e->getTrace()];
        }
    }

    public function get(string $endpoint): array
    {
        try {
            $response = $this->client->get($endpoint);
            $body=$response->getBody();
            $content=$body->getContents();
            return json_decode($content, true);
        } catch (\Exception $e) {
            Log::error('[OpenAI GET] ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}
