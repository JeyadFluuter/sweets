<?php

namespace App\Http\Requests\Admin\V1\Setting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'settings' => ['required', 'array', 'min:1'],
            'settings.*.key' => ['required', 'string', 'max:100'],
            'settings.*.value' => ['nullable'],
            'settings.*.group' => ['sometimes', 'string', 'max:50'],
            'settings.*.type' => ['sometimes', 'string', 'in:string,integer,float,boolean,json'],
            'settings.*.is_sensitive' => ['sometimes', 'boolean'],
        ];
    }
}
