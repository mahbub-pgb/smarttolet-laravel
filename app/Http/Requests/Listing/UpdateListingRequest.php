<?php

declare(strict_types=1);

namespace App\Http\Requests\Listing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ownership is enforced by the policy in the controller.
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:160'],
            'description' => ['sometimes', 'string', 'max:5000'],
            'type' => ['sometimes', 'string', Rule::in(['apartment', 'room', 'sublet', 'office', 'shop', 'house', 'garage', 'hostel'])],
            'category' => ['sometimes', 'nullable', 'string', 'max:50'],
            'rent' => ['sometimes', 'integer', 'min:0', 'max:100000000'],
            'bedrooms' => ['sometimes', 'integer', 'min:0', 'max:50'],
            'bathrooms' => ['sometimes', 'integer', 'min:0', 'max:50'],
            'area_name' => ['sometimes', 'string', 'max:120'],
            'address' => ['sometimes', 'string', 'max:255'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'amenities' => ['sometimes', 'array', 'max:40'],
            'amenities.*' => ['string', 'max:60'],
            'images' => ['sometimes', 'array', 'max:10'],
            'images.*' => ['file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }
}
