<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403, __('messages.auth.forbidden'));
        }

        if (! $user->isAccountValid()) {
            abort(403, __('messages.auth.inactive_or_expired'));
        }

        $token = $user->currentAccessToken();
        if ($token instanceof PersonalAccessToken && ! $token->can('admin')) {
            abort(403, __('messages.auth.forbidden'));
        }

        return $next($request);
    }
}
