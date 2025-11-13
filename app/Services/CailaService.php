<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\Channel;
use App\Models\ChannelUser;
use App\Models\MessUser;
use DirectoryTree\ImapEngine\Mailbox;
use DirectoryTree\ImapEngine\Message;
use GuzzleHttp\Client;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CailaService
{
    public $client;
    public $token;
    public $baseUrl;

    public function __construct()
    {
        // https://caila.io/api/mlpbilling/account/1000181505/balance
        // https://caila.io/api/mlpbilling/account/1000181505/balance/short

        $this->client = new Client();
        $this->token = env('CAILA_TOKEN');
        $this->baseUrl = 'https://caila.io/api/adapters/openai/v1';

        //https://caila.io/api/adapters/openai/v1/assistants
        //https://api.openai.com/v1/assistants
    }

    public function post($url = '/threads', $data)
    {
        $response = $this->client->post($this->baseUrl . $url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->token,
                'OpenAI-Beta' => 'assistants=v2',
            ],
            'json' => $data,
        ]);

        $body = $response->getBody();
        $data = json_decode($body->getContents(), true);
        return $data;
    }
    public function get($url = '/threads')
    {
        $response = $this->client->get($this->baseUrl . $url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->token,
                'OpenAI-Beta' => 'assistants=v2',
            ],
        ]);

        $body = $response->getBody();
        $data = json_decode($body->getContents(), true);
        return $data;
    }


}
