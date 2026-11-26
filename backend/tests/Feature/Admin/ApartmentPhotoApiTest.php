<?php

use App\Models\Amenity;
use App\Models\Apartment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
    Queue::fake();
    $this->actingAs(User::factory()->create());
    $this->apartment = Apartment::factory()->create();
    $this->url = "/api/admin/apartments/{$this->apartment->id}/photos";
});

function photo(string $name = 'salon.jpg', int $width = 1600, int $height = 1067): UploadedFile
{
    return UploadedFile::fake()->image($name, $width, $height);
}

describe('envoi', function () {
    it('enregistre les photos en privé et lance leurs conversions en tâche de fond', function () {
        $response = $this->post($this->url, ['photos' => [photo(), photo('chambre.png')]], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.width', 1600)
            ->assertJsonPath('data.0.ready', false)
            ->assertJsonPath('data.0.urls.card.webp', null);

        $media = $this->apartment->getFirstMedia(Apartment::PHOTOS);
        expect($media->disk)->toBe('local')
            ->and($media->conversions_disk)->toBe('public')
            ->and($media->file_name)->not->toContain('salon');
        Storage::disk('local')->assertExists($media->getPathRelativeToRoot());
        Queue::assertPushed(PerformConversionsJob::class, 2);
    });

    it('refuse un fichier qui n’est pas une image, quelle que soit son extension', function () {
        $fake = UploadedFile::fake()->createWithContent('photo.jpg', '<?php echo "pas une image";');

        $this->post($this->url, ['photos' => [$fake]], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('photos.0');
    });

    it('refuse une photo trop petite', function () {
        $this->post($this->url, ['photos' => [photo('mini.jpg', 400, 300)]], ['Accept' => 'application/json'])
            ->assertJsonValidationErrors('photos.0');
    });

    it('limite le nombre de photos par logement', function () {
        foreach (range(1, 30) as $ignored) {
            $this->apartment->addMedia(photo())->toMediaCollection(Apartment::PHOTOS);
        }

        $this->post($this->url, ['photos' => [photo()]], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'too_many_photos');
    });
});

it('génère les versions WebP et AVIF ré-encodées', function () {
    config(['media-library.queue_conversions_by_default' => false]);

    $this->post($this->url, ['photos' => [photo()]], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.0.ready', true);

    $media = $this->apartment->getFirstMedia(Apartment::PHOTOS);
    foreach (['thumb', 'card', 'large'] as $size) {
        Storage::disk('public')->assertExists($media->getPathRelativeToRoot($size));
        if (function_exists('imageavif')) {
            Storage::disk('public')->assertExists($media->getPathRelativeToRoot("$size-avif"));
        }
    }
    expect($media->getPathRelativeToRoot('card'))->toEndWith('.webp');
    [$width, $height] = getimagesize(Storage::disk('public')->path($media->getPathRelativeToRoot('thumb')));
    expect([$width, $height])->toBe([480, 360]);
});

describe('ordre, texte alternatif et suppression', function () {
    beforeEach(function () {
        $this->photos = collect(range(1, 3))->map(
            fn () => $this->apartment->addMedia(photo())->toMediaCollection(Apartment::PHOTOS),
        );
    });

    it('change l’ordre ; la première devient la couverture', function () {
        [$a, $b, $c] = $this->photos->pluck('id')->all();

        $this->putJson("{$this->url}/order", ['ids' => [$c, $a, $b]])
            ->assertOk()
            ->assertJsonPath('data.*.id', [$c, $a, $b]);

        expect($this->apartment->fresh()->getFirstMedia(Apartment::PHOTOS)->id)->toBe($c);
    });

    it('refuse un ordre incomplet ou avec une photo d’un autre logement', function () {
        [$a, $b] = $this->photos->pluck('id')->all();
        $foreign = Apartment::factory()->create()->addMedia(photo())->toMediaCollection(Apartment::PHOTOS);

        $this->putJson("{$this->url}/order", ['ids' => [$a, $b]])->assertJsonPath('code', 'invalid_photo_order');
        $this->putJson("{$this->url}/order", ['ids' => [$a, $b, $foreign->id]])->assertJsonPath('code', 'invalid_photo_order');
    });

    it('enregistre le texte alternatif en français et en anglais', function () {
        $id = $this->photos->first()->id;

        $this->patchJson("{$this->url}/$id", ['alt' => ['fr' => ' Salon lumineux ', 'en' => 'Bright living room']])
            ->assertOk()
            ->assertJsonPath('data.alt', ['fr' => 'Salon lumineux', 'en' => 'Bright living room']);
    });

    it('supprime une photo du logement', function () {
        $id = $this->photos->first()->id;

        $this->deleteJson("{$this->url}/$id")->assertNoContent();

        expect($this->apartment->fresh()->getMedia(Apartment::PHOTOS))->toHaveCount(2);
    });

    it('ne touche pas aux photos d’un autre logement (404)', function () {
        $other = Apartment::factory()->create();

        $this->deleteJson("/api/admin/apartments/{$other->id}/photos/{$this->photos->first()->id}")->assertNotFound();
        expect($this->apartment->fresh()->getMedia(Apartment::PHOTOS))->toHaveCount(3);
    });
});

describe('équipements', function () {
    it('liste le catalogue avec les libellés traduits', function () {
        Amenity::factory()->create(['key' => 'wifi', 'label' => ['fr' => 'Wi-Fi', 'en' => 'Wi-Fi']]);

        $this->getJson('/api/admin/amenities')->assertOk()->assertJsonPath('data.0.key', 'wifi');
    });

    it('coche et décoche les équipements d’un logement', function () {
        [$wifi, $parking, $tv] = Amenity::factory()->count(3)->create();
        $this->apartment->amenities()->attach($tv);

        $this->putJson("/api/admin/apartments/{$this->apartment->id}/amenities", ['amenity_ids' => [$wifi->id, $parking->id]])
            ->assertOk()
            ->assertJsonPath('data.amenity_ids', [$wifi->id, $parking->id]);

        $this->putJson("/api/admin/apartments/{$this->apartment->id}/amenities", ['amenity_ids' => []])
            ->assertJsonPath('data.amenity_ids', []);
    });

    it('refuse un équipement inconnu', function () {
        $this->putJson("/api/admin/apartments/{$this->apartment->id}/amenities", ['amenity_ids' => [999]])
            ->assertJsonValidationErrors('amenity_ids.0');
    });
});

it('ferme les photos au rôle ménage', function () {
    $this->actingAs(User::factory()->housekeeping()->create());

    $this->post($this->url, ['photos' => [photo()]], ['Accept' => 'application/json'])->assertForbidden();
});
