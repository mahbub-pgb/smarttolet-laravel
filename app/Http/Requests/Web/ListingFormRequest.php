<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use App\Models\Listing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared validation for the dashboard create/edit listing form. The set of
 * fields mirrors the multi-section form (Basic Info, Location, Details,
 * Amenities, Occupancy & Rules, Media, Publishing).
 */
abstract class ListingFormRequest extends FormRequest
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
            // Basic info
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:5000'],
            'type' => ['required', 'string', Rule::in(Listing::TYPES)],
            'rent' => ['required', 'integer', 'min:0', 'max:100000000'],
            'advance_amount' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'available_from' => ['nullable', 'date'],

            // Location
            'latitude' => ['required_with:longitude', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['required_with:latitude', 'nullable', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:255'],
            'area_name' => ['nullable', 'string', 'max:120'],

            // Details
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:50'],
            'bathrooms' => ['nullable', 'integer', 'min:0', 'max:50'],
            'area_sqft' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'balconies' => ['nullable', 'integer', 'min:0', 'max:50'],
            'floor_number' => ['nullable', 'integer', 'min:-5', 'max:200'],
            'building_floors' => ['nullable', 'integer', 'min:0', 'max:200'],

            // Amenities + occupancy/rules (checkbox keys)
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['string', Rule::in(array_keys(Listing::AMENITIES))],
            'occupancy_rules' => ['nullable', 'array'],
            'occupancy_rules.*' => ['string', Rule::in(array_keys(Listing::OCCUPANCY_RULES))],

            // Media
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'picked' => ['nullable', 'array', 'max:10'],
            'picked.*' => ['integer', Rule::exists('media', 'id')->where('owner_id', $this->user()?->id)],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['string'],
            'video_tour_url' => ['nullable', 'url', 'max:255', 'regex:#^https?://(www\.)?(youtube\.com|youtu\.be)/#i'],

            // Publishing
            'as_draft' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'video_tour_url.regex' => 'Enter a valid YouTube URL.',
            'latitude.required_with' => 'Drop a pin on the map to set the location.',
        ];
    }
}
