<?php

use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;

it('blocks customers from admin api routes', function () {
    $customer = Customer::factory()->create();
    Sanctum::actingAs($customer);

    $this->getJson('/api/v1/admin/orders')
        ->assertForbidden();
});

it('allows staff users on admin api routes', function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole(\App\Enums\Role::SuperAdmin->value);
    Sanctum::actingAs($admin, ['admin']);

    $this->getJson('/api/v1/admin/orders')
        ->assertOk()
        ->assertJsonPath('success', true);
});
