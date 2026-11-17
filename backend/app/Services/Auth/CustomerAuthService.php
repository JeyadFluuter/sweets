<?php

namespace App\Services\Auth;

use App\Models\Customer;
use App\Support\LibyanPhone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CustomerAuthService
{
    /**
     * @param  array{name:string,phone:string,password:string,email?:string|null}  $data
     * @return array{customer: Customer, token: string}
     */
    public function register(array $data): array
    {
        $phone = LibyanPhone::normalize($data['phone'] ?? null);

        if (! $phone) {
            throw ValidationException::withMessages([
                'phone' => ['رقم الهاتف يجب أن يكون ليبياً صالحاً (مثال: 0912345678).'],
            ]);
        }

        return DB::transaction(function () use ($data, $phone) {
            $customer = Customer::query()->create([
                'name' => $data['name'],
                'phone' => $phone,
                'email' => filled($data['email'] ?? null) ? $data['email'] : null,
                'password' => $data['password'],
                'is_guest' => false,
            ]);

            $token = $customer->createToken('customer-app', ['customer'])->plainTextToken;

            return compact('customer', 'token');
        });
    }

    /**
     * @param  array{phone:string,password:string}  $credentials
     * @return array{customer: Customer, token: string}
     */
    public function login(array $credentials): array
    {
        $phone = LibyanPhone::normalize($credentials['phone'] ?? null);

        if (! $phone) {
            throw ValidationException::withMessages([
                'phone' => ['رقم الهاتف يجب أن يكون ليبياً صالحاً (مثال: 0912345678).'],
            ]);
        }

        $customer = Customer::query()->where('phone', $phone)->first();

        if (! $customer || ! $customer->password || ! Hash::check($credentials['password'], $customer->password)) {
            throw ValidationException::withMessages([
                'phone' => [__('messages.auth.invalid_credentials')],
            ]);
        }

        if ($customer->is_blocked) {
            throw ValidationException::withMessages([
                'phone' => [__('messages.auth.blocked')],
            ]);
        }

        $token = $customer->createToken('customer-app', ['customer'])->plainTextToken;

        return compact('customer', 'token');
    }

    /**
     * @return array{customer: Customer, token: string}
     */
    public function guest(): array
    {
        $customer = Customer::query()->create([
            'name' => 'ضيف',
            'is_guest' => true,
        ]);

        $token = $customer->createToken('customer-guest', ['guest'])->plainTextToken;

        return compact('customer', 'token');
    }

    public function logout(Customer $customer): void
    {
        $customer->currentAccessToken()?->delete();
    }

    /**
     * @param  array{name?:string,phone?:string,email?:string|null,password?:string}  $data
     */
    public function updateProfile(Customer $customer, array $data): Customer
    {
        $payload = [];
        foreach (['name', 'phone', 'email', 'password'] as $key) {
            if (array_key_exists($key, $data)) {
                $payload[$key] = $data[$key];
            }
        }

        if (! empty($payload['phone']) && ! empty($payload['password']) && $customer->is_guest) {
            $payload['is_guest'] = false;
        }

        $customer->update($payload);

        return $customer->fresh();
    }
}
