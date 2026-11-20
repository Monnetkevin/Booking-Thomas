<?php

use App\Enums\AuditAction;
use App\Models\Apartment;
use App\Models\AuditLog;
use App\Models\PlatformPayout;
use App\Models\User;
use App\Services\PlatformImport\Parsers\CsvTable;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->cocon = Apartment::factory()->create(['name' => ['fr' => 'Le Cocon', 'en' => 'Cocoon']]);
    $this->nid = Apartment::factory()->create(['name' => ['fr' => 'Nid doux', 'en' => 'Nest']]);
});

function platformFile(string $name): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, (string) file_get_contents(__DIR__."/../../fixtures/platform/$name"));
}

it('lit les montants et les dates des exports', function () {
    expect(CsvTable::cents('1 204,00'))->toBe(120400)
        ->and(CsvTable::cents('1,234.56'))->toBe(123456)
        ->and(CsvTable::cents('49.50 EUR'))->toBe(4950)
        ->and(CsvTable::cents('-12 €'))->toBe(-1200)
        ->and(CsvTable::cents(''))->toBeNull()
        ->and(CsvTable::date('04/11/2026', true)?->toDateString())->toBe('2026-11-04')
        ->and(CsvTable::date('11/04/2026', false)?->toDateString())->toBe('2026-11-04')
        ->and(CsvTable::date('31/02/2026', true))->toBeNull();
});

it('prévisualise un export Airbnb : séjours reconnus, lignes illisibles, annonces à rattacher', function () {
    $this->post('/api/admin/platform-imports/preview', ['platform' => 'airbnb', 'file' => platformFile('airbnb-fr.csv')], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonCount(2, 'data.rows')
        ->assertJsonPath('data.rows.0.confirmation_code', 'HMABC123')
        ->assertJsonPath('data.rows.0.arrival', '2026-11-04')
        ->assertJsonPath('data.rows.0.departure', '2026-11-07')
        ->assertJsonPath('data.rows.0.net_amount', 31250)
        ->assertJsonPath('data.rows.0.fees', 950)
        ->assertJsonPath('data.rows.1.net_amount', 120400)
        ->assertJsonPath('data.skipped', [['line' => 5, 'reason' => 'unreadable']])
        ->assertJsonPath('data.listings', [
            ['listing' => 'Studio Cocon centre', 'apartment_id' => null],
            ['listing' => 'Nid doux gare', 'apartment_id' => null],
        ]);
});

it('importe avec le rattachement des annonces, le retient, et ré-importe sans doublon', function () {
    $mapping = [['listing' => 'Studio Cocon centre', 'apartment_id' => $this->cocon->id], ['listing' => 'Nid doux gare', 'apartment_id' => $this->nid->id]];

    $this->post('/api/admin/platform-imports', ['platform' => 'airbnb', 'file' => platformFile('airbnb-fr.csv'), 'mapping' => $mapping], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.rows_created', 2)
        ->assertJsonPath('data.rows_skipped', 1);
    expect(PlatformPayout::query()->where('confirmation_code', 'HMABC123')->sole())
        ->apartment_id->toBe($this->cocon->id)
        ->nights->toBe(3)
        ->and(AuditLog::query()->where('action', AuditAction::PlatformPayoutsImported->value)->count())->toBe(1);

    // Deuxième import, sans rattachement : celui du premier est retenu, rien n'est dupliqué.
    $this->post('/api/admin/platform-imports/preview', ['platform' => 'airbnb', 'file' => platformFile('airbnb-fr.csv')], ['Accept' => 'application/json'])
        ->assertJsonPath('data.listings.0.apartment_id', $this->cocon->id)
        ->assertJsonPath('data.rows.0.already_imported', true);
    $this->post('/api/admin/platform-imports', ['platform' => 'airbnb', 'file' => platformFile('airbnb-fr.csv')], ['Accept' => 'application/json'])
        ->assertJsonPath('data.rows_created', 0)
        ->assertJsonPath('data.rows_updated', 2);
    expect(PlatformPayout::query()->count())->toBe(2);
    $this->getJson('/api/admin/platform-imports')->assertJsonCount(2, 'data');
});

it('importe un export Booking.com sans les annulations, net = prix moins commission', function () {
    $this->post('/api/admin/platform-imports', [
        'platform' => 'booking',
        'file' => platformFile('booking-en.csv'),
        'mapping' => [['listing' => 'Le Cocon', 'apartment_id' => $this->cocon->id]],
    ], ['Accept' => 'application/json'])->assertCreated()->assertJsonPath('data.rows_created', 1);

    expect(PlatformPayout::query()->sole())
        ->gross_amount->toBe(33000)
        ->fees->toBe(4950)
        ->net_amount->toBe(28050);
});

it('refuse un fichier qui n’est pas l’export de la plateforme, et le gestionnaire', function () {
    $this->post('/api/admin/platform-imports/preview', ['platform' => 'airbnb', 'file' => platformFile('booking-en.csv')], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'invalid_platform_file');
    $this->post('/api/admin/platform-imports/preview', ['platform' => 'airbnb', 'file' => UploadedFile::fake()->create('photo.png', 10, 'image/png')], ['Accept' => 'application/json'])
        ->assertJsonValidationErrors('file');

    $this->actingAs(User::factory()->manager()->create())->getJson('/api/admin/platform-imports')->assertForbidden();
});

it('additionne les versements d’un long séjour, garde les annonces au nom numérique et ignore les valeurs trop longues', function () {
    $this->post('/api/admin/platform-imports', [
        'platform' => 'airbnb',
        'file' => platformFile('airbnb-multi.csv'),
        'mapping' => [['listing' => '12', 'apartment_id' => $this->cocon->id], ['listing' => '2', 'apartment_id' => $this->nid->id]],
    ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.rows_created', 2)
        ->assertJsonPath('data.rows_updated', 0)
        ->assertJsonPath('data.rows_skipped', 1);

    expect(PlatformPayout::query()->where('confirmation_code', 'HMLONG1')->sole())
        ->apartment_id->toBe($this->cocon->id)
        ->net_amount->toBe(200000)
        ->fees->toBe(6000)
        ->nights->toBe(32)
        ->arrival->toDateString()->toBe('2026-12-01')
        ->and(PlatformPayout::query()->where('confirmation_code', 'HMOTHER')->sole()->apartment_id)->toBe($this->nid->id);
});
