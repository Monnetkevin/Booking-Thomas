<?php

use App\Services\PlatformImport\Parsers\AirbnbCsvParser;
use App\Services\PlatformImport\Parsers\InvalidPlatformFileException;
use Carbon\CarbonImmutable;

const AIRBNB_HEADER = 'Date;Type;Code de confirmation;Date de début;Nuits;Voyageur;Logement;Montant;Frais de service;Revenus bruts';

function airbnbCsv(string ...$lines): string
{
    return implode("\n", [AIRBNB_HEADER, ...$lines])."\n";
}

it('lit une réservation : dates, montants en centimes, frais positifs', function () {
    $result = (new AirbnbCsvParser)->parse(airbnbCsv('05/11/2026;Réservation;HM1;04/11/2026;3;Jean;Studio;800,00;-24,00;824,00'));

    $row = $result->rows[0];
    expect($result->rows)->toHaveCount(1)
        ->and($row->confirmationCode)->toBe('HM1')
        ->and($row->arrival->toDateString())->toBe('2026-11-04')
        ->and($row->departure->toDateString())->toBe('2026-11-07')
        ->and($row->netAmount)->toBe(80000)
        ->and($row->fees)->toBe(2400)
        ->and($row->grossAmount)->toBe(82400);
});

it('rattache un ajustement sans dates à la réservation du même fichier, sans chercher plus loin', function () {
    $called = false;
    $result = (new AirbnbCsvParser)->parse(airbnbCsv(
        '05/11/2026;Réservation;HM1;04/11/2026;3;Jean;Studio;800,00;;',
        '06/11/2026;Ajustement de la résolution;HM1;;;Jean;;-800,00;;',
    ), function () use (&$called) {
        $called = true;

        return [];
    });

    expect($result->rows)->toHaveCount(2)
        ->and($result->rows[1]->arrival->toDateString())->toBe('2026-11-04')
        ->and($result->rows[1]->listing)->toBe('Studio')
        ->and($called)->toBeFalse();
});

it('rattache un ajustement d’un export ultérieur au séjour déjà importé, demandé une seule fois', function () {
    $asked = [];
    $result = (new AirbnbCsvParser)->parse(airbnbCsv(
        '02/03/2027;Annulation;HMOLD;;;Jean;;-500,00;;',
        '03/03/2027;Ajustement;HMOLD;;;Jean;;-20,00;;',
        '04/03/2027;Ajustement;HMNONE;;;;;-10,00;;',
    ), function (array $codes) use (&$asked) {
        $asked[] = $codes;

        return ['HMOLD' => ['start' => CarbonImmutable::parse('2026-11-04'), 'nights' => 3, 'listing' => 'Studio']];
    });

    expect($asked)->toBe([['HMOLD', 'HMNONE']])
        ->and($result->rows)->toHaveCount(2)
        ->and($result->rows[0]->arrival->toDateString())->toBe('2026-11-04')
        ->and($result->rows[0]->nights())->toBe(3)
        ->and($result->rows[0]->listing)->toBe('Studio')
        ->and($result->skipped)->toBe([['line' => 4, 'reason' => 'unreadable']]);
});

it('écarte un ajustement sans dates quand aucun séjour n’est connu', function () {
    $result = (new AirbnbCsvParser)->parse(airbnbCsv('02/03/2027;Annulation;HMOLD;;;Jean;Studio;-500,00;;'));

    expect($result->rows)->toBe([])
        ->and($result->skipped)->toBe([['line' => 2, 'reason' => 'unreadable']]);
});

it('garde ses propres dates à un ajustement qui en a', function () {
    $result = (new AirbnbCsvParser)->parse(airbnbCsv(
        '05/11/2026;Réservation;HM1;04/11/2026;3;Jean;Studio;800,00;;',
        '06/11/2026;Ajustement;HM1;05/11/2026;1;Jean;Studio;-100,00;;',
    ));

    expect($result->rows[1]->arrival->toDateString())->toBe('2026-11-05')
        ->and($result->rows[1]->nights())->toBe(1);
});

