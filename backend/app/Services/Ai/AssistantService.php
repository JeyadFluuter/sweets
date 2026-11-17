<?php

namespace App\Services\Ai;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssistantService
{
    public function __construct(
        private readonly AiManager $aiManager,
        private readonly ConsultantEngine $consultantEngine,
    ) {}

    public function chat(Customer $customer, string $message, ?int $conversationId = null): array
    {
        $settings = $this->aiManager->settings();

        if (! $settings->is_enabled) {
            throw ValidationException::withMessages([
                'ai' => ['المساعد غير مفعّل حالياً من لوحة التحكم.'],
            ]);
        }

        $this->assertDailyLimit($customer, (int) $settings->daily_message_limit);

        return DB::transaction(function () use ($customer, $message, $conversationId, $settings) {
            $conversation = $conversationId
                ? AiConversation::query()->where('customer_id', $customer->id)->findOrFail($conversationId)
                : AiConversation::query()->create([
                    'customer_id' => $customer->id,
                    'title' => mb_substr($message, 0, 60),
                    'context' => [],
                ]);

            AiMessage::query()->create([
                'ai_conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $message,
            ]);

            $context = ConversationContext::fromArray($conversation->context);
            $result = $this->consultantEngine->respond($message, $context);

            // رسالة المحرك هي المصدر. الصياغة بالـ AI اختيارية للعروض فقط — ممنوعة أثناء جمع المعلومات
            // لأنها كانت تختلق إجماليات وعروض قبل اكتمال العدد/الميزانية.
            $messageText = $result['message'];
            $provider = 'consultant';
            $model = 'rules-engine';
            $latency = 0;
            $suggestions = $result['suggestions'];

            $canPolish = $settings->last_test_status === 'connected'
                && in_array($result['status'], [
                    ConversationContext::STATUS_PROPOSAL,
                    ConversationContext::STATUS_MODIFYING,
                ], true)
                && $suggestions !== [];

            if ($canPolish) {
                try {
                    $polished = $this->polishMessageWithAi($settings, $result, $message);
                    if ($this->isSafePolishedMessage($polished, $result)) {
                        $messageText = $polished;
                        $provider = (string) $settings->provider;
                        $model = (string) $settings->model;
                    }
                } catch (\Throwable) {
                    // نحتفظ برسالة المحرك عند فشل الصياغة
                }
            }

            // خارج النطاق فقط نفرّغ الاقتراحات. أثناء جمع المعلومات قد نعرض أصناف للاستعلام.
            if ($result['status'] === ConversationContext::STATUS_OUT_OF_SCOPE) {
                $suggestions = [];
                $result['estimated_total'] = 0;
            }

            /** @var ConversationContext $freshContext */
            $freshContext = $result['context'];
            $freshContext->status = $result['status'];

            $conversation->forceFill([
                'context' => $freshContext->toArray(),
            ])->save();

            AiMessage::query()->create([
                'ai_conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $messageText,
                'suggested_items' => $suggestions,
                'provider' => $provider,
                'model' => $model,
                'latency_ms' => $latency,
            ]);

            return [
                'conversation_id' => $conversation->id,
                'status' => $result['status'],
                'message' => $messageText,
                'suggestions' => $suggestions,
                'estimated_total' => $result['estimated_total'],
                'budget_adjusted' => $result['budget_adjusted'],
                'context' => [
                    'occasion' => $freshContext->occasion,
                    'people' => $freshContext->people,
                    'budget' => $freshContext->budget,
                    'focus_keywords' => $freshContext->focusKeywords,
                ],
            ];
        });
    }

    /**
     * يطلب من النموذج إعادة صياغة الرسالة فقط وفق الحالة والسياق المعتمدين من Backend.
     *
     * @param  array<string,mixed>  $result
     */
    protected function polishMessageWithAi($settings, array $result, string $userMessage): ?string
    {
        $payload = [
            'status' => $result['status'],
            'approved_message' => $result['message'],
            'estimated_total' => $result['estimated_total'],
            'suggestions_count' => count($result['suggestions']),
            'context' => $result['context']->summaryForPrompt(),
            'user_message' => $userMessage,
        ];

        $messages = [
            [
                'role' => 'system',
                'content' => $this->buildSystemPrompt($settings->system_prompt ?: $this->aiManager->defaultPrompt()),
            ],
            [
                'role' => 'user',
                'content' => 'أعد صياغة message فقط لتبدو طبيعية أكثر، بدون تغيير الحقائق أو اختراع أرقام/أصناف أو إجمالي جديد. أعد JSON فقط: {"status":"'.$result['status'].'","message":"...","suggestions":[]}\nالبيانات المعتمدة: '.json_encode($payload, JSON_UNESCAPED_UNICODE),
            ],
        ];

        $response = $this->aiManager->chat($messages, $settings);
        $parsed = $this->parseJson($response->content);
        $text = trim((string) ($parsed['message'] ?? ''));

        return $text !== '' ? $text : null;
    }

