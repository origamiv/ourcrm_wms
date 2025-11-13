<?php

namespace App\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DbConnectionsCommand extends Command
{
    protected $signature = 'db:connections {count_idle?}';
    protected $description = 'Показать статистику подключений к PostgreSQL';

    public function handle()
    {
        $count_idle = $this->argument('count_idle') ?? 0;

        while(1) {
            try {
                $result = DB::selectOne("
                SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN state = 'active' THEN 1 ELSE 0 END) AS active,
                    SUM(CASE WHEN state = 'idle in transaction' THEN 1 ELSE 0 END) AS idle_in_transaction,
                    SUM(CASE WHEN state = 'idle' THEN 1 ELSE 0 END) AS idle
                FROM pg_stat_activity
            ");

                $maxConnections = DB::selectOne("SHOW max_connections");

                $this->info("🔌 Подключения к БД:");
                $this->line("Максимально допустимые подключения: {$maxConnections->max_connections}");
                $this->line("Всего подключений: {$result->total}");
                $this->line("Активные подключения: {$result->active}");
                $this->line("Ожидающие (idle): {$result->idle}");
                $this->line("Ожидающие в транзакциях (idle): {$result->idle_in_transaction}");
                $this->line("Количество для уничтожения ожидающих (count_idle): {$count_idle}");

                if ($count_idle > 0) {
                    if ($result->idle > $count_idle) {
                        $result = DB::selectOne("SELECT pg_terminate_backend(pid)
FROM pg_stat_activity
WHERE state = 'idle' or state = 'idle in transaction'
  AND pid <> pg_backend_pid();");
                        //dump($result);


                        $result = DB::selectOne("
                SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN state = 'active' THEN 1 ELSE 0 END) AS active,
                    SUM(CASE WHEN state = 'idle' THEN 1 ELSE 0 END) AS idle
                FROM pg_stat_activity
            ");

                        $this->line('--------------------------');
                        $this->line("Максимально допустимые подключения: {$maxConnections->max_connections}");
                        $this->line("Всего подключений: {$result->total}");
                        $this->line("Активные подключения: {$result->active}");
                        $this->line("Ожидающие (idle): {$result->idle}");

                    }
                }

            } catch (\Throwable $e) {
                $this->error("Ошибка: " . $e->getMessage());
            }
        }
    }
}
