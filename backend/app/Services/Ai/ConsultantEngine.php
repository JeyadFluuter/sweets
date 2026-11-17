<?php

namespace App\Services\Ai;

use App\Models\Collection;
use App\Models\Product;
use Illuminate\Support\Collection as SupportCollection;

/**
 * مستشار المبيعات متعدد المراحل — يفهم النية، يسأل تدريجياً، ويقترح عبر تحقق Backend.
 */
class ConsultantEngine
{
    public function __construct(
        private readonly IntentDetector $intentDetector,
        private readonly OfferValidator $validator,
        private readonly SuggestionPlanner $planner,
        private readonly ArabicProductMatcher $matcher,
    ) {}

    /**
     * @return array{
     *   status:string,
     *   message:string,
     *   suggestions:list<array<string,mixed>>,
     *   estimated_total:float,
     *   context:ConversationContext,
     *   budget_adjusted:bool
     * }
     */
    public function respond(string $userMessage, ConversationContext $context): array
    {
        $detected = $this->intentDetector->detect($userMessage, $context);
        $context->occasion = $detected['occasion'];
        $context->people = $detected['people'];
        $context->budget = $detected['budget'];
        $context->focusKeywords = $detected['focus_keywords'];
        $context->style = $detected['style'];
        $context->lastIntent = $detected['intent'];

        if ($detected['intent'] === 'out_of_scope') {
            $context->status = ConversationContext::STATUS_OUT_OF_SCOPE;

            return $this->result(
                $context,
                ConversationContext::STATUS_OUT_OF_SCOPE,
                'أقدر أساعدك فقط في اختيار أصناف وباقات Sweet Store حسب المناسبة وعدد الضيوف والميزانية. صف لي طلب الحلويات اللي تبيه.',
                [],
                0,
                false,
            );
        }

        $catalogCheck = $this->validator->validate([]);
        if ($catalogCheck['empty_catalog']) {
            $context->status = ConversationContext::STATUS_COLLECTING;

            return $this->result(
                $context,
                ConversationContext::STATUS_COLLECTING,
                'ما في أصناف متاحة للاقتراح حالياً. بعد تحديث بيانات المتجر نقدر نرتب لك عرضاً — أو تصفّح الأصناف يدوياً.',
                [],
                0,
                false,
            );
        }

        // تعديل عرض سابق
        if (in_array($detected['intent'], ['reduce_price', 'increase_qty', 'make_fancy'], true)
            && $context->lastSuggestions !== []) {
            return $this->modifyExisting($context, $detected['intent']);
        }

        // استعلام عن أصناف معيّنة (أنواع/شن عندكم) — بدون إجبار باقة أو مناسبة
        if ($detected['intent'] === 'browse_items') {
            return $this->showAvailableItems($context, askNext: false);
        }

        // طلب صنف محدد وما زلنا نجمع معلومات: اعرض الأصناف المتاحة + اسأل الناقص فقط
        if (in_array($detected['intent'], ['request_product', 'ask_offers'], true)
            && $context->hasFocus()
            && ($context->people === null || $context->budget === null)) {
            return $this->showAvailableItems($context, askNext: true);
        }

        // جمع المعلومات على مراحل — سؤال واحد في كل مرة (للطلبات العامة)
        if ($question = $this->nextQuestion($context)) {
            $context->status = ConversationContext::STATUS_COLLECTING;

            return $this->result(
                $context,
                ConversationContext::STATUS_COLLECTING,
                $question,
                [],
                0,
                false,
            );
        }

        // جاهزون لعرض مقترح بالكميات
        return $this->buildProposal($context, allowRepeat: false);
    }