    /**
     * يرفض صياغة النموذج إذا اخترعت عرضاً/إجماليات غير معتمدة.
     *
     * @param  array<string,mixed>  $result
     */
    protected function isSafePolishedMessage(?string $polished, array $result): bool
    {
        if ($polished === null || trim($polished) === '') {
            return false;
        }

        $text = mb_strtolower($polished);
        $approvedTotal = (float) ($result['estimated_total'] ?? 0);

        // لا يُسمح بذكر إجمالي مختلف عن المعتمد
        if (preg_match_all('/(\d+(?:\.\d+)?)\s*د\.?ل/u', $text, $matches)) {
            foreach ($matches[1] as $num) {
                $value = (float) $num;
                if ($approvedTotal <= 0 && $value > 0) {
                    return false;
                }
                if ($approvedTotal > 0 && abs($value - $approvedTotal) > 1) {
                    // اسمح بذكر الميزانية من السياق
                    $budget = (float) ($result['context']->budget ?? 0);
                    if ($budget > 0 && abs($value - $budget) <= 1) {
                        continue;
                    }

                    return false;
                }
            }
        }

        return true;
    }

    protected function assertDailyLimit(Customer $customer, int $limit): void
    {
        $key = 'ai.daily.'.$customer->id.'.'.now()->toDateString();
        $count = (int) Cache::get($key, 0);
        if ($count >= $limit) {
            throw ValidationException::withMessages([
                'ai' => ['وصلت للحد اليومي لرسائل المساعد.'],
            ]);
        }
        Cache::put($key, $count + 1, now()->endOfDay());
    }

    protected function buildSystemPrompt(string $template): string
    {
        $products = Cache::remember('ai.products', 60, function () {
            return Product::query()->available()->where('ai_suggestable', true)
                ->with('tags')
                ->get([
                    'id', 'name', 'price', 'discount_price', 'discount_starts_at', 'discount_ends_at',
                    'min_order_qty', 'max_order_qty',
                ])
                ->map(fn (Product $p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price' => $p->effectivePrice(),
                    'min_qty' => (int) ($p->min_order_qty ?: 1),
                    'max_qty' => (int) ($p->max_order_qty ?: 999),
                    'tags' => $p->tags->pluck('name')->all(),
                ])->all();
        });

        $collections = Cache::remember('ai.collections', 60, function () {
            return Collection::query()->visible()->get(['id', 'name', 'price'])
                ->map(fn (Collection $c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'price' => $c->price,
                ])->all();
        });

        return str_replace(
            ['{{today}}', '{{lead_time_rules}}', '{{products}}', '{{collections}}'],
            [
                now('Africa/Tripoli')->toDateString(),
                'لا تسأل عن التاريخ.',
                json_encode($products, JSON_UNESCAPED_UNICODE),
                json_encode($collections, JSON_UNESCAPED_UNICODE),
            ],
            $template
        );
    }

    protected function parseJson(string $content): ?array
    {
        $content = $this->stripCodeFences(trim($content));

        if (preg_match('/\{.*\}/s', $content, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $message = $this->extractJsonStringField($content, 'message');
        if ($message === null) {
            return null;
        }

        return [
            'message' => $message,
            'status' => $this->extractJsonStringField($content, 'status'),
            'suggestions' => [],
        ];
    }

    protected function stripCodeFences(string $content): string
    {
        $content = preg_replace('/^```(?:json)?\s*/iu', '', $content) ?? $content;
        $content = preg_replace('/\s*```$/u', '', $content) ?? $content;

        return trim($content);
    }

    protected function extractJsonStringField(string $content, string $field): ?string
    {
        $pattern = '/"'.preg_quote($field, '/').'"\s*:\s*"((?:\\\\.|[^"\\\\])*)/u';
        if (! preg_match($pattern, $content, $m)) {
            return null;
        }

        return trim(stripcslashes(rtrim($m[1], "\" \n\r\t")));
    }
}
