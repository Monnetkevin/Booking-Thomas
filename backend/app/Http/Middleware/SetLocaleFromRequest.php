<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * La SPA envoie Accept-Language (fr ou en) selon la langue de la page : messages d'erreur
 * et contenus traduits (nom, description des logements…) sont rendus dans cette langue.
 */
class SetLocaleFromRequest
{
    /** @var list<string> */
    public const SUPPORTED = ['fr', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->getPreferredLanguage(self::SUPPORTED) ?? config('app.locale');
        app()->setLocale(in_array($locale, self::SUPPORTED, true) ? $locale : 'fr');

        $response = $next($request);
        $response->headers->set('Content-Language', app()->getLocale());
        $response->setVary('Accept-Language', false);

        return $response;
    }
}