it('distingue deux ajustements identiques, avec des clés stables d’un import à l’autre', function () {
    $csv = airbnbCsv(
        '05/11/2026;Réservation;HM1;04/11/2026;3;Jean;Studio;800,00;;',
        '06/11/2026;Ajustement de la résolution;HM1;;;Jean;;-50,00;;',
        '06/11/2026;Ajustement de la résolution;HM1;;;Jean;;-50,00;;',
    );
    $first = array_map(fn ($row) => $row->entryKey, (new AirbnbCsvParser)->parse($csv)->rows);
    $again = array_map(fn ($row) => $row->entryKey, (new AirbnbCsvParser)->parse($csv)->rows);

    expect(array_unique($first))->toHaveCount(3)
        ->and($again)->toBe($first);
});

it('ne change pas la clé d’un ajustement unique quand un double apparaît plus loin', function () {
    $single = (new AirbnbCsvParser)->parse(airbnbCsv('06/11/2026;Ajustement;HM1;04/11/2026;3;Jean;Studio;-50,00;;'))->rows;
    $double = (new AirbnbCsvParser)->parse(airbnbCsv(
        '06/11/2026;Ajustement;HM1;04/11/2026;3;Jean;Studio;-50,00;;',
        '06/11/2026;Ajustement;HM1;04/11/2026;3;Jean;Studio;-50,00;;',
    ))->rows;

    expect($double[0]->entryKey)->toBe($single[0]->entryKey)
        ->and($double[1]->entryKey)->not->toBe($single[0]->entryKey);
});

it('distingue un ajustement d’une réservation de même montant', function () {
    $rows = (new AirbnbCsvParser)->parse(airbnbCsv(
        '05/11/2026;Réservation;HM1;04/11/2026;3;Jean;Studio;100,00;;',
        '05/11/2026;Ajustement;HM1;04/11/2026;3;Jean;Studio;100,00;;',
    ))->rows;

    expect($rows[0]->entryKey)->not->toBe($rows[1]->entryKey);
});

it('garde le signe des frais d’un ajustement : frais rendus négatifs', function () {
    $rows = (new AirbnbCsvParser)->parse(airbnbCsv(
        '05/11/2026;Réservation;HM1;04/11/2026;3;Jean;Studio;800,00;24,00;',
        '06/11/2026;Annulation;HM1;;;Jean;;-800,00;-24,00;',
        '07/11/2026;Ajustement;HM1;;;Jean;;-30,00;5,00;',
    ))->rows;

    expect($rows[0]->fees)->toBe(2400)
        ->and($rows[1]->fees)->toBe(-2400)
        ->and($rows[2]->fees)->toBe(500);
});

it('ignore les versements bancaires et les autres types', function () {
    $result = (new AirbnbCsvParser)->parse(airbnbCsv(
        '09/11/2026;Versement;;;;;;;;',
        '09/11/2026;Revenus divers;;;;;;12,00;;',
    ));

    expect($result->rows)->toBe([])->and($result->skipped)->toBe([]);
});

it('lit un export anglais, dates au format MM/JJ/AAAA', function () {
    $csv = "Date,Type,Confirmation Code,Start Date,Nights,Guest,Listing,Amount,Service fee,Gross earnings\n"
        ."11/05/2026,Reservation,HM1,11/04/2026,3,Jean,Studio,800.00,24.00,824.00\n";

    expect((new AirbnbCsvParser)->parse($csv)->rows[0]->arrival->toDateString())->toBe('2026-11-04');
});

it('refuse un fichier sans les colonnes attendues', function () {
    (new AirbnbCsvParser)->parse("Date;Type\n05/11/2026;Réservation\n");
})->throws(InvalidPlatformFileException::class);
