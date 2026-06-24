<?php

declare(strict_types=1);

namespace App\Http\Requests\Listing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModerateListingRequest extends FormRequest
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
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'reason' => ['required_if:action,reject', 'nullable', 'string', 'max:255'],
        ];
    }
}
