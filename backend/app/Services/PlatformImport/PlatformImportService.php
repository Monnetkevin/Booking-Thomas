<?php

namespace App\Services\PlatformImport;

use App\Enums\AuditAction;
use App\Enums\PayoutPlatform;
use App\Models\Apartment;
use App\Models\PlatformImport;
use App\Repositories\Contracts\ApartmentRepositoryInterface;
use App\Repositories\Contracts\PlatformPayoutRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\PlatformImport\Parsers\AirbnbCsvParser;
use App\Services\PlatformImport\Parsers\BookingCsvParser;
use App\Services\PlatformImport\Parsers\PlatformCsvParser;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Import des revenus des plateformes depuis leurs exports CSV (spec : l'iCal ne transmet
 * aucun montant). Deux temps : aperçu (lignes reconnues, annonces à rattacher, déjà
 * importées), puis enregistrement avec le rattachement annonce → logement, retenu pour la
 * fois suivante. Ré-import sans doublon : une clé par versement (ligne de l'export).
 */
class PlatformImportService
{
    public const HISTORY = 10;

    public function __construct(
        private readonly PlatformPayoutRepositoryInterface $payouts,
        private readonly ApartmentRepositoryInterface $apartments,
        private readonly AirbnbCsvParser $airbnb,
        private readonly BookingCsvParser $booking,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return array{rows: list<array<string, mixed>>, skipped: list<array{line: int, reason: string}>, cancelled: int, listings: list<array{listing: string, apartment_id: int|null}>}
     */
    public function preview(PayoutPlatform $platform, string $contents): array
    {
        $result = $this->parser($platform)->parse($contents);
        $map = $this->validMap($this->payouts->listingMap($platform));
        $existing = $this->payouts->existingKeys($platform, array_map(fn (PlatformRow $row) => $row->entryKey, $result->rows));

        $listings = [];
        foreach ($result->rows as $row) {
            $listings[$row->listing] ??= ['listing' => $row->listing, 'apartment_id' => $map[$row->listing] ?? null];
        }

        return [
            'rows' => array_map(fn (PlatformRow $row) => [
                'line' => $row->line,
                'entry_key' => $row->entryKey,
                'confirmation_code' => $row->confirmationCode,
                'listing' => $row->listing,
                'guest' => $row->guest,
                'arrival' => $row->arrival->toDateString(),
                'departure' => $row->departure->toDateString(),
                'gross_amount' => $row->grossAmount,
                'fees' => $row->fees,
                'net_amount' => $row->netAmount,
                'already_imported' => in_array($row->entryKey, $existing, true),
            ], $result->rows),
            'skipped' => $result->skipped,
            // Annulées chez la plateforme et déjà importées : retirées à l'import.
            'cancelled' => count($this->payouts->existingKeys($platform, $result->cancelledKeys)),
            'listings' => array_values($listings),
        ];
    }

    /**
     * @param  array<string, int|null>  $mapping  annonce → logement choisis à l'aperçu ; null : détachée
     */
    public function import(PayoutPlatform $platform, string $filename, string $contents, array $mapping, ?int $userId): PlatformImport
    {
        $result = $this->parser($platform)->parse($contents);
        // array_replace garde les clés : une annonce nommée « 12 » reste « 12 » (le spread
        // renumérote les clés entières et mélangerait les annonces). Une annonce mise à null
        // par l'hôte est détachée : ses lignes ne sont pas importées.
        $map = $this->validMap(array_replace($this->payouts->listingMap($platform), $mapping));

        $import = DB::transaction(function () use ($platform, $filename, $result, $mapping, $map, $userId): PlatformImport {
            // Détachées (null) et rattachées, clés préservées (array_replace, jamais le spread).
            $this->payouts->saveListingMap($platform, array_replace(array_filter($mapping, fn (?int $id) => $id === null), $this->validMap($mapping)));
            $import = $this->payouts->createImport(['platform' => $platform, 'filename' => mb_substr($filename, 0, 190), 'user_id' => $userId]);
            $created = $updated = 0;
            $skipped = count($result->skipped);
            $detached = [];
            foreach ($result->rows as $row) {
                $apartmentId = $map[$row->listing] ?? null;
                if ($apartmentId === null) {
                    // Annonce détachée : ses versements déjà importés (rattachés par erreur) sont retirés.
                    $detached[] = $row->entryKey;
                    $skipped++;

                    continue;
                }
                $isNew = $this->payouts->upsert($platform, $row->entryKey, [
                    'confirmation_code' => $row->confirmationCode,
                    'apartment_id' => $apartmentId,
                    'platform_import_id' => $import->id,
                    'arrival' => $row->arrival->toDateString(),
                    'departure' => $row->departure->toDateString(),
                    'nights' => $row->nights(),
                    'gross_amount' => $row->grossAmount,
                    'fees' => $row->fees,
                    'net_amount' => $row->netAmount,
                ]);
                $isNew ? $created++ : $updated++;
            }

            $removed = $this->payouts->deleteKeys($platform, $result->cancelledKeys) + $this->payouts->deleteKeys($platform, $detached);

            return $this->payouts->updateImport($import, ['rows_created' => $created, 'rows_updated' => $updated, 'rows_skipped' => $skipped, 'rows_removed' => $removed]);
        });

        $this->audit->record(AuditAction::PlatformPayoutsImported, $import, after: [
            'platform' => $platform->value,
            'created' => $import->rows_created,
            'updated' => $import->rows_updated,
            'skipped' => $import->rows_skipped,
            'removed' => $import->rows_removed,
        ]);

        return $import;
    }

    /**
     * @return Collection<int, PlatformImport>
     */
    public function history(): Collection
    {
        return $this->payouts->recentImports(self::HISTORY);
    }

    private function parser(PayoutPlatform $platform): PlatformCsvParser
    {
        return match ($platform) {
            PayoutPlatform::Airbnb => $this->airbnb,
            PayoutPlatform::Booking => $this->booking,
        };
    }

    /**
     * Rattachements vers des logements existants seulement (les annonces détachées sont omises).
     *
     * @param  array<string, int|null>  $map
     * @return array<string, int>
     */
    private function validMap(array $map): array
    {
        $ids = $this->apartments->allOrdered()->map(fn (Apartment $apartment) => $apartment->id)->all();

        return array_filter($map, fn (?int $id) => $id !== null && in_array($id, $ids, true));
    }
}
