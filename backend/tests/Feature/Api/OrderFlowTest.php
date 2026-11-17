<?php

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\DeliverySlot;
use App\Models\Product;
use App\Models\RejectionReason;
use App\Models\User;
use Database\Seeders\FulfillmentSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(SettingSeeder::class);
    $this->seed(FulfillmentSeeder::class);
    $this->seed(RolePermissionSeeder::class);

    config(['payments.driver' => 'fake']);

    $this->customer = Customer::factory()->create(['phone' => '0912003004']);
    Sanctum::actingAs($this->customer);
});

it('places reviews accepts and pays an order', function () {
    $product = Product::factory()->create([
        'is_available' => true,
        'price' => 40,
    ]);

    $this->postJson('/api/v1/cart/items', [
        'item_type' => 'product',
        'item_id' => $product->id,
        'quantity' => 2,
    ])->assertCreated();

    $dates = $this->getJson('/api/v1/fulfillment/available-dates?delivery_type=pickup')
        ->assertOk()
        ->json('data.dates');

    expect($dates)->not->toBeEmpty();
    $date = $dates[0];

    $slots = $this->getJson('/api/v1/fulfillment/slots?delivery_type=pickup&date='.$date)
        ->assertOk()
        ->json('data.slots');

    expect($slots)->not->toBeEmpty();
    $time = $slots[0]['start'];

    $orderResponse = $this->postJson('/api/v1/orders', [
        'delivery_type' => 'pickup',
        'fulfillment_date' => $date,
        'fulfillment_time' => $time,
        'recipient_is_self' => true,
    ])->assertCreated();

    $orderId = $orderResponse->json('data.id');
    expect($orderResponse->json('data.status'))->toBe(OrderStatus::PendingReview->value);

    $admin = User::factory()->create();
    $admin->assignRole(\App\Enums\Role::SuperAdmin->value);
    Sanctum::actingAs($admin, ['admin']);

    $this->postJson("/api/v1/admin/orders/{$orderId}/accept")
        ->assertOk()
        ->assertJsonPath('data.status', OrderStatus::AwaitingPayment->value);

    Sanctum::actingAs($this->customer);

    $this->postJson("/api/v1/orders/{$orderId}/pay", [
        'payment_plan' => 100,
    ])->assertOk()
        ->assertJsonPath('data.order.status', OrderStatus::Confirmed->value)
        ->assertJsonPath('data.order.payment_status', 'paid');
});

it('rejects guest order placement', function () {
    $guest = Customer::factory()->guest()->create();
    Sanctum::actingAs($guest);

    $product = Product::factory()->create(['is_available' => true]);
    $this->postJson('/api/v1/cart/items', [
        'item_type' => 'product',
        'item_id' => $product->id,
    ])->assertCreated();

    $this->postJson('/api/v1/orders', [
        'delivery_type' => 'pickup',
        'fulfillment_date' => now()->addDay()->toDateString(),
        'fulfillment_time' => '18:00',
    ])->assertStatus(422);
});
