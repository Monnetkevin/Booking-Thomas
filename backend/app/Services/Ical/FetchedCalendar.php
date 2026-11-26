<?php

namespace App\Services\Ical;

/** Contenu d'un flux téléchargé et son ETag (pour la requête conditionnelle suivante). */
final readonly class FetchedCalendar
{
    public function __construct(
        public string $content,
        public ?string $etag,
    ) {}

    public function hash(): string
    {
        return hash('sha256', $this->content);
    }
}
