<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@sweets.ly'],
            [
                'name' => 'المدير العام',
                'phone' => '0910000000',
                'password' => 'password',
                'is_active' => true,
                'allowed_modules' => null,
            ]
        );

        $admin->syncRoles([Role::SuperAdmin->value]);
    }
}
