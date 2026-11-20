<?php

namespace App\Http\Responses;

use Illuminate\Http\Response;

/** Réponse de téléchargement d'un fichier CSV, jamais mise en cache (données privées). */
final class CsvDownload
{
    /**
     * @param  string  $basename  nom sans extension ni date, ex. « reservations »
     */
    public static function make(string $content, string $basename): Response
    {
        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$basename.'-'.now()->format('Y-m-d').'.csv"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
