<?php

namespace App\Services\Finance;

class ModelSource
{
    public function __construct(
        public readonly string $type,
        public readonly int $id,
    ) {}
}
