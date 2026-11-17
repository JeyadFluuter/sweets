<?php

namespace App\Http\Requests\Admin\V1\Order;

use Illuminate\Foundation\Http\FormRequest;

class RejectOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reject', $this->route('order')) ?? false;
    }

    public function rules(): array
    {
        return [
            'reason_id' => ['required', 'exists:rejection_reasons,id'],
            'note' => ['nullable', 'string'],
        ];
    }
}
