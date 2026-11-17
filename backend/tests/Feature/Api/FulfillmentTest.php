<?php

use App\Models\Customer;
use Database\Seeders\FulfillmentSeeder;
use Database\Seeders\SettingSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(SettingSeeder::class);
    $this->seed(FulfillmentSeeder::class);
    Sanctum::actingAs(Customer::factory()->create());
});

it('returns available pickup dates and slots', function () {
    $dates = $this->getJson('/api/v1/fulfillment/available-dates?delivery_type=pickup')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->json('data.dates');

    expect($dates)->toBeArray()->not->toBeEmpty();

    $this->getJson('/api/v1/fulfillment/slots?delivery_type=pickup&date='.$dates[0])
        ->assertOk()
        ->assertJsonStructure(['data' => ['slots']]);
});
