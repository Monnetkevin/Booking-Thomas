<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage : ->middleware('role:admin') ou 'role:admin,housekeeping'.
 * Un compte désactivé est déconnecté immédiatement, même avec une session ouverte.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        if (! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            abort(401);
        }

        abort_unless(in_array($user->role->value, $roles, true), 403);

        return $next($request);
    }
}
