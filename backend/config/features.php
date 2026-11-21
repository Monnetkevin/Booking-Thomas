<?php

/*
 * Fonctionnalités mises de côté, activables par variable d'environnement.
 */
return [

    /*
     * Import des revenus Airbnb et Booking.com depuis leurs fichiers CSV. Désactivé jusqu'à la
     * fin du projet : formats des exports à confirmer sur de vrais fichiers, et corrections
     * relevées à la revue (encodage Excel, ajustements et réservations modifiées comptés deux
     * fois, annulations avec frais). Désactivé : page et API fermées (404), un séjour
     * Booking.com compte comme nuit vendue sans versement (voir SoldStayBlocks).
     */
    'platform_import' => (bool) env('PLATFORM_IMPORT_ENABLED', false),

];
