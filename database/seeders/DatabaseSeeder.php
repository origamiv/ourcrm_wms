<?php

declare(strict_types=1);

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Account;
use App\Models\Channel;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $account = Account::query()->create([
            'name' => str_random(10),
            'status' => 1,
        ]);

        Channel::query()->create([
            'name' => str_random(10),
            'account_id' => $account->id,
            'status' => 1
        ]);
    }
}
