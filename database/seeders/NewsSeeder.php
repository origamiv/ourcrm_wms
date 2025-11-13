<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\News;
use Illuminate\Database\Seeder;

final class NewsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        News::factory()->count(10)->create();
    }
}
