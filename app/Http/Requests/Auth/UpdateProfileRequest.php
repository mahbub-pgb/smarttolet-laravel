<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'email' => ['sometimes', 'nullable', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'photo' => ['sometimes', 'nullable', 'url'],
            'dob' => ['sometimes', 'nullable', 'date', 'before:today'],
            'gender' => ['sometimes', 'nullable', Rule::in(['male', 'female', 'other'])],
            'occupation' => ['sometimes', 'nullable', 'string', 'max:120'],
            'nid' => ['sometimes', 'nullable', 'string', 'max:50'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'area_preferences' => ['sometimes', 'nullable', 'array', 'max:20'],
            'area_preferences.*' => ['string', 'max:100'],
            'password' => ['sometimes', 'nullable', 'string', 'min:8', 'max:72'],
        ];
    }
}
