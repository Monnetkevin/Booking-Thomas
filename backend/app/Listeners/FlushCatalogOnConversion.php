<?php

namespace App\Listeners;

use App\Services\Catalog\CatalogCache;
use Spatie\MediaLibrary\Conversions\Events\ConversionHasBeenCompletedEvent;

/** Une photo vient d'être convertie : le site public doit la montrer sans attendre. */
class FlushCatalogOnConversion
{
    public function __construct(private readonly CatalogCache $catalog) {}

    public function handle(ConversionHasBeenCompletedEvent $event): void
    {
        $this->catalog->flush();
    }
}