    /**
     * يعرض الأصناف المتاحة المطابقة للطلب — بدون باقات افتراضية.
     *
     * @return array<string,mixed>
     */
    protected function showAvailableItems(ConversationContext $context, bool $askNext): array
    {
        $products = Product::query()->available()->where('ai_suggestable', true)->get();
        $matched = $this->matchFocusProducts($products, $context->focusKeywords);

        if ($matched->isEmpty() && $context->focusKeywords !== []) {
            $focus = implode(' و', $context->focusKeywords);
            $context->status = ConversationContext::STATUS_COLLECTING;

            return $this->result(
                $context,
                ConversationContext::STATUS_COLLECTING,
                "حالياً ما لقيت أصناف مطابقة لـ{$focus} ضمن المتاح. تحب تصفّح حلويات ثانية أو تغيّر الاسم؟",
                [],
                0,
                false,
            );
        }

        if ($matched->isEmpty()) {
            $matched = $products->take(4)->values();
        }

        $raw = [];
        foreach ($matched->take(4) as $product) {
            $raw[] = [
                'type' => 'product',
                'id' => $product->id,
                'quantity' => max(1, (int) ($product->min_order_qty ?: 1)),
                'reason' => 'متاح الآن · '.(float) $product->effectivePrice().' د.ل',
            ];
        }

        $validated = $this->validator->validate($raw, null, [], true);
        $names = collect($validated['suggestions'])->pluck('name')->all();
        $focusLabel = $context->focusKeywords !== []
            ? implode(' و', $context->focusKeywords)
            : 'الأصناف';

        $listText = $names !== []
            ? implode('، ', $names)
            : 'بعض الأصناف المتاحة';

        $message = "عندنا من {$focusLabel}: {$listText}.";
        if ($askNext) {
            if ($context->people === null) {
                $message .= ' كم شخص تقريباً عشان نضبط الكمية؟';
            } elseif ($context->budget === null) {
                $message .= ' بكم الميزانية تقريباً بالدينار؟';
            } else {
                $message .= ' تحب نثبّت كميات حسب العدد والميزانية؟';
            }
        } else {
            $message .= ' تقدر تضيف من البطاقات، أو قولّي العدد والميزانية نرتب لك كمية مناسبة.';
        }

        $context->status = ConversationContext::STATUS_COLLECTING;
        // لا نحفظها كآخر عرض مكتمل حتى لا تُمنع لاحقاً كـ "تكرار"
        // لكن نعرضها للزبون الآن

        return $this->result(
            $context,
            ConversationContext::STATUS_COLLECTING,
            $message,
            $validated['suggestions'],
            $validated['estimated_total'],
            false,
        );
    }

    /**
     * @param  SupportCollection<int, Product>  $products
     * @param  list<string>  $keywords
     * @return SupportCollection<int, Product>
     */
    protected function matchFocusProducts(SupportCollection $products, array $keywords): SupportCollection
    {
        if ($keywords === []) {
            return collect();
        }

        return $products
            ->filter(fn (Product $product) => $this->matcher->productMatches($product, $keywords))
            ->values();
    }

    protected function nextQuestion(ConversationContext $context): ?string
    {
        $hasFocus = $context->hasFocus();

        // بداية عامة بدون صنف محدد: اسأل المناسبة ثم العدد
        if (! $hasFocus && $context->occasion === null) {
            return 'نقدر نرتب لك تشكيلة حلويات مناسبة. شن نوع المناسبة وكم شخص تقريباً؟';
        }

        if ($context->people === null) {
            if ($hasFocus) {
                $focus = implode(' و', $context->focusKeywords);

                return "نقدر نجهزلك تشكيلة {$focus} مناسبة. كم شخص تقريباً؟";
            }

            $occasion = $context->occasion ?? 'المناسبة';

            return "تمام لمناسبة {$occasion}. كم شخص تقريباً؟";
        }

        if ($context->budget === null) {
            $who = $context->people.' شخص';
            if ($hasFocus) {
                $focus = implode(' و', $context->focusKeywords);

                return "ممتاز — {$focus} لـ {$who}. بكم الميزانية اللي حاطها تقريباً بالدينار؟";
            }
            $occasion = $context->occasion ?? 'المناسبة';

            return "نقترح تشكيلة تناسب {$occasion} لـ {$who}. بكم الميزانية اللي حاطها تقريباً؟";
        }

        return null;
    }

    /**
     * @return array<string,mixed>
     */
    protected function buildProposal(ConversationContext $context, bool $allowRepeat): array
    {
        $raw = $this->composeRawSuggestions($context);
        $exclude = $allowRepeat ? [] : $context->lastSuggestionKeys;

        $validated = $this->validator->validate(
            $raw,
            $context->budget,
            $exclude,
            $allowRepeat,
        );

        // لو منع التكرار أفرغ العرض، ابنِ من جديد مع السماح بالتكرار لكن بكميات مختلفة إن لزم
        if ($validated['suggestions'] === [] && $raw !== []) {
            $validated = $this->validator->validate($raw, $context->budget, [], true);
        }

        if ($validated['suggestions'] === []) {
            $context->status = ConversationContext::STATUS_COLLECTING;

            return $this->result(
                $context,
                ConversationContext::STATUS_COLLECTING,
                'ما قدرت أركب عرضاً ضمن الميزانية الحالية من الأصناف المتاحة. تحب ترفع الميزانية شوي أو نركز على صنف واحد؟',
                [],
                0,
                false,
            );
        }

        $context->status = ConversationContext::STATUS_PROPOSAL;
        $context->rememberSuggestions($validated['suggestions']);

        $message = $this->proposalMessage($context, $validated['estimated_total'], $validated['budget_adjusted']);

        return $this->result(
            $context,
            ConversationContext::STATUS_PROPOSAL,
            $message,
            $validated['suggestions'],
            $validated['estimated_total'],
            $validated['budget_adjusted'],
        );
    }

