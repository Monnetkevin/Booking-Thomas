<?php

// Back-office uniquement (en français) : erreurs de synchronisation et alertes.
return [
    'errors' => [
        'unreachable' => 'Calendrier injoignable (délai dépassé ou serveur indisponible).',
        'http' => 'La plateforme a répondu par une erreur HTTP :status.',
        'too_large' => 'Le fichier du calendrier est anormalement volumineux.',
        'invalid' => 'Le fichier reçu n’est pas un calendrier iCal valide.',
        'suspicious_empty' => 'Le calendrier reçu est vide alors que des nuits étaient bloquées : il est ignoré par précaution.',
        'no_import_url' => 'Aucune URL d’import n’est renseignée pour ce calendrier.',
        'already_exists' => 'Ce logement a déjà un calendrier :provider.',
        'import_url_host' => 'Cette adresse n’est pas un calendrier :provider.',
    ],
];
