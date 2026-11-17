<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\Auth\LoginRequest;
use App\Http\Resources\Admin\V1\UserResource;
use App\Http\Responses\ApiResponse;
use App\Services\Auth\AdminAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AdminAuthService $authService) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return ApiResponse::success([
            'token' => $result['token'],
            'user' => new UserResource($result['user']->load('roles', 'permissions')),
        ], __('messages.auth.logged_in'));
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return ApiResponse::success(null, __('messages.auth.logged_out'));
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(
            new UserResource($request->user()->load('roles', 'permissions'))
        );
    }
}
