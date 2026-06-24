<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

class AdvertisementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::ManageAdvertisements) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'title' => [$required, 'string', 'max:160'],
            'image' => [$required, 'string', 'max:255'],
            'placement' => [$required, 'string', 'max:60'],
            'target_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }
}
