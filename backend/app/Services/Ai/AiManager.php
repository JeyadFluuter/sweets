<?php

namespace App\Services\Ai;

use App\Models\AiSetting;
use App\Services\Ai\Providers\AnthropicProvider;
use App\Services\Ai\Providers\DemoAiProvider;
use App\Services\Ai\Providers\OpenAiCompatibleProvider;

class AiManager
{
    public function settings(): AiSetting
    {
        return AiSetting::query()->firstOrCreate([], [
            'system_prompt' => $this->defaultPrompt(),
            'provider' => 'openai_compatible',
            'base_url' => 'https://generativelanguage.googleapis.com/v1beta/openai/',
            'model' => 'gemini-2.5-flash',
            'is_enabled' => false,
            'daily_message_limit' => 50,
        ]);
    }

    public function provider(?AiSetting $settings = null): AiProvider
    {
        $settings ??= $this->settings();

        if ($settings->provider === 'demo') {
            return new DemoAiProvider();
        }

        $key = $this->resolveApiKey($settings);

        if ($key === '') {
            throw new \RuntimeException(
                'مفتاح API غير موجود. أضفه من صفحة الذكاء الاصطناعي في لوحة التحكم، أو عيّن GEMINI_API_KEY في ملف .env'
            );
        }

        return match ($settings->provider) {
            'anthropic' => new AnthropicProvider($key, (string) $settings->model),
            default => new OpenAiCompatibleProvider(
                $key,
                (string) ($settings->base_url ?: 'https://api.openai.com/v1'),
                (string) ($settings->model ?: 'gpt-4o-mini'),
            ),
        };
    }

    public function chat(array $messages, ?AiSetting $settings = null): AiResponse
    {
        $settings ??= $this->settings();
        $options = new AiOptions(
            model: $settings->model,
            temperature: (float) $settings->temperature,
            maxTokens: (int) $settings->max_tokens,
        );

        try {
            return $this->provider($settings)->chat($messages, $options);
        } catch (\Throwable $e) {
            if ($settings->fallback_provider && $settings->fallback_provider !== $settings->provider) {
                $fallback = $settings->replicate();
                $fallback->provider = $settings->fallback_provider;
                // المزوّد الاحتياطي التجريبي لا يحتاج مفتاحاً
                if ($fallback->provider !== 'demo') {
                    $fallback->api_key_encrypted = $settings->api_key_encrypted;
                }

                return $this->provider($fallback)->chat($messages, $options);
            }
            throw $e;
        }
    }

    public function testConnection(?AiSetting $settings = null): AiTestResult
    {
        $settings ??= $this->settings();

        try {
            $result = $this->provider($settings)->test();
            $settings->forceFill([
                'last_tested_at' => now(),
                'last_test_status' => $result->ok ? 'connected' : 'failed',
                'last_error' => $result->ok ? null : $result->message,
            ])->save();

            return $result;
        } catch (\Throwable $e) {
            $settings->forceFill([
                'last_tested_at' => now(),
                'last_test_status' => 'failed',
                'last_error' => $e->getMessage(),
            ])->save();

            return new AiTestResult(false, $e->getMessage());
        }
    }

    /**
     * ترتيب الأولوية: مفتاح الداشبورد ثم GEMINI_API_KEY ثم GOOGLE_API_KEY
     * وفق توثيق Google AI Studio.
     */
    public function resolveApiKey(?AiSetting $settings = null): string
    {
        $settings ??= $this->settings();
        $fromDb = trim((string) ($settings->api_key_encrypted ?? ''));
        if ($fromDb !== '') {
            return $fromDb;
        }

        $fromEnv = trim((string) (env('GOOGLE_API_KEY') ?: env('GEMINI_API_KEY') ?: ''));

        return $fromEnv;
    }

