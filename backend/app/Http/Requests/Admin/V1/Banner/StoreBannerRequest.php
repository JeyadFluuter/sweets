<?php

namespace App\Http\Requests\Admin\V1\Banner;

use App\Enums\BannerLinkType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('banners.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:150'],
            'link_type' => ['required', Rule::enum(BannerLinkType::class)],
            'link_id' => ['nullable', 'integer', 'required_unless:link_type,none'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => ['required', 'image', 'max:5120', 'mimes:jpg,jpeg,png,webp,gif'],
        ];
    }
}
