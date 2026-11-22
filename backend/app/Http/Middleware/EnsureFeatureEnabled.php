<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage : ->middleware('feature:platform_import'). Une fonctionnalité désactivée
 * (config/features.php) répond 404, comme si la route n'existait pas.
 */
class EnsureFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        abort_unless((bool) config("features.{$feature}"), 404);

        return $next($request);
    }
}
