<?php

namespace App\Services\Ai;

class AiResponse
{
    public function __construct(
        public readonly string $content,
        public readonly string $provider,
        public readonly string $model,
        public readonly int $latencyMs = 0,
    ) {}
}
