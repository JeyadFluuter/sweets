<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\User\StoreUserRequest;
use App\Http\Requests\Admin\V1\User\UpdateUserRequest;
use App\Http\Resources\Admin\V1\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\Users\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService)
    {
        $this->authorizeResource(User::class, 'user');
    }

    public function index(Request $request): JsonResponse
    {
        $users = $this->userService->list($request->only(['search', 'is_active', 'role']), $request->integer('per_page', 15));

        return ApiResponse::success(UserResource::collection($users)->response()->getData(true));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return ApiResponse::success(new UserResource($user), __('messages.users.created'), 201);
    }

    public function show(User $user): JsonResponse
    {
        return ApiResponse::success(new UserResource($user->load('roles', 'permissions')));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user = $this->userService->update($user, $request->validated());

        return ApiResponse::success(new UserResource($user), __('messages.users.updated'));
    }

    public function destroy(User $user): JsonResponse
    {
        $this->userService->delete($user);

        return ApiResponse::success(null, __('messages.users.deleted'));
    }
}
