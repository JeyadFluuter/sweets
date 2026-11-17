<?php

namespace App\Http\Requests\Admin\V1\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('categories.update') ?? false;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')?->id ?? $this->route('category');

        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:150', Rule::unique('categories', 'slug')->ignore($categoryId)],
            'parent_id' => ['nullable', 'exists:categories,id', Rule::notIn([(int) $categoryId])],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => ['sometimes', 'nullable', 'image', 'max:4096', 'mimes:jpg,jpeg,png,webp,gif'],
        ];
    }
}
