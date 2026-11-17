<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Purchase;
use App\Models\User;

class PurchasePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole(Role::SuperAdmin->value) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('purchases.view');
    }

    public function view(User $user, Purchase $purchase): bool
    {
        return $user->can('purchases.view');
    }

    public function create(User $user): bool
    {
        return $user->can('purchases.create');
    }
}
