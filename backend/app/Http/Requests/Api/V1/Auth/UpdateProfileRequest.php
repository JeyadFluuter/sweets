<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Support\LibyanPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $this->merge(['phone' => LibyanPhone::normalize($this->input('phone'))]);
        }

        if ($this->has('email')) {
            $email = $this->input('email');
            $this->merge(['email' => filled($email) ? trim((string) $email) : null]);
        }
    }

    public function rules(): array
    {
        $customerId = $this->user()?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'phone' => [
                'sometimes',
                'required',
                'string',
                LibyanPhone::validationRule(),
                Rule::unique('customers', 'phone')->ignore($customerId),
            ],
            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:150',
                Rule::unique('customers', 'email')->ignore($customerId),
            ],
            'password' => ['sometimes', 'required', 'string', 'min:6', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب.',
            'phone.required' => 'رقم الهاتف مطلوب.',
            'phone.unique' => 'رقم الهاتف مسجّل مسبقاً.',
            'email.email' => 'صيغة البريد غير صحيحة.',
            'email.unique' => 'البريد مسجّل مسبقاً.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ];
    }
}
