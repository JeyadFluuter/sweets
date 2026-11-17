<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\AiOptions;
use App\Services\Ai\AiProvider;
use App\Services\Ai\AiResponse;
use App\Services\Ai\AiTestResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * مزوّد متوافق مع OpenAI Chat Completions.
 * يعمل مع Gemini عبر:
 * https://generativelanguage.googleapis.com/v1beta/openai/
 *
 * المصادقة: Authorization: Bearer {API_KEY}
 * @see https://ai.google.dev/gemini-api/docs/openai
 */
class OpenAiCompatibleProvider implements AiProvider
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly string $model,
    ) {}

    public function chat(array $messages, AiOptions $options): AiResponse
    {
        $started = microtime(true);
        $model = $options->model ?: $this->model;
        // مهم: لا نستخدم post('/chat/completions') مع baseUrl لأن المسار المطلق
        // يمسح /v1beta/openai/ ويسبب 404 على Gemini.
        $endpoint = rtrim($this->baseUrl, '/').'/chat/completions';

        $request = Http::timeout(60)
            ->acceptJson()
            ->withToken($this->apiKey);

        if ($this->isGeminiHost()) {
            $request = $request->withHeaders([
                'x-goog-api-key' => $this->apiKey,
            ]);
        }

        $payload = [
            'model' => $model,
            'temperature' => $options->temperature,
            'messages' => $messages,
        ];

        if ($options->maxTokens > 0) {
            $payload['max_tokens'] = $options->maxTokens;
        }

        $response = $request->post($endpoint, $payload);

        if (! $response->successful()) {
            throw new \RuntimeException($this->friendlyError($response->status(), $response->body()));
        }

        $content = (string) data_get($response->json(), 'choices.0.message.content', '');
        if (trim($content) === '') {
            throw new \RuntimeException('رد فارغ من المزوّد. تحقق من اسم النموذج والمفتاح.');
        }

        return new AiResponse(
            content: $content,
            provider: 'openai_compatible',
            model: $model,
            latencyMs: (int) ((microtime(true) - $started) * 1000),
        );
    }

    public function test(): AiTestResult
    {
        try {
            $result = $this->chat([
                ['role' => 'user', 'content' => 'قل مرحبا بجملة قصيرة جداً'],
            ], new AiOptions(model: $this->model, temperature: 0.1, maxTokens: 64));

            return new AiTestResult(true, 'الاتصال ناجح', $result->model, $result->latencyMs);
        } catch (\Throwable $e) {
            return new AiTestResult(false, $e->getMessage(), $this->model);
        }
    }

    protected function isGeminiHost(): bool
    {
        return Str::contains($this->baseUrl, 'generativelanguage.googleapis.com');
    }

    protected function friendlyError(int $status, string $body): string
    {
        $detail = $this->extractGoogleError($body);

        return match (true) {
            $status === 400 => 'طلب غير صالح'.($detail ? ': '.$detail : '. تحقق من اسم النموذج.'),
            $status === 401, $status === 403 => 'مفتاح API غير صحيح أو غير مصرّح. أنشئ مفتاحاً من Google AI Studio وقيّده لـ Gemini API.',
            $status === 404 => 'النموذج أو الرابط غير موجود. استخدم النموذج gemini-2.5-flash مع Base URL الصحيح.'.($detail ? ' — '.$detail : ''),
            $status === 429 => 'تم تجاوز الحد المجاني أو معدل الطلبات.',
            $status >= 500 => 'خطأ في خادم المزوّد، حاول لاحقاً.',
            default => 'فشل الاتصال بالمزوّد'.($detail ? ': '.$detail : ': '.$body),
        };
    }

    protected function extractGoogleError(string $body): ?string
    {
        $json = json_decode($body, true);
        if (! is_array($json)) {
            return null;
        }

        return data_get($json, 'error.message')
            ?? data_get($json, 'message')
            ?? null;
    }
}