    /**
     * @return array<string,mixed>
     */
    protected function modifyExisting(ConversationContext $context, string $intent): array
    {
        $context->status = ConversationContext::STATUS_MODIFYING;
        $raw = $context->lastSuggestions;

        if ($intent === 'reduce_price') {
            if ($context->budget !== null) {
                $context->budget = max(40, round($context->budget * 0.75, 2));
            }
            $raw = $this->scaleQuantities($raw, 0.7);
            $context->style = 'cheap';
        }

        if ($intent === 'increase_qty') {
            $raw = $this->scaleQuantities($raw, 1.35);
            if ($context->budget !== null) {
                $context->budget = round($context->budget * 1.15, 2);
            }
        }

        if ($intent === 'make_fancy') {
            $context->style = 'fancy';
            // أعد البناء مع نفس السياق لكن أسلوباً أفخم، مع منع نسخ نفس المفاتيح إن أمكن
            return $this->buildProposal($context, allowRepeat: false);
        }

        $validated = $this->validator->validate($raw, $context->budget, [], true);
        if ($validated['suggestions'] === []) {
            return $this->buildProposal($context, allowRepeat: true);
        }

        $context->rememberSuggestions($validated['suggestions']);
        $message = match ($intent) {
            'reduce_price' => $validated['budget_adjusted']
                ? 'عدّلت العرض وخفّضت الكميات ليبقى أقرب لميزانيتك. الإجمالي التقريبي '.$this->money($validated['estimated_total']).' د.ل. مناسب لك؟'
                : 'رتّبت نسخة أوفر من نفس التوجه. الإجمالي التقريبي '.$this->money($validated['estimated_total']).' د.ل.',
            'increase_qty' => 'زوّدت الكميات حسب طلبك. الإجمالي التقريبي '.$this->money($validated['estimated_total']).' د.ل. تحب نثبتها؟',
            default => 'عدّلت العرض. الإجمالي التقريبي '.$this->money($validated['estimated_total']).' د.ل.',
        };

        return $this->result(
            $context,
            ConversationContext::STATUS_MODIFYING,
            $message,
            $validated['suggestions'],
            $validated['estimated_total'],
            $validated['budget_adjusted'],
        );
    }

    /**
     * @return list<array<string,mixed>>
     */
    protected function composeRawSuggestions(ConversationContext $context): array
    {
        $people = max(1, (int) ($context->people ?? 10));
        $budget = (float) ($context->budget ?? max(150, $people * 12));
        $products = Product::query()->available()->where('ai_suggestable', true)->get();
        $collections = Collection::query()->visible()->get();

        if ($context->hasFocus()) {
            return $this->suggestionsForFocus($products, $context, $people, $budget);
        }

        $raw = [];
        if (in_array($context->occasion, ['خطوبة', 'فرح', 'عيد ميلاد'], true) && $collections->isNotEmpty()) {
            $collection = $this->pickCollection($collections, $context);
            if ($collection) {
                $raw[] = [
                    'type' => 'collection',
                    'id' => $collection->id,
                    'quantity' => max(1, (int) ceil($people / 20)),
                    'reason' => 'باقة مناسبة للمناسبة',
                ];
            }
        }

        $picked = $this->pickProducts($products, $context, $people, $budget, $raw !== [] ? 2 : 3);
        foreach ($picked as $row) {
            $raw[] = $row;
        }

        return $raw;
    }

    /**
     * @param  SupportCollection<int, Product>  $products
     * @return list<array<string,mixed>>
     */
    protected function suggestionsForFocus(SupportCollection $products, ConversationContext $context, int $people, float $budget): array
    {
        $matched = $products
            ->filter(fn (Product $product) => $this->matcher->productMatches($product, $context->focusKeywords))
            ->values();

        if ($matched->isEmpty()) {
            return $this->pickProducts($products, $context, $people, $budget, 3);
        }

        // أسلوب فخم: كميات أوفر قليلاً / رخيص: أقل
        $factor = match ($context->style) {
            'fancy' => 1.2,
            'cheap' => 0.75,
            default => 1.0,
        };

        $raw = [];
        $spentHint = 0.0;
        foreach ($matched->take(3) as $product) {
            $left = max(1, $budget - $spentHint);
            $qty = $this->qtyFor($product, (int) ceil($people * $factor), $left);
            $raw[] = [
                'type' => 'product',
                'id' => $product->id,
                'quantity' => $qty,
                'reason' => 'حسب طلبك: '.$product->name,
            ];
            $spentHint += (float) $product->effectivePrice() * $qty;
        }

        return $raw;
    }

