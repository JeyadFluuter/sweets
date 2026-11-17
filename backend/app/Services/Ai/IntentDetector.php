<?php

namespace App\Services\Ai;

class IntentDetector
{
    public function __construct(private readonly ArabicProductMatcher $matcher) {}

    /**
     * @return array{
     *   intent:string,
     *   occasion:?string,
     *   people:?int,
     *   budget:?float,
     *   focus_keywords:list<string>,
     *   style:?string
     * }
     */
    public function detect(string $message, ConversationContext $context): array
    {
        $text = $this->matcher->normalize($message);
        $planner = app(SuggestionPlanner::class);

        $occasion = $planner->detectOccasion($text) ?? $context->occasion;
        $people = $planner->parsePeople($text) ?? $context->people;
        $budget = $planner->parseBudget($text) ?? $context->budget;
        $focus = $this->extractFocusKeywords($message);
        if ($focus === []) {
            $focus = $context->focusKeywords;
        }

        $style = $context->style;
        if ($this->wantsFancy($text)) {
            $style = 'fancy';
        } elseif ($this->wantsCheaper($text)) {
            $style = 'cheap';
        }

        $intent = $this->resolveIntent($text, $context, $focus);

        return [
            'intent' => $intent,
            'occasion' => $occasion,
            'people' => $people,
            'budget' => $budget,
            'focus_keywords' => $focus,
            'style' => $style,
        ];
    }

    protected function resolveIntent(
        string $text,
        ConversationContext $context,
        array $focus,
    ): string {
        if ($this->isOutOfScope($text)) {
            return 'out_of_scope';
        }

        // سؤال عن أنواع/أصناف متاحة لصنف معيّن — ليس طلب باقة
        if ($this->isBrowseCatalog($text)) {
            return 'browse_items';
        }

        if ($this->wantsCheaper($text) || str_contains($text, 'نقص') || str_contains($text, 'أرخص') || str_contains($text, 'اوفر') || str_contains($text, 'أوفر')) {
            return $context->lastSuggestions !== [] ? 'reduce_price' : 'set_budget';
        }

        if (str_contains($text, 'زوّد') || str_contains($text, 'زود') || str_contains($text, 'أكثر') || str_contains($text, 'اكثر') || str_contains($text, 'زيد')) {
            return $context->lastSuggestions !== [] ? 'increase_qty' : 'general';
        }

        if ($this->wantsFancy($text)) {
            return $context->lastSuggestions !== [] ? 'make_fancy' : 'set_style';
        }

        if (str_contains($text, 'باقة') || str_contains($text, 'بكج')) {
            return 'ask_collection';
        }

        // أي ذكر لصنف محدد = تركيز على الصنف (حتى لو مكرر من السياق)
        if ($this->extractFocusKeywords($text) !== []) {
            return 'request_product';
        }

        if (app(SuggestionPlanner::class)->parsePeople($text) !== null) {
            return 'set_people';
        }

        if (app(SuggestionPlanner::class)->parseBudget($text) !== null) {
            return 'set_budget';
        }

        if (app(SuggestionPlanner::class)->detectOccasion($text) !== null) {
            return 'set_occasion';
        }

        if (str_contains($text, 'عرض') || str_contains($text, 'عروض') || str_contains($text, 'اقترح') || str_contains($text, 'اقتراح')) {
            return 'ask_offers';
        }

        if (str_contains($text, 'حلويات') || str_contains($text, 'مناسبة') || str_contains($text, 'نبي') || str_contains($text, 'أبي') || str_contains($text, 'أريد')) {
            return 'start_order';
        }

        return 'general';
    }

    public function isBrowseCatalog(string $text): bool
    {
        $text = $this->matcher->normalize($text);

        return str_contains($text, 'أنواع')
            || str_contains($text, 'انواع')
            || str_contains($text, 'شن عندكم')
            || str_contains($text, 'شو عندكم')
            || str_contains($text, 'وش عندكم')
            || str_contains($text, 'شن أنواع')
            || str_contains($text, 'وريني')
            || str_contains($text, 'شن موجود')
            || str_contains($text, 'المتاح')
            || (str_contains($text, 'عندكم') && (
                str_contains($text, 'من')
                || $this->extractFocusKeywords($text) !== []
            ));
    }

    /** @return list<string> */
    public function extractFocusKeywords(string $text): array
    {
        return $this->matcher->extractFocusFromMessage($text);
    }

    protected function wantsFancy(string $text): bool
    {
        return str_contains($text, 'فخمة')
            || str_contains($text, 'فاخر')
            || str_contains($text, 'أحلى')
            || str_contains($text, 'احلى')
            || str_contains($text, 'مميزة');
    }

    protected function wantsCheaper(string $text): bool
    {
        return str_contains($text, 'غالي')
            || str_contains($text, 'رخيص')
            || str_contains($text, 'ميزانية أقل')
            || str_contains($text, 'الميزانية أقل')
            || str_contains($text, 'نقصلي السعر')
            || str_contains($text, 'انقص');
    }

    protected function isOutOfScope(string $text): bool
    {
        $blocked = [
            'اختراق', 'هكر', 'hack', 'password', 'كلمة المرور', 'api key',
            'دواء', 'وصفة طبية', 'انتخاب', 'سلاح', 'قنبلة', 'porn', 'جنس',
            'بطاقة ائتمان', 'visa', 'بيانات الزبائن',
        ];

        foreach ($blocked as $word) {
            if (str_contains($text, mb_strtolower($word))) {
                return true;
            }
        }

        return false;
    }
}
