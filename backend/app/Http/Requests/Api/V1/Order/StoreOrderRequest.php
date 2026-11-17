<?php

namespace App\Http\Requests\Api\V1\Order;

use App\Support\LibyanPhone;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('recipient_phone')) {
            $this->merge([
                'recipient_phone' => LibyanPhone::normalize($this->input('recipient_phone')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'delivery_type' => ['required', 'in:pickup,delivery'],
            'fulfillment_date' => ['required', 'date'],
            'fulfillment_time' => ['required', 'date_format:H:i'],
            'address_text' => ['required_if:delivery_type,delivery', 'nullable', 'string'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'recipient_is_self' => ['sometimes', 'boolean'],
            'recipient_name' => ['required_if:recipient_is_self,false', 'nullable', 'string', 'max:120'],
            'recipient_phone' => ['required_if:recipient_is_self,false', 'nullable', 'string', LibyanPhone::validationRule()],
            'customer_note' => ['nullable', 'string'],
        ];
    }
}
