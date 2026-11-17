<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Coupon;
use App\Models\User;

class CouponPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole(Role::SuperAdmin->value) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('coupons.view');
    }

    public function view(User $user, Coupon $coupon): bool
    {
        return $user->can('coupons.view');
    }

    public function create(User $user): bool
    {
        return $user->can('coupons.create');
    }

    public function update(User $user, Coupon $coupon): bool
    {
        return $user->can('coupons.update');
    }

    public function delete(User $user, Coupon $coupon): bool
    {
        return $user->can('coupons.delete');
    }
}
