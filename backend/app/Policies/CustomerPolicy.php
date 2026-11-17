<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole(Role::SuperAdmin->value) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('customers.view');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->can('customers.view');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->can('customers.update');
    }
}
