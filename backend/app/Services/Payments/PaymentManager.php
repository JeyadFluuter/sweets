<?php

namespace App\Services\Payments;

use App\Services\Payments\Gateways\FakeGateway;
use App\Services\Payments\Gateways\ManualGateway;
use InvalidArgumentException;

class PaymentManager
{
    public function driver(?string $name = null): PaymentGateway
    {
        $name ??= config('payments.driver', env('PAYMENT_DRIVER', 'manual'));

        return match ($name) {
            'manual' => new ManualGateway,
            'fake' => new FakeGateway,
            default => throw new InvalidArgumentException("بوابة الدفع غير معروفة: {$name}"),
        };
    }
}
