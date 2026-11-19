<?php

namespace App\Http\Requests\Admin\Audit;

use App\Enums\AuditAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Journal d'audit : toutes les actions, ou une seule. */
class ListAuditLogsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => ['sometimes', 'nullable', Rule::enum(AuditAction::class)],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function action(): ?AuditAction
    {
        return AuditAction::tryFrom((string) $this->input('action'));
    }
}
