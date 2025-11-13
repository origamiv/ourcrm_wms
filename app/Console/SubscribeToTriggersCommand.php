<?php

namespace App\Console;

//use App\Events\OrderCreated;
//use App\Events\OrderDeleted;
//use App\Events\OrderUpdated;
//use App\Events\UserCreated;
//use App\Events\UserDeleted;
//use App\Events\UserUpdated;
use App\Events\MessageAddedEvent;
use App\Jobs\CommandsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;

/**
 * Class SubscribeToTriggers
 *
 * @package App\Console\Commands
 */
class SubscribeToTriggersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'triggers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen for changes on database and update the platform accordingly';

    /**
     *  Tables to synchronize.
     *
     * @var array
     */
    protected $tables;

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {
        // Listen to the 'my_channel' notifications
        $this->info('Starting');

        $data=[
            'command' =>'send',
            'account_id' => 2,
            'peer_id' => 476390579,
            'message' => 'давай увидимся',
        ];
        dump($data);
        CommandsJob::dispatch($data);
        //dd(123);


//        SendMessageJob::dispatch([
//            'command' =>'add_users_to_group',
//            'account_id' => 47,
//            'group_id' => 4866982502,
//            'users'=>array_values([
//                "@vsmorodinsky",
//                "+79651591199",
//                5772012875
//            ])
//        ]);

//        SendMessageJob::dispatch([
//            'command' =>'create_group',
//            'account_id' => 47,
//            'title' => 'Вязанные тапки',
//            'users'=>array_values([
////                "@username1",
//                "+79651591199",
//                5772012875
//            ])
//        ]);

//        return ;

        $config = config('database.connections.pgsql');

        $host     = $config['host']     ?? '127.0.0.1';
        $port     = $config['port']     ?? 5432;
        $dbname   = $config['database'] ?? 'postgres';
        $user     = $config['username'] ?? 'postgres';
        $password = $config['password'] ?? '';

        // Собираем строку подключения
        $connString = "host={$host} port={$port} dbname={$dbname} user={$user} password={$password}";

        dump($connString);

        $conn = pg_connect($connString);
        dump($conn);

        if (!$conn) {
            $this->error("❌ Не удалось подключиться к PostgreSQL");
            return;
        }

        pg_query($conn, 'LISTEN events');


        // Forever loop
        while (true) {

            {
                //$notification = $pdo->pgsqlGetNotify(PDO::FETCH_ASSOC, 10000);
                $notification = pg_get_notify($conn, PGSQL_ASSOC);

                if ($notification) {
                    $payload = json_decode($notification['payload'], true);


                    //dump($payload);
                    echo '.';
                    $message_id = $payload['data']['id'];

//                if ($payload['table']=='messages' && $payload['data']['account_id']==47 && $payload['data']['channel_id']==14166403) {
//                    dump($payload['data']);
//                }
                    MessageAddedEvent::dispatch($message_id);
                    //echo '.';
                    //$this->info('Received notification: ' . json_encode($notification, JSON_THROW_ON_ERROR));
                }

                \Artisan::call('event:clear');
                \Artisan::call('event:cache');
            }

        }
    }
}
