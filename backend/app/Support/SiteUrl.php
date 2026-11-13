<?php

namespace App\Support;

/**
 * Adresse publique du site (SPA, API sous /api, calendriers sous /ical), réglée par
 * FRONTEND_URL : liens des e-mails, plan du site, URL données aux plateformes.
 */
final class SiteUrl
{
    /** « https://exemple.fr », sans barre finale. */
    public static function base(): string
    {
        return rtrim((string) config('booking.frontend_url'), '/');
    }

    /** Chemin du site (« /admin ») → URL complète. */
    public static function to(string $path): string
    {
        return self::base().'/'.ltrim($path, '/');
    }

    /** Nom de domaine seul (« exemple.fr »). */
    public static function host(): string
    {
        return (string) parse_url(self::base(), PHP_URL_HOST) ?: 'localhost';
    }
}
