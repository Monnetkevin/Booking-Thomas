<?php

namespace App\Http\Controllers;

use App\Services\Seo\SitemapService;
use Illuminate\Http\Response;

/** Plan du site et consignes aux robots, servis par Laravel sous le domaine du site. */
class SeoController extends Controller
{
    public function __construct(private readonly SitemapService $sitemap) {}

    public function sitemap(): Response
    {
        return response()
            ->view('seo.sitemap', ['entries' => $this->sitemap->entries()])
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /api/',
            '',
            'Sitemap: '.$this->sitemap->baseUrl().'/sitemap.xml',
        ];

        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
