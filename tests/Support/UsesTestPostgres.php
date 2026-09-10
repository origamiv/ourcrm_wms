<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

trait UsesTestPostgres
{
    public function setupPostgres(): void
    {
        if (DB::connection()->getDatabaseName() !== 'wms_test' || config('database.connections.pgsql.host') !== '/var/run/postgresql') {
            throw new RuntimeException('Тесты разрешены только в локальной БД wms_test.');
        }
        DB::beginTransaction();
        DB::unprepared(file_get_contents(base_path('tests/Support/schema.sql')));
        DB::unprepared(file_get_contents(database_path('sql/user_sync.sql')));
        (require database_path('migrations/2026_09_10_000003_create_shared_entity_changes.php'))->up();
        (require database_path('migrations/2026_09_10_000004_move_sync_state_to_public.php'))->up();
        (require database_path('migrations/2026_09_10_000011_create_client_documents.php'))->up();
        config(['wms.sync_page_size' => 2]);
    }

    public function makeUser(array $attributes = [], bool $admin = false): User
    {
        $user = new User;
        $user->forceFill([...['name' => 'Тест', 'email' => uniqid().'@example.test', 'password' => 'Test_password_123', 'status' => 1, 'tenant_id' => 'tenant_a'], ...$attributes])->save();
        if ($admin) {
            $role = DB::table('main.roles')->insertGetId(['slug' => 'admin', 'status' => 1]);
            DB::table('main.role_user')->insert(['role_id' => $role, 'user_id' => $user->id, 'tenant_id' => $user->tenant_id, 'status' => 1]);
        }

        return $user;
    }

    public function loginUser(User $user): void
    {
        $this->actingAs($user)->withSession(['wms_credential' => $user->credentialFingerprint()]);
    }
}
