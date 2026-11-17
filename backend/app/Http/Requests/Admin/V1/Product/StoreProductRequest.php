<?php

namespace App\Http\Requests\Admin\V1\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('products.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:200', 'unique:products,slug'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'discount_starts_at' => ['nullable', 'date'],
            'discount_ends_at' => ['nullable', 'date', 'after_or_equal:discount_starts_at'],
            'category_id' => ['required', 'exists:categories,id'],
            'is_available' => ['sometimes', 'boolean'],
            'min_order_qty' => ['sometimes', 'integer', 'min:1'],
            'max_order_qty' => ['sometimes', 'integer', 'min:1', 'gte:min_order_qty'],
            'ingredients' => ['nullable', 'string'],
            'allergen_alerts' => ['nullable', 'string'],
            'ai_suggestable' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['image', 'max:5120', 'mimes:jpg,jpeg,png,webp,gif'],
        ];
    }
}
