<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Authentification de la SPA par cookie de session (Sanctum, garde « web »).
 * La SPA appelle d'abord GET /sanctum/csrf-cookie, puis POST /api/auth/login.
 */
class SessionController extends Controller
{
    public function store(LoginRequest $request): UserResource
    {
        $request->authenticate();
        $request->session()->regenerate();

        return new UserResource($request->user());
    }

    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function destroy(Request $request): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
