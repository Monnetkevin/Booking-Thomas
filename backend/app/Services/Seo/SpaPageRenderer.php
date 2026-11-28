<?php

namespace App\Services\Seo;

use App\Support\SiteUrl;
use RuntimeException;

/**
 * index.html du site (frontend/dist), avec la langue et les balises de la page demandée
 * à la place du titre générique.
 */
class SpaPageRenderer
{
    public function render(PageMeta $meta): string
    {
        $path = (string) config('booking.spa_index');
        $html = is_file($path) ? file_get_contents($path) : false;
        if ($html === false) {
            throw new RuntimeException("index.html du site introuvable ($path) : lancer « npm run build » dans frontend.");
        }

        $image = $meta->image !== null && str_starts_with($meta->image, '/') ? SiteUrl::base().$meta->image : $meta->image;
        $tags = view('seo.page-meta', [
            'meta' => $meta,
            'image' => $image,
            'urls' => ['fr' => SiteUrl::localized($meta->path, 'fr'), 'en' => SiteUrl::localized($meta->path, 'en')],
        ])->render();

        $html = preg_replace('#<title>.*?</title>#s', trim($tags), $html, 1) ?? $html;

        return preg_replace('#<html lang="[a-z]+"#', '<html lang="'.$meta->locale.'"', $html, 1) ?? $html;
    }
}
