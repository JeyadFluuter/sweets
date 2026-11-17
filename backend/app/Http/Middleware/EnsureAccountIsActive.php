<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use App\Models\Customer;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->isAccountValid()) {
            return ApiResponse::error(__('messages.auth.inactive_or_expired'), null, 403);
        }

        if ($user instanceof Customer && $user->is_blocked) {
            return ApiResponse::error(__('messages.auth.blocked'), null, 403);
        }

        return $next($request);
    }
}
