<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (Permissions::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $all = Permissions::all();

        $map = [
            Role::SuperAdmin->value => $all,
            Role::SalesManager->value => [
                'customers.view', 'customers.update',
                'orders.view', 'orders.create', 'orders.update', 'orders.export',
                'products.view', 'categories.view', 'coupons.view', 'coupons.create', 'coupons.update',
                'reports.view', 'reports.export',
            ],
            Role::Accountant->value => [
                'orders.view', 'payments.view', 'payments.create', 'payments.update',
                'expenses.view', 'expenses.create', 'expenses.update', 'expenses.export',
                'reports.view', 'reports.export', 'purchases.view',
            ],
            Role::ContentSupervisor->value => [
                'categories.view', 'categories.create', 'categories.update',
                'products.view', 'products.create', 'products.update',
                'tags.view', 'tags.create', 'tags.update',
                'banners.view', 'banners.create', 'banners.update', 'banners.delete',
                'collections.view', 'collections.create', 'collections.update',
            ],
            Role::OrderStaff->value => [
                'orders.view', 'orders.update',
                'customers.view',
                'products.view', 'categories.view',
            ],
            Role::PurchasingSupervisor->value => [
                'purchases.view', 'purchases.create', 'purchases.update', 'purchases.export',
                'stock.view', 'stock.create', 'stock.update',
                'suppliers.view', 'suppliers.create', 'suppliers.update',
            ],
        ];

        foreach (Role::cases() as $role) {
            $spatieRole = SpatieRole::findOrCreate($role->value, 'web');
            $spatieRole->syncPermissions($map[$role->value] ?? []);
        }
    }
}
