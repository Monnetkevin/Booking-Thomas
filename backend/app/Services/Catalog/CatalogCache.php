<?php

namespace App\Services\Catalog;

use Closure;
use Illuminate\Contracts\Cache\Repository;

/**
 * Cache des données publiques des logements (liste, fiches), une minute au plus.
 * Vidé à chaque modification d'un logement, d'une photo, d'un tarif ou d'un équipement.
 */
class CatalogCache
{
    public const TAG = 'public-catalog';

    public const TTL_SECONDS = 60;

    public function __construct(private readonly Repository $cache) {}

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function remember(string $key, Closure $callback): mixed
    {
        return $this->cache->tags([self::TAG])->remember($key, self::TTL_SECONDS, $callback);
    }

    public function flush(): void
    {
        $this->cache->tags([self::TAG])->flush();
    }
}
