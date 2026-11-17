<?php

namespace App\Services\Payments;

class PaymentSession
{
    public function __construct(
        public readonly string $reference,
        public readonly string $status,
        public readonly ?string $redirectUrl = null,
        public readonly array $meta = [],
    ) {}
}
