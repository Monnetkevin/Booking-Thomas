<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Authentification de la SPA par cookie de session (Sanctum, garde « web »).
 * La SPA appelle d'abord GET /sanctum/csrf-cookie, puis POST /api/auth/login.
 */
class SessionController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    public function store(LoginRequest $request): UserResource
    {
        $user = $this->auth->login(
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
            remember: $request->boolean('remember'),
            ip: $request->ip(),
        );

        // Nouvel identifiant de session après connexion (fixation de session).
        $request->session()->regenerate();

        return new UserResource($user);
    }

    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function destroy(Request $request): Response
    {
        $this->auth->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
