<?php

namespace App\Http\Requests\Api\V1\Cart;

use Illuminate\Foundation\Http\FormRequest;

class StoreCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_type' => ['required', 'in:product,collection'],
            'item_id' => ['required', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
