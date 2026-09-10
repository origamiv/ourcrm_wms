<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\AccessService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class EnsureWmsAccess
{
    public function handle(Request $request, Closure $next, string $mode = 'user')
    {
        $user = $request->user();
        if ($request->hasSession() && $request->hasHeader('X-WMS-User') && $user
            && ((string) $request->header('X-WMS-User') !== (string) $user->id
                || rawurldecode((string) $request->header('X-WMS-Tenant')) !== $user->tenant_id)) {
            return response()->json(['message' => 'Учётная запись в браузере изменилась. Выполните вход заново.'], 401);
        }
        $token = $user?->currentAccessToken();
        $fingerprint = $request->hasSession() ? $request->session()->get('wms_credential') : $token?->credential_fingerprint;
        if (! $user || ! app(AccessService::class)->active($user) || ! is_string($fingerprint)
            || ! hash_equals($user->credentialFingerprint(), $fingerprint)) {
            if ($request->hasSession()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return $request->expectsJson() ? response()->json(['message' => 'Требуется вход.'], 401) : redirect()->route('login');
        }
        abort_if($mode === 'admin' && ! app(AccessService::class)->isAdmin($user), 403, 'Недостаточно прав.');
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
