<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminAuthService
{
    /**
     * @param  array{email:string,password:string}  $credentials
     * @return array{user: User, token: string}
     */
    public function login(array $credentials): array
    {
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('messages.auth.invalid_credentials')],
            ]);
        }

        if (! $user->isAccountValid()) {
            throw ValidationException::withMessages([
                'email' => [__('messages.auth.inactive_or_expired')],
            ]);
        }

        $token = $user->createToken('admin-panel', ['admin'])->plainTextToken;

        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->log('تسجيل دخول للوحة التحكم');

        return compact('user', 'token');
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
