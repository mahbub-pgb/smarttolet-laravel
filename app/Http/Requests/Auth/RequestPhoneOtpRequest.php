<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class RequestPhoneOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('mobile')) {
            $this->merge(['mobile' => PhoneNumber::normalize((string) $this->input('mobile'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mobile' => ['required', 'string', 'regex:/^01[3-9]\d{8}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.regex' => 'Enter a valid Bangladeshi mobile number (e.g. 01712345678).',
        ];
    }

    public function mobile(): string
    {
        return (string) $this->validated('mobile');
    }
}
