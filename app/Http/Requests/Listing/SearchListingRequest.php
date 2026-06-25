<?php

declare(strict_types=1);

namespace App\Http\Requests\Listing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'type' => ['sometimes', 'nullable', 'string', 'max:40'],
            'category' => ['sometimes', 'nullable', 'string', 'max:50'],
            'occupancy' => ['sometimes', 'nullable', 'string', 'max:50'],
            'area' => ['sometimes', 'nullable', 'string', 'max:120'],
            'min_rent' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'max_rent' => ['sometimes', 'nullable', 'integer', 'min:0', 'gte:min_rent'],
            'bedrooms' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:50'],
            'bathrooms' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:50'],

            // Geo radius search.
            'lat' => ['sometimes', 'nullable', 'numeric', 'between:-90,90', 'required_with:lng'],
            'lng' => ['sometimes', 'nullable', 'numeric', 'between:-180,180', 'required_with:lat'],
            'radius' => ['sometimes', 'nullable', 'numeric', 'between:0.1,100'], // km

            'sort' => ['sometimes', 'nullable', Rule::in(['nearest', 'price_asc', 'price_desc', 'oldest', 'popular'])],
            'page' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'between:1,100'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        $data = $this->validated();
        unset($data['page'], $data['limit']);
        $data['_scope'] = 'public';

        return $data;
    }

    public function perPage(): int
    {
        return (int) ($this->validated('limit') ?? 15);
    }

    public function pageNumber(): int
    {
        return (int) ($this->validated('page') ?? 1);
    }
}
