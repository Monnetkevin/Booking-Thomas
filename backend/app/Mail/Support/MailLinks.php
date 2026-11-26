<?php

namespace App\Mail\Support;

use App\Support\SiteUrl;

/**
 * Liens du site public dans la langue de l'e-mail (les pages anglaises sont sous /en).
 */
final class MailLinks
{
    /**
     * @return array{home: string, contact: string, legal: string}
     */
    public static function forLocale(string $locale): array
    {
        return [
            'home' => self::to('/', $locale),
            'contact' => self::to('/contact', $locale),
            'legal' => self::to('/mentions-legales', $locale),
        ];
    }

    /** Chemin du site public (« /contact ») → URL complète dans la langue voulue. */
    public static function to(string $path, string $locale): string
    {
        $base = SiteUrl::base();
        $prefix = $locale === 'en' ? '/en' : '';
        $path = '/'.ltrim($path, '/');

        return $base.($path === '/' ? ($prefix ?: '/') : $prefix.$path);
    }
}
