<?php

use App\Services\PlatformImport\Parsers\BookingCsvParser;

it('lit des dates JJ/MM/AAAA dans un export français', function () {
    $rows = (new BookingCsvParser)->parse("Numéro de réservation;Arrivée;Départ;Prix\n77;03/04/2027;06/04/2027;300,00\n")->rows;

    expect($rows[0]->arrival->toDateString())->toBe('2027-04-03')
        ->and($rows[0]->departure->toDateString())->toBe('2027-04-06');
});

it('lit des dates MM/JJ/AAAA dans un export anglais', function () {
    $rows = (new BookingCsvParser)->parse("Book number,Check-in,Check-out,Price\n77,03/04/2027,03/06/2027,300.00\n")->rows;

    expect($rows[0]->arrival->toDateString())->toBe('2027-03-04')
        ->and($rows[0]->departure->toDateString())->toBe('2027-03-06');
});

it('déduit l’ordre des dates du fichier quand un jour dépasse 12, quelle que soit la langue', function () {
    $rows = (new BookingCsvParser)->parse("Book number,Check-in,Check-out,Price\n77,03/04/2027,06/04/2027,300.00\n78,20/04/2027,22/04/2027,200.00\n")->rows;

    expect($rows[0]->arrival->toDateString())->toBe('2027-04-03')
        ->and($rows[1]->arrival->toDateString())->toBe('2027-04-20');
});

it('garde les dates ISO telles quelles', function () {
    $rows = (new BookingCsvParser)->parse("Book number,Check-in,Check-out,Price\n77,2027-03-04,2027-03-06,300.00\n")->rows;

    expect($rows[0]->arrival->toDateString())->toBe('2027-03-04');
});
