<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\AiOptions;
use App\Services\Ai\AiProvider;
use App\Services\Ai\AiResponse;
use App\Services\Ai\AiTestResult;
use App\Services\Ai\SuggestionPlanner;

/**
 * مزوّد محلي للتطوير والتجربة — يعمل بدون مفتاح API.
 */
class DemoAiProvider implements AiProvider
{
    public function __construct(private readonly ?SuggestionPlanner $planner = null) {}

    public function chat(array $messages, AiOptions $options): AiResponse
    {
        $started = microtime(true);
        $userText = '';
        foreach (array_reverse($messages) as $message) {
            if (($message['role'] ?? '') === 'user') {
                $userText = (string) ($message['content'] ?? '');
                break;
            }
        }

        $planner = $this->planner ?? app(SuggestionPlanner::class);
        $planned = $planner->plan($userText);

        $payload = [
            'message' => $planned['message'],
            'suggestions' => $planned['suggestions'],
        ];

        return new AiResponse(
            content: json_encode($payload, JSON_UNESCAPED_UNICODE),
            provider: 'demo',
            model: 'demo-local',
            latencyMs: (int) ((microtime(true) - $started) * 1000),
        );
    }

    public function test(): AiTestResult
    {
        return new AiTestResult(true, 'المزوّد التجريبي جاهز', 'demo-local', 5);
    }
}
