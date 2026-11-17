<?php

namespace App\Http\Requests\Admin\V1\Tag;

use App\Enums\TagType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tags.update') ?? false;
    }

    public function rules(): array
    {
        $tagId = $this->route('tag')?->id ?? $this->route('tag');

        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:120', Rule::unique('tags', 'slug')->ignore($tagId)],
            'type' => ['sometimes', Rule::enum(TagType::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
