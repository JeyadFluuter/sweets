<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\UpdateProfileRequest;
use App\Http\Resources\Api\V1\CustomerResource;
use App\Http\Responses\ApiResponse;
use App\Services\Auth\CustomerAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly CustomerAuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return ApiResponse::success([
            'token' => $result['token'],
            'customer' => new CustomerResource($result['customer']),
        ], __('messages.auth.registered'), 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return ApiResponse::success([
            'token' => $result['token'],
            'customer' => new CustomerResource($result['customer']),
        ], __('messages.auth.logged_in'));
    }

    public function guest(): JsonResponse
    {
        $result = $this->authService->guest();

        return ApiResponse::success([
            'token' => $result['token'],
            'customer' => new CustomerResource($result['customer']),
        ], __('messages.auth.guest_created'), 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return ApiResponse::success(null, __('messages.auth.logged_out'));
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(new CustomerResource($request->user()));
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $customer = $this->authService->updateProfile($request->user(), $request->validated());

        return ApiResponse::success(new CustomerResource($customer), 'تم تحديث البيانات');
    }
}
