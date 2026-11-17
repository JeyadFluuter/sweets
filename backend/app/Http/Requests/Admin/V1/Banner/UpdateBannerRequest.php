<?php

namespace App\Http\Requests\Admin\V1\Banner;

use App\Enums\BannerLinkType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('banners.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:150'],
            'link_type' => ['sometimes', Rule::enum(BannerLinkType::class)],
            'link_id' => ['nullable', 'integer'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => ['sometimes', 'nullable', 'image', 'max:5120', 'mimes:jpg,jpeg,png,webp,gif'],
        ];
    }
}
