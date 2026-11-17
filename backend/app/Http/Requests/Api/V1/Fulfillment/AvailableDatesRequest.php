<?php

namespace App\Http\Requests\Api\V1\Fulfillment;

use Illuminate\Foundation\Http\FormRequest;

class AvailableDatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delivery_type' => ['required', 'in:pickup,delivery'],
        ];
    }
}
