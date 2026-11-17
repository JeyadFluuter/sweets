<?php

namespace App\Services\Payments;

class PaymentResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $reference,
        public readonly string $status,
        public readonly ?string $idempotencyKey = null,
        public readonly array $meta = [],
    ) {}
}
