<?php

namespace App\Http\Resources\Admin;

use App\Models\PlatformImport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PlatformImport
 */
class PlatformImportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'platform' => $this->platform->value,
            'filename' => $this->filename,
            'by' => $this->user?->name,
            'rows_created' => $this->rows_created,
            'rows_updated' => $this->rows_updated,
            'rows_skipped' => $this->rows_skipped,
            'rows_removed' => $this->rows_removed,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
