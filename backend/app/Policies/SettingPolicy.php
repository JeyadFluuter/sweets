<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Setting;
use App\Models\User;

class SettingPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole(Role::SuperAdmin->value) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('settings.view');
    }

    public function update(User $user): bool
    {
        return $user->can('settings.update');
    }
}
