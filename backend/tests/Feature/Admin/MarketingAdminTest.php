<?php

use App\Enums\BannerLinkType;
use App\Enums\CouponScope;
use App\Enums\CouponType;
use App\Enums\Role;
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

it('creates a banner', function () {
    $this->postJson('/api/v1/admin/banners', [
        'title' => 'عرض خاص',
        'link_type' => BannerLinkType::None->value,
        'sort_order' => 1,
        'is_active' => true,
    ])->assertCreated()
        ->assertJsonPath('data.title', 'عرض خاص');
});

it('creates a collection with items', function () {
    $product = Product::factory()->create();

    $this->postJson('/api/v1/admin/collections', [
        'name' => 'باقة مناسبات',
        'price' => 150,
        'items' => [
            ['product_id' => $product->id, 'quantity' => 2],
        ],
    ])->assertCreated()
        ->assertJsonPath('data.name', 'باقة مناسبات');
});

it('creates a coupon', function () {
    $this->postJson('/api/v1/admin/coupons', [
        'code' => 'SAVE15',
        'type' => CouponType::Fixed->value,
        'value' => 15,
        'min_order_amount' => 40,
        'scope' => CouponScope::All->value,
        'is_active' => true,
    ])->assertCreated()
        ->assertJsonPath('data.code', 'SAVE15');
});
