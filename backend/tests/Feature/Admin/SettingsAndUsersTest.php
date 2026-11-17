<?php

use App\Enums\Role;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::SuperAdmin->value);
    Sanctum::actingAs($this->admin, ['admin']);
});

it('updates settings', function () {
    $this->seed(SettingSeeder::class);

    $response = $this->putJson('/api/v1/admin/settings', [
        'settings' => [
            [
                'key' => 'delivery_fee',
                'value' => 15,
                'group' => 'fulfillment',
                'type' => 'float',
            ],
        ],
    ]);

    $response->assertOk()->assertJsonPath('success', true);
    expect(Setting::getValue('delivery_fee'))->toEqual(15.0);
});

it('creates a staff user with role', function () {
    $response = $this->postJson('/api/v1/admin/users', [
        'name' => 'موظف طلبات',
        'email' => 'orders@sweets.ly',
        'password' => 'password123',
        'roles' => [Role::OrderStaff->value],
        'is_active' => true,
    ]);

    $response->assertCreated()->assertJsonPath('data.email', 'orders@sweets.ly');

    $user = User::query()->where('email', 'orders@sweets.ly')->first();
    expect($user->hasRole(Role::OrderStaff->value))->toBeTrue();
});
