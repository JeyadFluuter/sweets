<?php

namespace App\Http\Requests\Admin\V1\Order;

use Illuminate\Foundation\Http\FormRequest;

class SuggestSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('order')) ?? false;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'time' => ['required', 'date_format:H:i'],
            'note' => ['nullable', 'string'],
        ];
    }
}
