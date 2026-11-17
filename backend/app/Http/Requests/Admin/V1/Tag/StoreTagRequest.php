<?php

namespace App\Http\Requests\Admin\V1\Tag;

use App\Enums\TagType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tags.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:120', 'unique:tags,slug'],
            'type' => ['required', Rule::enum(TagType::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
