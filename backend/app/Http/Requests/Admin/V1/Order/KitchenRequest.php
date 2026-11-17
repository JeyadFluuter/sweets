<?php

namespace App\Http\Requests\Admin\V1\Order;

use Illuminate\Foundation\Http\FormRequest;

class KitchenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', \App\Models\Order::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
        ];
    }
}
