<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Collection;
use App\Models\User;

class CollectionPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole(Role::SuperAdmin->value) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('collections.view');
    }

    public function view(User $user, Collection $collection): bool
    {
        return $user->can('collections.view');
    }

    public function create(User $user): bool
    {
        return $user->can('collections.create');
    }

    public function update(User $user, Collection $collection): bool
    {
        return $user->can('collections.update');
    }

    public function delete(User $user, Collection $collection): bool
    {
        return $user->can('collections.delete');
    }
}
