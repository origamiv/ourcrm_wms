<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\AccessService;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'wms';

    public function share(Request $request): array
    {
        $user = $request->user();

        return [...parent::share($request), 'auth' => $user ? [
            'id' => (string) $user->id, 'name' => $user->name, 'tenant_id' => $user->tenant_id,
            'is_admin' => app(AccessService::class)->isAdmin($user),
        ] : null, 'cacheVersion' => config('wms.cache_version'), 'csrfToken' => csrf_token()];
    }
}
