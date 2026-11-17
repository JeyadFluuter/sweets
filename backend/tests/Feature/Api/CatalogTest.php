<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;

it('lists active categories', function () {
    Category::factory()->create(['name' => 'حلو', 'is_active' => true]);
    Category::factory()->create(['name' => 'مخفي', 'is_active' => false]);

    $response = $this->getJson('/api/v1/categories');

    $response->assertOk()
        ->assertJsonPath('success', true);

    $names = collect($response->json('data'))->pluck('name');
    expect($names)->toContain('حلو')->not->toContain('مخفي');
});

it('shows available product details', function () {
    $product = Product::factory()->create(['is_available' => true]);
    $tag = Tag::factory()->create();
    $product->tags()->attach($tag);

    $this->getJson("/api/v1/products/{$product->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $product->id)
        ->assertJsonPath('success', true);
});

it('lists category products with search', function () {
    $category = Category::factory()->create(['is_active' => true]);
    Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'بقلاوة فاخرة',
        'is_available' => true,
    ]);
    Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'كنافة',
        'is_available' => true,
    ]);

    $response = $this->getJson("/api/v1/categories/{$category->id}/products?search=".urlencode('بقلاوة'));

    $response->assertOk();

    $payload = $response->json('data.products');
    $items = $payload['data'] ?? $payload ?? [];

    expect(collect($items)->pluck('name')->all())->toContain('بقلاوة فاخرة');
});
