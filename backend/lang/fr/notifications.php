<?php

return [
    'ical_conflict' => [
        'title' => 'Conflit de dates : :apartment',
        'body' => ':provider a transmis un séjour (:dates) qui recouvre :count réservation(s) du site. Annulez l’une des deux sans attendre.',
    ],
    'ical_feed_failing' => [
        'title' => 'Calendrier :provider en échec : :apartment',
        'body' => ':failures lectures de suite ont échoué : les nuits de ce logement ne sont plus à jour. Dernière erreur : :error',
    ],
    'ical_empty_calendar_refused' => [
        'title' => 'Calendrier :provider vide refusé : :apartment',
        'body' => 'Le calendrier reçu est vide alors que des nuits étaient bloquées : il est ignoré par précaution. S’il est bien vide chez :provider, confirmez-le depuis l’écran de synchronisation.',
    ],
    'ical_feed_recovered' => [
        'title' => 'Calendrier :provider de nouveau à jour : :apartment',
        'body' => 'La lecture du calendrier fonctionne à nouveau ; les nuits du logement sont à jour.',
    ],
    'booking_confirmed' => [
        'title' => 'Nouvelle réservation : :apartment',
        'body' => ':guest, :dates, :guests voyageur(s), :total.',
    ],
    'contact_message' => [
        'title' => 'Message de :name',
        'body' => ':excerpt',
    ],
    'stay' => [
        'added' => 'Nouveau séjour : :apartment',
        'cancelled' => 'Séjour annulé : :apartment',
        'moved' => 'Séjour déplacé : :apartment',
        'dates' => 'Arrivée :arrival, départ :departure.',
        'previous' => 'Avant : arrivée :arrival, départ :departure.',
        'guests' => ':count voyageur(s).',
        'turnover' => 'Départ et arrivée le même jour : ménage entre les deux séjours.',
    ],
    'daily_summary' => [
        'title' => 'Demain, :date : :count mouvement(s)',
        'body' => 'Départs et arrivées de demain, par logement.',
        'departure' => ':apartment : départ avant :time',
        'arrival' => ':apartment : arrivée à partir de :time:guests',
        'arrival_guests' => ', :count voyageur(s)',
        'blocked' => ':apartment : fermé à partir de demain, pas d’arrivée',
        'occupied' => ':apartment : occupé par des proches à partir de demain',
        'occupied_end' => ':apartment : fin d’occupation par des proches, ménage à prévoir',
    ],
];