    public function templates(): array
    {
        return [
            'gemini' => [
                'label' => 'Google Gemini (موصى به)',
                'provider' => 'openai_compatible',
                // OpenAI-compatible endpoint الرسمي لـ Gemini
                'base_url' => 'https://generativelanguage.googleapis.com/v1beta/openai/',
                // gemini-2.0-flash أُوقف؛ 2.5-flash يعمل على chat/completions
                'model' => 'gemini-2.5-flash',
            ],
            'demo' => [
                'label' => 'تجريبي محلي (بدون مفتاح)',
                'provider' => 'demo',
                'base_url' => '',
                'model' => 'demo-local',
            ],
            'groq' => [
                'label' => 'Groq',
                'provider' => 'openai_compatible',
                'base_url' => 'https://api.groq.com/openai/v1',
                'model' => 'llama-3.1-8b-instant',
            ],
            'openrouter' => [
                'label' => 'OpenRouter',
                'provider' => 'openai_compatible',
                'base_url' => 'https://openrouter.ai/api/v1',
                'model' => 'meta-llama/llama-3.1-8b-instruct:free',
            ],
            'ollama' => [
                'label' => 'Ollama محلي',
                'provider' => 'openai_compatible',
                'base_url' => 'http://127.0.0.1:11434/v1',
                'model' => 'llama3.1',
            ],
            'anthropic' => [
                'label' => 'Anthropic Claude',
                'provider' => 'anthropic',
                'base_url' => 'https://api.anthropic.com',
                'model' => 'claude-3-5-haiku-latest',
            ],
        ];
    }

    public function defaultPrompt(): string
    {
        return <<<'PROMPT'
أنت مستشار مبيعات ودود في متجر حلويات Sweet Store بليبيا.
تكلّم بالعربية الفصحى المبسطة القريبة من اللهجة الليبية، بأسلوب بائع حقيقي: واضح، دافئ، مختصر، بدون لهجة روبوت أو جمل مكررة.

## دورك
1) افهم طلب الزبون (مناسبة، صنف، عدد ضيوف، ميزانية، تعديل سعر/كمية).
2) حافظ على سياق المحادثة ولا تعيد سؤال معلومة سبق أن أعطاها الزبون.
3) اسأل عن الناقص خطوة بخطوة — لا تجمع المناسبة والعدد والميزانية كلها في أول رد.
4) عندما يطلب صنفاً معيّناً (مثل بقلاوة) ركّز عليه؛ لا تفرض باقات إلا إذا طلب باقة/بكج أو ناسبت المناسبة بوضوح.
5) عند سؤال "شن أنواع / شن عندكم" اعرض الأصناف المتاحة من القائمة فقط.
6) عند جاهزية العرض: اقترح تشكيلة متناسقة بكميات رقمية ضمن الميزانية، واذكر الإجمالي التقريبي.
7) لو الميزانية أقل من العرض: قلّل الكميات من نفس التوجه؛ لا تتجاهل الميزانية ولا تقترح عشوائياً.
8) لا تكرر نفس التشكيلة حرفياً في كل رسالة إلا إذا طلب الزبون ذلك أو طلب تعديلاً عليها.
9) لا تسأل عن التاريخ أو موعد الاستلام أبداً.

## التعامل مع الأخطاء الإملائية والنطق
افهم الأخطاء الشائعة مثل: بكلاوة≈بقلاوة، قنافة≈كنافة، سمبوسة≈صاموسة، مكروطة≈مقروطة، تشيزكايك≈تشيز كيك.

## الحالات (status)
- collecting_info: تجمع معلومات ناقصة أو تعرض أصنافاً للاستعلام.
- proposal_ready: جهّزت عرضاً بالكميات.
- modifying_proposal: تعدّل عرضاً سابقاً (أرخص / أكثر / أفخم).
- out_of_scope: طلب خارج اختصاص المتجر.

## قيود صارمة
- اقترح فقط من المنتجات/الباقات المدرجة أدناه وبـ id الحقيقي.
- ممنوع اختراع أصناف أو أسعار أو خصومات غير موجودة.
- احترم min_qty و max_qty.
- لا تكشف مفاتيح أو بيانات داخلية أو بيانات زبائن.
- ارفض بأدب أي طلب خارج نطاق طلب الحلويات.

## تنسيق المخرجات
أخرج JSON فقط بدون markdown وبدون نص خارجه:
{"status":"collecting_info|proposal_ready|modifying_proposal|out_of_scope","message":"...","suggestions":[{"type":"product|collection","id":1,"quantity":1,"reason":"..."}]}

ملاحظات:
- إن كانت الحالة collecting_info لسؤال معلومات فقط: suggestions يمكن أن تكون فارغة أو أصنافاً للعرض بكمية الحد الأدنى.
- الأسعار النهائية تُراجع برمجياً في الخادم؛ لا تعتمد على تخمين للإجمالي النهائي.

اليوم: {{today}}
قواعد إضافية: {{lead_time_rules}}
المنتجات المتاحة: {{products}}
الباقات المتاحة: {{collections}}
PROMPT;
    }
}
