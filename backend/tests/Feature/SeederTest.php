<?php

use App\Enums\UserRole;
use App\Models\Amenity;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    config([
        'booking.admin.email' => 'hote@example.com',
        'booking.admin.password' => 'un-mot-de-passe-solide',
    ]);
});

it('crée le compte admin, les réglages et les équipements', function () {
    $this->seed(DatabaseSeeder::class);

    $admin = User::where('email', 'hote@example.com')->sole();

    expect($admin->role)->toBe(UserRole::Admin)
        ->and(Hash::check('un-mot-de-passe-solide', $admin->password))->toBeTrue()
        ->and(Setting::get('booking.min_notice_days'))->toBe(1)
        ->and(Setting::get('notifications.admin_emails'))->toBe(['hote@example.com'])
        ->and(Amenity::where('key', 'wifi')->sole()->getTranslation('label', 'en'))->toBe('Wi-Fi');
});

it('ne crée aucun logement : ils se saisissent dans l’admin', function () {
    $this->seed(DatabaseSeeder::class);

    $this->assertDatabaseCount('apartments', 0);
});

it('peut être rejoué sans écraser les modifications de l’hôte', function () {
    $this->seed(DatabaseSeeder::class);
    User::where('email', 'hote@example.com')->update(['name' => 'Thomas']);
    Setting::put('booking.min_notice_days', 3);

    $this->seed(DatabaseSeeder::class);

    expect(User::count())->toBe(1)
        ->and(User::sole()->name)->toBe('Thomas')
        ->and(Setting::get('booking.min_notice_days'))->toBe(3)
        ->and(Amenity::count())->toBe(20);
});

it('refuse de créer un admin sans mot de passe solide', function () {
    config(['booking.admin.password' => 'court']);

    $this->seed(DatabaseSeeder::class);
})->throws(RuntimeException::class);
