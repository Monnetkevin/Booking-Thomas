<?php

namespace App\Services\Seo;

use App\Services\Catalog\PublicCatalogService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

/**
 * Balises d'une page publique d'après son URL, pour les aperçus de liens (WhatsApp,
 * Facebook) qui n'exécutent pas JavaScript. Mêmes règles que le composant Seo du site :
 * titre « Page · Nom du site », fiche = nom, accroche et première photo du logement.
 */
class PageMetaService
{
    /** Pages fixes : chemin sans préfixe de langue → clé des textes (lang/{fr,en}/seo.php). */
    private const STATIC_PAGES = [
        '/' => 'home',
        '/logements' => 'apartments',
        '/contact' => 'contact',
        '/avis' => 'reviews',
        '/mentions-legales' => 'legal',
        '/cgv' => 'terms',
        '/confidentialite' => 'privacy',
    ];

    public function __construct(private readonly PublicCatalogService $catalog) {}

    /** @param  string  $uri  chemin demandé (« en/logements/le-cocon », « / ») */
    public function forUri(string $uri): PageMeta
    {
        $path = '/'.trim($uri, '/');
        $locale = 'fr';
        if ($path === '/en' || str_starts_with($path, '/en/')) {
            $locale = 'en';
            $path = '/'.ltrim(substr($path, 3), '/');
        }

        if (isset(self::STATIC_PAGES[$path])) {
            $key = self::STATIC_PAGES[$path];

            return $this->page($locale, $path, $key, image: $key === 'home' ? $this->homeImage($locale) : null);
        }

        if (preg_match('#^/logements/([^/]+)$#', $path, $match)) {
            return $this->apartment($locale, $path, $match[1]);
        }

        // Liens personnels reçus par e-mail : jamais indexés.
        if (preg_match('#^/reservation/[^/]+$#', $path)) {
            return $this->page($locale, $path, 'booking', noIndex: true);
        }
        if (preg_match('#^/avis/[^/]+$#', $path)) {
            return $this->page($locale, $path, 'review_form', noIndex: true);
        }

        return $this->notFound($locale, $path);
    }

    private function apartment(string $locale, string $path, string $slug): PageMeta
    {
        try {
            $detail = $this->catalog->detail($slug, $locale);
        } catch (ModelNotFoundException) {
            return $this->notFound($locale, $path);
        }

        $description = $detail['tagline']
            ?? ($detail['description'] !== null ? Str::limit((string) $detail['description'], 155, '') : null)
            ?? __('seo.pages.apartments.description', locale: $locale);

        return new PageMeta(
            locale: $locale,
            path: $path,
            title: $this->fullTitle((string) $detail['name']),
            description: (string) $description,
            image: $detail['photos'][0]['sources']['large']['webp'] ?? null,
        );
    }

    /** Accueil : photo de couverture du premier logement, comme le site. */
    private function homeImage(string $locale): ?string
    {
        foreach ($this->catalog->summaries($locale) as $summary) {
            if ($summary['cover'] !== null) {
                return $summary['cover']['sources']['large']['webp'] ?? null;
            }
        }

        return null;
    }

    private function notFound(string $locale, string $path): PageMeta
    {
        return $this->page($locale, $path, 'not_found', noIndex: true, status: 404);
    }

    private function page(string $locale, string $path, string $key, ?string $image = null, bool $noIndex = false, int $status = 200): PageMeta
    {
        return new PageMeta(
            locale: $locale,
            path: $path,
            title: $this->fullTitle(__("seo.pages.$key.title", locale: $locale)),
            description: __("seo.pages.$key.description", locale: $locale),
            image: $image,
            noIndex: $noIndex,
            status: $status,
        );
    }

    private function fullTitle(string $title): string
    {
        return $title.' · '.config('app.name');
    }
}
