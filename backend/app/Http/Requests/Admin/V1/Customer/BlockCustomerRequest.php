<?php

namespace App\Http\Requests\Admin\V1\Customer;

use Illuminate\Foundation\Http\FormRequest;

class BlockCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('customers.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'is_blocked' => ['required', 'boolean'],
            'internal_notes' => ['nullable', 'string'],
        ];
    }
}
