<?php

namespace App\Http\Resources;

use App\Models\Apartment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Utilisateur connecté, avec ses droits utiles à l'interface : le front affiche, l'API décide
 * (ApartmentPolicy::managePricing pour les prix).
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'can_manage_pricing' => $this->resource->can('managePricing', Apartment::class),
        ];
    }
}
