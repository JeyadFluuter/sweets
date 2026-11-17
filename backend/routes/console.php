<?php

use App\Services\Catalog\PopularProductsService;
use App\Services\Notifications\FirebaseConnectionTester;
use App\Services\Orders\OrderService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('catalog:refresh-popular', function (PopularProductsService $service) {
    $service->refresh();
    $this->info('تم تحديث قائمة الأكثر طلباً.');
})->purpose('Refresh popular products cache');

Artisan::command('orders:expire-unpaid', function (OrderService $orders) {
    $count = $orders->expireUnpaid();
    $this->info("تم إنهاء {$count} طلبات منتهية المهلة.");
})->purpose('Expire unpaid awaiting_payment orders');

Artisan::command('firebase:test', function (FirebaseConnectionTester $tester) {
    $result = $tester->test($this->ask('Device token (اختياري)', null));
    foreach ($result['steps'] as $step) {
        $this->line(($step['ok'] ? '✅' : '❌').' '.$step['name'].': '.$step['message']);
    }
    $this->info('الحالة: '.$result['status']);
})->purpose('Test Firebase connection');

Schedule::command('catalog:refresh-popular')->hourly();
Schedule::command('orders:expire-unpaid')->everyFifteenMinutes();
