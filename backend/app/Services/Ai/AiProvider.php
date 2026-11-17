<?php

namespace App\Services\Ai;

interface AiProvider
{
    public function chat(array $messages, AiOptions $options): AiResponse;

    public function test(): AiTestResult;
}
