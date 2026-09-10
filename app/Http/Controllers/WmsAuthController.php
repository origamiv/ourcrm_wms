<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Http\Requests\LoginRequest;
use App\Services\AuthenticationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Авторизация WMS. */
final class WmsAuthController extends BaseApiController
{
    /**
     * Получить Bearer-токен по email и паролю.
     *
     * @unauthenticated
     */
    public function token(LoginRequest $request, AuthenticationService $auth): JsonResponse
    {
        $user = $auth->authenticate((string) $request->string('email'), (string) $request->string('password'), $request->ip());
        $token = $user->createToken('wms:'.$user->credentialFingerprint(), ['*'], now()->addHours(12));

        return response()->json(['token' => $token->plainTextToken, 'token_type' => 'Bearer', 'expires_at' => $token->accessToken->expires_at]);
    }

    public function revoke(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['success' => true]);
    }
}
