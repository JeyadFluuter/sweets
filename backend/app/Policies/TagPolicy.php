<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Tag;
use App\Models\User;

class TagPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole(Role::SuperAdmin->value) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('tags.view');
    }

    public function view(User $user, Tag $tag): bool
    {
        return $user->can('tags.view');
    }

    public function create(User $user): bool
    {
        return $user->can('tags.create');
    }

    public function update(User $user, Tag $tag): bool
    {
        return $user->can('tags.update');
    }

    public function delete(User $user, Tag $tag): bool
    {
        return $user->can('tags.delete');
    }
}
