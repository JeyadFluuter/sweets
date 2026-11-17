<?php

use App\Enums\Role;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::SuperAdmin->value);
    Sanctum::actingAs($this->admin, ['admin']);
});

it('creates a category as admin', function () {
    $response = $this->postJson('/api/v1/admin/categories', [
        'name' => 'حلو',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'حلو');

    expect(Category::query()->where('name', 'حلو')->exists())->toBeTrue();
});

it('creates a product with tags', function () {
    $category = Category::factory()->create();
    $tag = \App\Models\Tag::factory()->create();

    $response = $this->postJson('/api/v1/admin/products', [
        'name' => 'بقلاوة',
        'price' => 45.50,
        'category_id' => $category->id,
        'tag_ids' => [$tag->id],
    ]);

    $response->assertCreated()->assertJsonPath('data.name', 'بقلاوة');

    $product = Product::query()->where('name', 'بقلاوة')->first();
    expect($product)->not->toBeNull()
        ->and($product->tags)->toHaveCount(1);
});

it('logs in admin and returns token', function () {
    $user = User::factory()->create([
        'email' => 'manager@sweets.ly',
        'password' => 'password',
    ]);
    $user->assignRole(Role::SuperAdmin->value);

    $this->postJson('/api/v1/admin/auth/login', [
        'email' => 'manager@sweets.ly',
        'password' => 'password',
    ])->assertOk()
        ->assertJsonStructure(['data' => ['token', 'user']]);
});
