<?php

namespace App\Services\Pricing;

use RuntimeException;

/** Une nuit du séjour n'est couverte par aucune période tarifaire. */
class MissingRateException extends RuntimeException {}
