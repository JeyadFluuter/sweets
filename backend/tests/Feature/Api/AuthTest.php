<?php

use App\Models\Customer;

it('registers a customer with libyan phone without email', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'أحمد',
        'phone' => '0912345678',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.customer.phone', '0912345678')
        ->assertJsonPath('data.customer.email', null);

    expect(Customer::query()->where('phone', '0912345678')->exists())->toBeTrue();
});

it('normalizes international libyan phone on register', function () {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'سارة',
        'phone' => '+218912345679',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ])->assertCreated()
        ->assertJsonPath('data.customer.phone', '0912345679');
});

it('rejects non libyan phone numbers', function () {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'أحمد',
        'phone' => '0501234567',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ])->assertStatus(422)
        ->assertJsonPath('success', false);
});

it('logs in with libyan phone', function () {
    Customer::factory()->create([
        'phone' => '0922222222',
        'password' => 'secret123',
        'email' => null,
    ]);

    $this->postJson('/api/v1/auth/login', [
        'phone' => '218922222222',
        'password' => 'secret123',
    ])->assertOk()->assertJsonPath('success', true);
});

it('creates a guest session', function () {
    $this->postJson('/api/v1/auth/guest')
        ->assertCreated()
        ->assertJsonPath('data.customer.is_guest', true);
});

it('rejects blocked customers', function () {
    Customer::factory()->blocked()->create([
        'phone' => '0933333333',
        'password' => 'secret123',
    ]);

    $this->postJson('/api/v1/auth/login', [
        'phone' => '0933333333',
        'password' => 'secret123',
    ])->assertStatus(422);
});
