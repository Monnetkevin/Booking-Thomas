<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;

/**
 * Catalogue d'équipements que l'hôte coche ensuite pour chaque logement.
 */
class AmenitySeeder extends Seeder
{
    /** @var array<string, array{0: string, 1: string, 2: string}> clé => [fr, en, icône] */
    private const AMENITIES = [
        'wifi' => ['Wi-Fi', 'Wi-Fi', 'wifi'],
        'kitchen' => ['Cuisine équipée', 'Equipped kitchen', 'cooking-pot'],
        'washing_machine' => ['Lave-linge', 'Washing machine', 'washing-machine'],
        'dishwasher' => ['Lave-vaisselle', 'Dishwasher', 'utensils'],
        'coffee_maker' => ['Machine à café', 'Coffee maker', 'coffee'],
        'tv' => ['Télévision', 'TV', 'tv'],
        'heating' => ['Chauffage', 'Heating', 'thermometer'],
        'air_conditioning' => ['Climatisation', 'Air conditioning', 'snowflake'],
        'bed_linen' => ['Linge de lit fourni', 'Bed linen provided', 'bed'],
        'towels' => ['Serviettes fournies', 'Towels provided', 'bath'],
        'hair_dryer' => ['Sèche-cheveux', 'Hair dryer', 'wind'],
        'iron' => ['Fer à repasser', 'Iron', 'shirt'],
        'baby_bed' => ['Lit bébé sur demande', 'Baby cot on request', 'baby'],
        'balneo' => ['Baignoire balnéo', 'Spa bath', 'waves'],
        'balcony' => ['Balcon', 'Balcony', 'sun'],
        'free_parking' => ['Stationnement gratuit', 'Free parking', 'car'],
        'self_check_in' => ['Arrivée autonome', 'Self check-in', 'key-round'],
        'workspace' => ['Espace de travail', 'Workspace', 'laptop'],
        'non_smoking' => ['Non-fumeur', 'Non-smoking', 'cigarette-off'],
        'pets_allowed' => ['Animaux acceptés', 'Pets allowed', 'paw-print'],
    ];

    public function run(): void
    {
        foreach (self::AMENITIES as $key => [$fr, $en, $icon]) {
            Amenity::query()->firstOrCreate(['key' => $key], [
                'label' => ['fr' => $fr, 'en' => $en],
                'icon' => $icon,
            ]);
        }
    }
}
