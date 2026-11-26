<?php

namespace App\Enums;

/** Modèles de barème d'annulation, à valider avec le client. */
enum CancellationPolicy: string
{
    case Flexible = 'flexible';
    case Moderate = 'moderate';
    case Strict = 'strict';
}
