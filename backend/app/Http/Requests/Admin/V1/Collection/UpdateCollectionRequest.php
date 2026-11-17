<?php

namespace App\Http\Requests\Admin\V1\Collection;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('collections.update') ?? false;
    }

    public function rules(): array
    {
        $collectionId = $this->route('collection')?->id ?? $this->route('collection');

        return [
            'name' => ['sometimes', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:200', Rule::unique('collections', 'slug')->ignore($collectionId)],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.product_id' => ['required_with:items', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
        ];
    }
}
