<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::ManageUsers) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'role' => ['sometimes', Rule::in(['user', 'moderator', 'admin', 'super_admin'])],
            'is_suspended' => ['sometimes', 'boolean'],
            'is_landlord_verified' => ['sometimes', 'boolean'],
        ];
    }
}