    /**
     * @param  SupportCollection<int, Product>  $products
     * @return list<array<string,mixed>>
     */
    protected function pickProducts(SupportCollection $products, ConversationContext $context, int $people, float $budget, int $limit): array
    {
        $sorted = $products->sortBy(function (Product $p) use ($context, $budget, $limit) {
            $price = (float) $p->effectivePrice();
            if ($context->style === 'fancy') {
                return -$price;
            }
            if ($context->style === 'cheap') {
                return $price;
            }

            return abs($price - ($budget / max(3, $limit)));
        })->values();

        $raw = [];
        $spent = 0.0;
        foreach ($sorted->take($limit) as $product) {
            $left = max(1, $budget - $spent);
            $qty = $this->qtyFor($product, $people, $left);
            $raw[] = [
                'type' => 'product',
                'id' => $product->id,
                'quantity' => $qty,
                'reason' => $context->occasion
                    ? 'لمناسبة '.$context->occasion
                    : 'تشكيلة متناسقة',
            ];
            $spent += (float) $product->effectivePrice() * $qty;
        }

        return $raw;
    }

    /**
     * @param  SupportCollection<int, Collection>  $collections
     */
    protected function pickCollection(SupportCollection $collections, ConversationContext $context): ?Collection
    {
        if ($context->occasion) {
            $match = $collections->first(function (Collection $c) use ($context) {
                return str_contains(mb_strtolower($c->name), mb_strtolower($context->occasion));
            });
            if ($match) {
                return $match;
            }
        }

        return $collections->sortBy(fn (Collection $c) => (float) $c->price)->first();
    }

    protected function qtyFor(Product $product, int $people, float $budgetSlice): int
    {
        $price = max(0.01, (float) $product->effectivePrice());
        $name = mb_strtolower($product->name);
        $base = match (true) {
            str_contains($name, 'كيك') || str_contains($name, 'تشيز') => max(1, (int) ceil($people / 10)),
            str_contains($name, 'عصير') || str_contains($name, 'شاي') => max(1, $people),
            default => max(1, (int) ceil($people * 2)),
        };
        $maxByBudget = max(1, (int) floor($budgetSlice / $price));
        $qty = min($base, $maxByBudget);
        $min = max(1, (int) ($product->min_order_qty ?: 1));
        $max = (int) ($product->max_order_qty ?: 999);

        return max($min, min($max, $qty));
    }

    /**
     * @param  list<array<string,mixed>>  $items
     * @return list<array<string,mixed>>
     */
    protected function scaleQuantities(array $items, float $factor): array
    {
        foreach ($items as $i => $item) {
            $qty = max(1, (int) round(((int) ($item['quantity'] ?? 1)) * $factor));
            $items[$i]['quantity'] = $qty;
        }

        return $items;
    }

    protected function proposalMessage(ConversationContext $context, float $total, bool $budgetAdjusted): string
    {
        $parts = [];
        if ($context->hasFocus()) {
            $parts[] = 'جهّزت لك تشكيلة '.implode(' و', $context->focusKeywords);
        } elseif ($context->occasion) {
            $parts[] = 'جهّزت لك تشكيلة لمناسبة '.$context->occasion;
        } else {
            $parts[] = 'جهّزت لك تشكيلة مناسبة';
        }

        if ($context->people) {
            $parts[] = 'لـ '.$context->people.' شخص';
        }

        $line = implode(' ', $parts).'. الإجمالي التقريبي '.$this->money($total).' د.ل';
        if ($context->budget !== null) {
            $line .= $budgetAdjusted
                ? ' (عدّلت الكميات لتناسب ميزانية '.$this->money($context->budget).' د.ل).'
                : ' ضمن ميزانية '.$this->money($context->budget).' د.ل.';
        } else {
            $line .= '.';
        }

        return $line.' تحب تعدّل الكمية أو الميزانية؟';
    }

    protected function money(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    /**
     * @param  list<array<string,mixed>>  $suggestions
     * @return array<string,mixed>
     */
    protected function result(
        ConversationContext $context,
        string $status,
        string $message,
        array $suggestions,
        float $estimatedTotal,
        bool $budgetAdjusted,
    ): array {
        $context->status = $status;

        return [
            'status' => $status,
            'message' => $message,
            'suggestions' => $suggestions,
            'estimated_total' => $estimatedTotal,
            'context' => $context,
            'budget_adjusted' => $budgetAdjusted,
        ];
    }
}
