<?php

use App\Enums\CouponScope;
use App\Enums\CouponType;
use App\Models\Banner;
use App\Models\Collection;
use App\Models\Coupon;
use App\Models\Product;

it('returns home payload', function () {
    Banner::factory()->create(['title' => 'بانر تجريبي']);
    $product = Product::factory()->create([
        'is_available' => true,
        'discount_price' => 15,
        'price' => 25,
        'discount_starts_at' => now()->subDay(),
        'discount_ends_at' => now()->addDay(),
    ]);

    $collection = Collection::factory()->create(['name' => 'باقة العيد']);
    $collection->items()->create([
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $response = $this->getJson('/api/v1/home');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                'banners',
                'categories',
                'popular_products',
                'offers',
                'collections',
            ],
        ]);

    expect(collect($response->json('data.offers'))->pluck('id'))->toContain($product->id);
    expect(collect($response->json('data.collections'))->pluck('name'))->toContain('باقة العيد');
});

it('applies a valid coupon', function () {
    $product = Product::factory()->create(['price' => 50, 'is_available' => true]);

    Coupon::factory()->create([
        'code' => 'WELCOME10',
        'type' => CouponType::Percent,
        'value' => 10,
        'max_discount' => 20,
        'min_order_amount' => 30,
        'scope' => CouponScope::All,
        'is_active' => true,
    ]);

    $this->postJson('/api/v1/coupons/apply', [
        'code' => 'welcome10',
        'subtotal' => 50,
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 50],
        ],
    ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.discount', 5);
});

it('rejects invalid coupon code', function () {
    $this->postJson('/api/v1/coupons/apply', [
        'code' => 'MISSING',
        'subtotal' => 100,
    ])->assertStatus(422)
        ->assertJsonPath('success', false);
});

it('shows a visible collection', function () {
    $product = Product::factory()->create();
    $collection = Collection::factory()->create(['is_active' => true]);
    $collection->items()->create([
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $this->getJson("/api/v1/collections/{$collection->id}")
        ->assertOk()
        ->assertJsonPath('data.name', $collection->name)
        ->assertJsonMissingPath('data.lead_time_hours');
});
