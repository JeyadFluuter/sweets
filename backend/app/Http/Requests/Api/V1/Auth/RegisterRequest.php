<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Support\LibyanPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $phone = LibyanPhone::normalize($this->input('phone'));
        $email = $this->input('email');

        $this->merge([
            'phone' => $phone,
            'email' => filled($email) ? trim((string) $email) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => [
                'required',
                'string',
                LibyanPhone::validationRule(),
                Rule::unique('customers', 'phone'),
            ],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('customers', 'email')],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب.',
            'phone.required' => 'رقم الهاتف الليبي مطلوب.',
            'phone.unique' => 'رقم الهاتف مسجّل مسبقاً.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.unique' => 'البريد الإلكتروني مسجّل مسبقاً.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ];
    }
}
