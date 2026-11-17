<?php

namespace App\Services\Users;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return User::query()
            ->with('roles', 'permissions')
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when($filters['role'] ?? null, function ($q, $role) {
                $q->role($role);
            })
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $roles = $data['roles'] ?? [];
            $permissions = $data['permissions'] ?? [];
            unset($data['roles'], $data['permissions']);

            $user = User::query()->create($data);

            if ($roles) {
                $user->syncRoles($roles);
            }

            if ($permissions) {
                $user->syncPermissions($permissions);
            }

            activity()->performedOn($user)->log('إنشاء مستخدم');

            return $user->load('roles', 'permissions');
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $roles = $data['roles'] ?? null;
            $permissions = $data['permissions'] ?? null;
            unset($data['roles'], $data['permissions']);

            if (empty($data['password'])) {
                unset($data['password']);
            }

            $user->update($data);

            if (is_array($roles)) {
                $user->syncRoles($roles);
            }

            if (is_array($permissions)) {
                $user->syncPermissions($permissions);
            }

            activity()->performedOn($user)->log('تحديث مستخدم');

            return $user->fresh(['roles', 'permissions']);
        });
    }

    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            activity()->performedOn($user)->log('حذف مستخدم');
            $user->delete();
        });
    }
}
