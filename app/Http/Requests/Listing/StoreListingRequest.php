<?php

declare(strict_types=1);

namespace App\Http\Requests\Listing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreListingRequest extends FormRequest
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
        return [
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:5000'],
            'type' => ['required', 'string', Rule::in(['apartment', 'room', 'sublet', 'office', 'shop', 'house', 'garage', 'hostel'])],
            'category' => ['sometimes', 'nullable', 'string', 'max:50'],
            'rent' => ['required', 'integer', 'min:0', 'max:100000000'],
            'bedrooms' => ['sometimes', 'integer', 'min:0', 'max:50'],
            'bathrooms' => ['sometimes', 'integer', 'min:0', 'max:50'],
            'area_name' => ['required', 'string', 'max:120'],
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['required_with:longitude', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['required_with:latitude', 'nullable', 'numeric', 'between:-180,180'],
            'amenities' => ['sometimes', 'array', 'max:40'],
            'amenities.*' => ['string', 'max:60'],
            'as_draft' => ['sometimes', 'boolean'],

            // Up to 10 images (multipart). Validate MIME + size.
            'images' => ['sometimes', 'array', 'max:10'],
            'images.*' => ['file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }
}
