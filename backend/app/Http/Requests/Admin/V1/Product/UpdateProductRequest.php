<?php

namespace App\Http\Requests\Admin\V1\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('products.update') ?? false;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id ?? $this->route('product');

        return [
            'name' => ['sometimes', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:200', Rule::unique('products', 'slug')->ignore($productId)],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0'],
            'discount_starts_at' => ['nullable', 'date'],
            'discount_ends_at' => ['nullable', 'date', 'after_or_equal:discount_starts_at'],
            'category_id' => ['sometimes', 'exists:categories,id'],
            'is_available' => ['sometimes', 'boolean'],
            'min_order_qty' => ['sometimes', 'integer', 'min:1'],
            'max_order_qty' => ['sometimes', 'integer', 'min:1', 'gte:min_order_qty'],
            'ingredients' => ['nullable', 'string'],
            'allergen_alerts' => ['nullable', 'string'],
            'ai_suggestable' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'images' => ['sometimes', 'nullable', 'array', 'max:8'],
            'images.*' => ['image', 'max:5120', 'mimes:jpg,jpeg,png,webp,gif'],
        ];
    }
}
