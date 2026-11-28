<?php

namespace App\Services\Seo;

/** Balises d'une page publique : titre complet, description, URL, image et indexation. */
final readonly class PageMeta
{
    public function __construct(
        public string $locale,
        /** Chemin sans préfixe de langue (« /logements/le-cocon »). */
        public string $path,
        public string $title,
        public string $description,
        public ?string $image = null,
        public bool $noIndex = false,
        /** 404 : page ou logement inconnu (le site affiche « Page introuvable »). */
        public int $status = 200,
    ) {}
}
