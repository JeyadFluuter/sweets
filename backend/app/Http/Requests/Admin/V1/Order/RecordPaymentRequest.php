<?php

namespace App\Http\Requests\Admin\V1\Order;

use Illuminate\Foundation\Http\FormRequest;

class RecordPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('payments', $this->route('order')) ?? false;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:advance,remainder'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['nullable', 'in:cash,transfer,card_on_delivery,manual,online'],
            'reference' => ['nullable', 'string'],
        ];
    }
}
