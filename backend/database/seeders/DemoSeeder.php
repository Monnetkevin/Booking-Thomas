<?php

namespace Database\Seeders;

use App\Enums\ApartmentStatus;
use App\Enums\BlockType;
use App\Models\Amenity;
use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use App\Models\RatePeriod;
use App\Services\Apartment\ApartmentService;
use App\Services\Catalog\CatalogCache;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use RuntimeException;

/**
 * Données de démonstration pour le développement et la recette (jamais en production) :
 * les 5 logements de la maquette, photos générées, tarifs sur 12 mois et quelques nuits prises.
 *   php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    private const APARTMENTS = [
        ['le-cocon', 'Le Cocon', 'The Cocoon', 'Bohème, rotin et lumière', 'Boho, rattan and light', '12 rue de la Paix', 60, 4, 1, 2, 7000, [232, 211, 184], ['wifi', 'kitchen', 'free_parking', 'washing_machine', 'tv', 'bed_linen']],
        ['nid-doux', 'Nid doux', 'Cosy Nest', 'Élégant et chaleureux, avec balcon', 'Elegant and warm, with a balcony', '8 avenue du Général Leclerc', 52, 4, 1, 2, 6500, [235, 220, 203], ['wifi', 'kitchen', 'balcony', 'free_parking', 'pets_allowed', 'baby_bed']],
        ['la-vie-en-rose', 'La vie en Rose', 'La Vie en Rose', 'Industriel tout en douceur', 'Gently industrial', '25 rue Gambetta', 38, 2, 1, 1, 6000, [241, 211, 203], ['wifi', 'kitchen', 'self_check_in', 'coffee_maker', 'tv']],
        ['l-elegance-mancelle', 'L’élégance Mancelle', 'L’Élégance Mancelle', 'Cocon chic et confortable', 'Chic and comfortable', '3 place de la République', 55, 4, 1, 2, 7500, [217, 205, 191], ['wifi', 'kitchen', 'free_parking', 'dishwasher', 'workspace']],
        ['le-loft-balneo', 'Le Loft balnéo', 'The Spa Loft', 'Parenthèse romantique avec baignoire spa', 'Romantic getaway with a spa bath', '41 rue Nationale', 45, 2, 1, 1, 9500, [233, 214, 218], ['wifi', 'balneo', 'self_check_in', 'kitchen', 'bed_linen', 'towels']],
    ];

    public function run(CatalogCache $catalog, ApartmentService $apartmentService): void
    {
        if (App::isProduction()) {
            throw new RuntimeException('Les données de démonstration ne doivent jamais être chargées en production.');
        }

        $this->call(AmenitySeeder::class);
        $amenities = Amenity::query()->pluck('id', 'key');

        foreach (self::APARTMENTS as $i => [$slug, $nameFr, $nameEn, $taglineFr, $taglineEn, $address, $surface, $capacity, $bedrooms, $beds, $price, $color, $amenityKeys]) {
            if (Apartment::withTrashed()->where('slug', $slug)->exists()) {
                continue;
            }

            $apartment = Apartment::query()->create([
                'slug' => $slug,
                'name' => ['fr' => $nameFr, 'en' => $nameEn],
                'tagline' => ['fr' => $taglineFr, 'en' => $taglineEn],
                'description' => [
                    'fr' => "$nameFr est un appartement de $surface m² au Mans, à quelques minutes du circuit des 24 Heures et du centre-ville.\n\nTexte de démonstration : la description définitive sera saisie par l’hôte.",
                    'en' => "$nameEn is a $surface m² apartment in Le Mans, a few minutes from the 24 Hours circuit and the city centre.\n\nDemo text: the final description will be written by the host.",
                ],
                'address' => $address,
                'postal_code' => '72000',
                'city' => 'Le Mans',
                'capacity' => $capacity,
                'bedrooms' => $bedrooms,
                'beds' => $beds,
                'bathrooms' => 1,
                'surface_m2' => $surface,
                'min_stay' => 2,
                'max_stay' => 28,
                'cleaning_fee' => 4000,
                'status' => ApartmentStatus::Published,
                'position' => $i,
            ]);
            $apartment->amenities()->sync($amenities->only($amenityKeys)->values());

            $this->rates($apartment, $price);
            $this->bookedNights($apartment, $i);
            $this->photos($apartment, $color, $nameFr);
        }

        // Position sur la carte calculée d'après l'adresse, comme pour un logement saisi dans l'admin
        // (tâches traitées par le service queue).
        $apartmentService->geocodeMissing();
        $catalog->flush();
    }

    private function rates(Apartment $apartment, int $price): void
    {
        $start = now()->startOfMonth();
        RatePeriod::query()->create([
            'apartment_id' => $apartment->id, 'label' => 'Saison', 'starts_on' => $start->toDateString(),
            'ends_on' => $start->copy()->addMonths(6)->subDay()->toDateString(), 'nightly_price' => $price,
            'weekend_supplement' => 1000,
        ]);
        RatePeriod::query()->create([
            'apartment_id' => $apartment->id, 'label' => 'Haute saison', 'starts_on' => $start->copy()->addMonths(6)->toDateString(),
            'ends_on' => $start->copy()->addMonths(13)->subDay()->toDateString(), 'nightly_price' => (int) round($price * 1.25),
            'min_stay' => 3, 'weekend_supplement' => 1500,
        ]);
    }

    /** Deux séjours déjà pris, décalés selon le logement. */
    private function bookedNights(Apartment $apartment, int $index): void
    {
        foreach ([[8 + $index, 3], [21 + $index, 4]] as [$offset, $nights]) {
            AvailabilityBlock::query()->create([
                'apartment_id' => $apartment->id,
                'starts_on' => now()->addDays($offset)->toDateString(),
                'ends_on' => now()->addDays($offset + $nights)->toDateString(),
                'type' => BlockType::Manual,
                'note' => 'Démonstration',
            ]);
        }
    }

    /**
     * @param  array{int, int, int}  $color
     */
    private function photos(Apartment $apartment, array $color, string $name): void
    {
        foreach (['Salon', 'Chambre', 'Cuisine', 'Salle de bain', 'Vue'] as $n => $room) {
            $path = tempnam(sys_get_temp_dir(), 'demo').'.jpg';
            $image = imagecreatetruecolor(1600, 1067);
            [$r, $g, $b] = $color;
            for ($y = 0; $y < 1067; $y++) {
                $shade = 1 - ($y / 1067) * (0.25 + $n * 0.05);
                imageline($image, 0, $y, 1599, $y, (int) imagecolorallocate($image, (int) ($r * $shade), (int) ($g * $shade), (int) ($b * $shade)));
            }
            imagefilledellipse($image, 300 + $n * 250, 520, 360, 360, (int) imagecolorallocatealpha($image, 255, 253, 249, 40));
            imagejpeg($image, $path, 85);

            $apartment->addMedia($path)
                ->usingFileName("demo-{$apartment->slug}-$n.jpg")
                ->withCustomProperties(['alt' => ['fr' => "$room — $name (photo de démonstration)", 'en' => "$room — demo photo"], 'width' => 1600, 'height' => 1067])
                ->toMediaCollection(Apartment::PHOTOS);
        }
    }
}
