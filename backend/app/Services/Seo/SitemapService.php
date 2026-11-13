<?php

namespace App\Services\Seo;

use App\Models\Apartment;
use App\Services\Catalog\PublicCatalogService;
use App\Support\SiteUrl;

/**
 * Plan du site : pages publiques et fiches publiées, chacune en français et en anglais
 * (liens hreflang alternés).
 */
class SitemapService
{
    /** Pages fixes : chemin (sans préfixe de langue) et priorité. */
    private const STATIC_PAGES = [
        '/' => 1.0,
        '/logements' => 0.9,
        '/contact' => 0.5,
        '/mentions-legales' => 0.2,
        '/cgv' => 0.2,
        '/confidentialite' => 0.2,
    ];

    public function __construct(private readonly PublicCatalogService $catalog) {}

    /**
     * @return list<array{fr: string, en: string, lastmod: string|null, priority: float}>
     */
    public function entries(): array
    {
        $entries = [];
        foreach (self::STATIC_PAGES as $path => $priority) {
            $entries[] = $this->entry($path, null, $priority);
        }
        foreach ($this->catalog->published() as $apartment) {
            /** @var Apartment $apartment */
            $entries[] = $this->entry("/logements/{$apartment->slug}", $apartment->updated_at?->toDateString(), 0.8);
        }

        return $entries;
    }

    public function baseUrl(): string
    {
        return SiteUrl::base();
    }

    /**
     * @return array{fr: string, en: string, lastmod: string|null, priority: float}
     */
    private function entry(string $path, ?string $lastmod, float $priority): array
    {
        return [
            'fr' => $this->baseUrl().$path,
            'en' => $this->baseUrl().'/en'.($path === '/' ? '' : $path),
            'lastmod' => $lastmod,
            'priority' => $priority,
        ];
    }
}
