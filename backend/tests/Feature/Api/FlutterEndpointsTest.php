<?php

use App\Models\AppNotification;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\Product;
use Laravel\Sanctum\Sanctum;

it('returns me profile', function () {
    $customer = Customer::factory()->create(['name' => 'نور']);
    Sanctum::actingAs($customer, ['customer']);

    $this->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.name', 'نور');
});

it('updates me profile', function () {
    $customer = Customer::factory()->create(['name' => 'قديم']);
    Sanctum::actingAs($customer, ['customer']);

    $this->patchJson('/api/v1/me', ['name' => 'جديد'])
        ->assertOk()
        ->assertJsonPath('data.name', 'جديد');
});

it('shows category with children and products', function () {
    $parent = Category::factory()->create(['name' => 'حلويات', 'is_active' => true, 'parent_id' => null]);
    $child = Category::factory()->create(['name' => 'بقلاوة', 'is_active' => true, 'parent_id' => $parent->id]);
    Product::factory()->create(['category_id' => $child->id, 'name' => 'بقلاوة فستق', 'is_available' => true]);

    $response = $this->getJson("/api/v1/categories/{$parent->id}");

    $response->assertOk()
        ->assertJsonPath('data.name', 'حلويات');

    $children = collect($response->json('data.children'));
    expect($children->pluck('name'))->toContain('بقلاوة');
    expect(collect($children->first()['products'] ?? [])->pluck('name'))->toContain('بقلاوة فستق');
});

it('lists all popular products and collections', function () {
    Product::factory()->create(['is_available' => true]);
    Collection::factory()->create(['is_active' => true, 'starts_at' => null, 'ends_at' => null]);

    $this->getJson('/api/v1/products/popular')->assertOk()->assertJsonPath('success', true);
    $this->getJson('/api/v1/collections')->assertOk()->assertJsonPath('success', true);
    $this->getJson('/api/v1/banners')->assertOk()->assertJsonPath('success', true);
});

it('deletes a notification', function () {
    $customer = Customer::factory()->create();
    $notification = AppNotification::query()->create([
        'customer_id' => $customer->id,
        'type' => 'order_status',
        'title' => 'اختبار',
        'body' => 'نص',
    ]);
    Sanctum::actingAs($customer, ['customer']);

    $this->deleteJson("/api/v1/notifications/{$notification->id}")
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(AppNotification::query()->whereKey($notification->id)->exists())->toBeFalse();
});
