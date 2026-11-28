<?php

namespace App\Http\Controllers;

use App\Services\Seo\PageMetaService;
use App\Services\Seo\SpaPageRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Premier chargement d'une page publique (Nginx le confie à Laravel) : index.html du site
 * avec les balises de la page, lisibles sans JavaScript par les aperçus de liens.
 */
class SpaPageController extends Controller
{
    public function __construct(
        private readonly PageMetaService $pages,
        private readonly SpaPageRenderer $renderer,
    ) {}

    public function __invoke(Request $request): Response
    {
        $meta = $this->pages->forUri($request->path());

        return response($this->renderer->render($meta), $meta->status)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            // Comme index.html : jamais gardé en cache, il référence les fichiers du dernier déploiement.
            ->header('Cache-Control', 'no-cache');
    }
}
