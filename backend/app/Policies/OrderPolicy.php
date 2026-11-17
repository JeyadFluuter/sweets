<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole(Role::SuperAdmin->value) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('orders.view');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->can('orders.view');
    }

    public function create(User $user): bool
    {
        return $user->can('orders.create') || $user->can('orders.update');
    }

    public function update(User $user, Order $order): bool
    {
        return $user->can('orders.update');
    }

    public function accept(User $user, Order $order): bool
    {
        return $user->can('orders.update');
    }

    public function reject(User $user, Order $order): bool
    {
        return $user->can('orders.update');
    }

    public function payments(User $user, Order $order): bool
    {
        return $user->can('payments.create') || $user->can('orders.update');
    }
}
