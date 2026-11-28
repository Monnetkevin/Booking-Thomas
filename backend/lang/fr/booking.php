<?php

return [
    'errors' => [
        'no_guest_email' => 'L’e-mail du voyageur n’a pas été saisi : ajoutez-le dans la fiche pour lui écrire.',
        'no_rate' => 'Aucun tarif ne couvre toutes ces nuits : indiquez le prix total convenu.',
        'free_stay' => 'Une réservation a toujours un montant : pour un séjour sans montant (usage personnel, logement prêté), fermez plutôt les dates.',
        'not_cancellable' => 'Seule une réservation confirmée peut être annulée.',
        'not_confirmed' => 'Cette réservation n’est pas confirmée : il n’y a pas de confirmation à renvoyer.',
        'block_already_removed' => 'Ce blocage a déjà été retiré.',
        'guest_stay_in_progress' => 'Ce voyageur a un séjour en cours ou à venir : ses données servent encore à la réservation. Anonymisez-le après son départ.',
        'block_not_removable' => 'Seul un blocage manuel peut être retiré : une réservation s’annule, un séjour importé suit sa plateforme.',
    ],
    'dates_unavailable' => 'Ces dates ne sont plus disponibles. Merci d’en choisir d’autres.',
    'captcha_failed' => 'La vérification anti-robot a échoué. Merci de réessayer.',
    'sources' => [
        'direct' => 'Site',
        'manual' => 'Saisie à la main',
    ],
    'statuses' => [
        'pending' => 'Paiement en cours',
        'confirmed' => 'Confirmée',
        'completed' => 'Terminée',
        'cancelled' => 'Annulée',
        'expired' => 'Expirée',
    ],
];
