<?php

namespace App\Http\Resources\Admin;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Notification
 */
class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'level' => $this->level->value,
            'title' => $this->data['title'],
            'body' => $this->data['body'],
            'details' => $this->data['details'] ?? [],
            'link' => $this->data['link'] ?? null,
            'read' => $this->read_at !== null,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
