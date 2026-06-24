<?php

declare(strict_types=1);

namespace App\Http\Requests\Blog;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::ManageBlog) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:200'],
            'excerpt' => ['sometimes', 'nullable', 'string', 'max:300'],
            'body' => ['sometimes', 'string'],
            'cover_image' => ['sometimes', 'nullable', 'string', 'max:255'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:blog_categories,id'],
            'status' => ['sometimes', Rule::in(['draft', 'published'])],
            'tags' => ['sometimes', 'array', 'max:20'],
            'tags.*' => ['string', 'max:50'],
        ];
    }
}
