<?php

namespace App\Services\Ai;

class AiOptions
{
    public function __construct(
        public readonly ?string $model = null,
        public readonly float $temperature = 0.4,
        public readonly int $maxTokens = 1024,
    ) {}
}
