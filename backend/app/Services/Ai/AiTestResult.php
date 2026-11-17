<?php

namespace App\Services\Ai;

class AiTestResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly string $message,
        public readonly ?string $model = null,
        public readonly int $latencyMs = 0,
    ) {}
}
