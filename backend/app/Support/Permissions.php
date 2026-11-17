<?php

namespace App\Support;

class Permissions
{
    public const MODULES = [
        'users',
        'roles',
        'customers',
        'categories',
        'products',
        'tags',
        'banners',
        'collections',
        'coupons',
        'orders',
        'payments',
        'purchases',
        'suppliers',
        'stock',
        'expenses',
        'reports',
        'notifications',
        'settings',
        'ai',
    ];

    public const ACTIONS = [
        'view',
        'create',
        'update',
        'delete',
        'export',
    ];

    public const SPECIAL = [
        'settings.firebase',
        'settings.ai',
        'settings.sensitive',
    ];

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        $permissions = [];

        foreach (self::MODULES as $module) {
            foreach (self::ACTIONS as $action) {
                $permissions[] = "{$module}.{$action}";
            }
        }

        return array_values(array_unique([...$permissions, ...self::SPECIAL]));
    }
}
