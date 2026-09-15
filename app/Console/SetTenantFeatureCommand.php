<?php

declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class SetTenantFeatureCommand extends Command
{
    protected $signature = 'tenant:feature {tenant} {feature} {enabled : 0 или 1}';

    protected $description = 'Включить или выключить tenant-фичу';

    public function handle(): int
    {
        $enabled = (int) $this->argument('enabled');
        if (! in_array($enabled, [0, 1], true)) {
            $this->error('Параметр enabled должен быть 0 или 1.');

            return self::INVALID;
        }
        DB::table('main.tenant_settings')->updateOrInsert(
            ['tenant_id' => (string) $this->argument('tenant'), 'name' => (string) $this->argument('feature')],
            ['value' => json_encode(['enabled' => $enabled === 1]), 'updated_at' => now(), 'created_at' => now()],
        );
        $this->info(($enabled ? 'Включена' : 'Выключена').' фича '.(string) $this->argument('feature').'.');

        return self::SUCCESS;
    }
}
