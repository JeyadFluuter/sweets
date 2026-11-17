<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\AiOptions;
use App\Services\Ai\AiProvider;
use App\Services\Ai\AiResponse;
use App\Services\Ai\AiTestResult;
use Illuminate\Support\Facades\Http;

class AnthropicProvider implements AiProvider
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
        private readonly string $baseUrl = 'https://api.anthropic.com',
    ) {}

    public function chat(array $messages, AiOptions $options): AiResponse
    {
        $started = microtime(true);
        $system = collect($messages)->firstWhere('role', 'system')['content'] ?? null;
        $filtered = collect($messages)->reject(fn ($m) => $m['role'] === 'system')->values()->all();

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
        ])->timeout(60)
            ->baseUrl(rtrim($this->baseUrl, '/'))
            ->post('/v1/messages', [
                'model' => $options->model ?: $this->model,
                'max_tokens' => $options->maxTokens,
                'temperature' => $options->temperature,
                'system' => $system,
                'messages' => $filtered,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('فشل Anthropic: '.$response->body());
        }

        $content = collect(data_get($response->json(), 'content', []))
            ->where('type', 'text')
            ->pluck('text')
            ->implode("\n");

        return new AiResponse(
            content: $content,
            provider: 'anthropic',
            model: $options->model ?: $this->model,
            latencyMs: (int) ((microtime(true) - $started) * 1000),
        );
    }

    public function test(): AiTestResult
    {
        try {
            $result = $this->chat([
                ['role' => 'user', 'content' => 'قل مرحبا'],
            ], new AiOptions(model: $this->model, maxTokens: 32));

            return new AiTestResult(true, 'الاتصال ناجح', $result->model, $result->latencyMs);
        } catch (\Throwable $e) {
            return new AiTestResult(false, $e->getMessage(), $this->model);
        }
    }
}
