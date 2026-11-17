<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Support\LibyanPhone;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => LibyanPhone::normalize($this->input('phone')),
        ]);
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', LibyanPhone::validationRule()],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'رقم الهاتف الليبي مطلوب.',
            'password.required' => 'كلمة المرور مطلوبة.',
        ];
    }
}
