<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** Accept area preferences as a comma-separated string from the form. */
    protected function prepareForValidation(): void
    {
        $areas = $this->input('area_preferences');

        if (is_string($areas)) {
            $this->merge([
                'area_preferences' => collect(explode(',', $areas))
                    ->map(fn ($a) => trim($a))
                    ->filter()
                    ->values()
                    ->all(),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'photo' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'dob' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'string', Rule::in(['male', 'female', 'other'])],
            'occupation' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required_with:longitude', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['required_with:latitude', 'nullable', 'numeric', 'between:-180,180'],
            'area_preferences' => ['nullable', 'array', 'max:20'],
            'area_preferences.*' => ['string', 'max:120'],

            // Optional password change.
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }
}
