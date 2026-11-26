<?php

namespace App\Services\Extra;

use App\Models\Extra;

/** Option retenue pour un séjour, vérifiée : disponible dans le logement, quantité permise. */
final readonly class SelectedExtra
{
    public function __construct(public Extra $extra, public int $quantity) {}
}
