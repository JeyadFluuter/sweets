<?php

namespace App\Services\Ai;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

/**
 * يطابق طلب الزبون مع الأصناف رغم الأخطاء الإملائية والنطق القريب.
 */
class ArabicProductMatcher
{
    /**
     * يستخرج كلمات تركيز من نص الزبون بمطابقة مرنة مع الكتالوج والأسماء الشائعة.
     *
     * @return list<string> كلمات/أسماء أصناف للبحث لاحقاً
     */
    public function extractFocusFromMessage(string $message): array
    {
        $text = $this->normalize($message);
        $folded = $this->fold($text);
        $found = [];

        foreach ($this->aliasMap() as $canonical => $aliases) {
            foreach ($aliases as $alias) {
                $aliasNorm = $this->normalize($alias);
                $aliasFold = $this->fold($aliasNorm);
                if ($aliasNorm === '') {
                    continue;
                }
                if (str_contains($text, $aliasNorm) || str_contains($folded, $aliasFold)) {
                    $found[] = $canonical;
                    break;
                }
                // خطأ إملائي قريب داخل النص (بكلاوة ≈ بقلاوة)
                foreach ($this->tokens($text) as $token) {
                    if ($this->similar($token, $aliasNorm)) {
                        $found[] = $canonical;
                        break 2;
                    }
                }
            }
        }

        // إن لم نجد من القاموس: طابق مع أسماء الكتالوج ثم رجّع الاسم القانوني إن وُجد
        if ($found === []) {
            foreach ($this->tokens($text) as $token) {
                if (mb_strlen($token) < 3 || $this->isStopWord($token)) {
                    continue;
                }
                $match = $this->fuzzyMatchProductToken($token);
                if ($match !== null) {
                    $found[] = $this->canonicalize($match);
                }
            }
        }

        return array_values(array_unique($found));
    }

    protected function canonicalize(string $keyword): string
    {
        $fold = $this->fold($keyword);
        foreach ($this->aliasMap() as $canonical => $aliases) {
            if ($this->fold($canonical) === $fold || $this->similar($canonical, $keyword)) {
                return $canonical;
            }
            foreach ($aliases as $alias) {
                if ($this->fold($alias) === $fold || $this->similar($alias, $keyword)) {
                    return $canonical;
                }
            }
        }

        return $keyword;
    }

    /**
     * هل المنتج يطابق كلمات التركيز (مع تسامح إملائي)؟
     *
     * @param  list<string>  $keywords
     */
    public function productMatches(Product $product, array $keywords): bool
    {
        if ($keywords === []) {
            return false;
        }

        $hay = $this->normalize($product->name.' '.($product->description ?? '').' '.($product->ingredients ?? ''));
        $hayFold = $this->fold($hay);

        foreach ($keywords as $keyword) {
            $k = $this->normalize((string) $keyword);
            $kFold = $this->fold($k);
            if ($k === '') {
                continue;
            }
            if (str_contains($hay, $k) || str_contains($hayFold, $kFold)) {
                return true;
            }
            // تطابق ضبابي مع كلمات اسم المنتج
            foreach ($this->tokens($hay) as $token) {
                if ($this->similar($this->fold($token), $kFold)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * تطبيع عربي مبسّط.
     */
    public function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[\x{064B}-\x{065F}]/u', '', $text) ?? $text;
        $text = str_replace(['أ', 'إ', 'آ', 'ٱ'], 'ا', $text);
        $text = str_replace('ى', 'ي', $text);
        $text = str_replace('ؤ', 'و', $text);
        $text = str_replace('ئ', 'ي', $text);
        $text = str_replace('ة', 'ه', $text);
        // إزالة ال التعريف للمطابقة
        $text = preg_replace('/\bال/u', '', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    /**
     * طيّ صوتي تقريبي لأخطاء النطق الشائعة (ق/ك، غريبة…).
     */
    public function fold(string $text): string
    {
        $text = $this->normalize($text);

        return strtr($text, [
            'ق' => 'ك',
            'گ' => 'ك',
            'ڤ' => 'ف',
            'چ' => 'ج',
            'ژ' => 'ز',
            'ض' => 'ظ',
            'ث' => 'ت',
            'ذ' => 'ز',
        ]);
    }

    public function similar(string $a, string $b): bool
    {
        $a = $this->fold($a);
        $b = $this->fold($b);
        if ($a === '' || $b === '') {
            return false;
        }
        if ($a === $b) {
            return true;
        }

        $lenA = mb_strlen($a);
        $lenB = mb_strlen($b);
        $len = max($lenA, $lenB);
        if ($len < 3 || abs($lenA - $lenB) > 2) {
            return false;
        }

        // لا تخلط كلمات تبدأ بحروف مختلفة (سمبوسة ≠ بسبوسة)
        if (mb_substr($a, 0, 1) !== mb_substr($b, 0, 1)) {
            return false;
        }

        $aa = $this->toByteKey($a);
        $bb = $this->toByteKey($b);
        $distance = levenshtein($aa, $bb);
        $maxDistance = $len <= 5 ? 1 : 2;

        return $distance >= 0 && $distance <= $maxDistance;
    }

    protected function containsFolded(string $hayFold, string $needleFold): bool
    {
        if ($needleFold === '' || mb_strlen($needleFold) < 3) {
            return false;
        }

        if (str_contains($hayFold, $needleFold)) {
            return true;
        }

        // نافذة منزلقة بنفس طول الكلمة تقريباً
        $hayTokens = $this->tokens($hayFold);
        foreach ($hayTokens as $token) {
            if ($this->similar($token, $needleFold)) {
                return true;
            }
        }

        return false;
    }

    protected function fuzzyMatchProductToken(string $token): ?string
    {
        $tokenFold = $this->fold($token);
        $best = null;
        $bestDistance = PHP_INT_MAX;

        foreach ($this->catalogKeywords() as $keyword) {
            $keyFold = $this->fold($keyword);
            if ($this->similar($tokenFold, $keyFold)) {
                $distance = levenshtein($this->toByteKey($tokenFold), $this->toByteKey($keyFold));
                if ($distance < $bestDistance) {
                    $bestDistance = $distance;
                    $best = $keyword;
                }
            }
        }

        return $best;
    }

    /** @return list<string> */
    protected function catalogKeywords(): array
    {
        return Cache::remember('ai.catalog_keywords', 60, function () {
            $keywords = [];
            $products = Product::query()->available()->where('ai_suggestable', true)->get(['name']);
            foreach ($products as $product) {
                $name = $this->normalize($product->name);
                $keywords[] = $name;
                foreach ($this->tokens($name) as $token) {
                    if (mb_strlen($token) >= 3 && ! $this->isStopWord($token)) {
                        $keywords[] = $token;
                    }
                }
            }

            return array_values(array_unique($keywords));
        });
    }

    /** @return array<string, list<string>> */
    protected function aliasMap(): array
    {
        return [
            'بقلاوة' => [
                'بقلاوة', 'بقلاوه', 'بقلاو', 'بكلاوة', 'بكلاوه', 'بقلوة', 'بكلاوة',
                'baklava', 'baqlava', 'baklawa', 'baqlawa',
            ],
            'كنافة' => ['كنافة', 'كنافه', 'كناف', 'قنافة', 'قنافه', 'kunafa', 'kenafa'],
            'بسبوسة' => ['بسبوسة', 'بسبوسه', 'بسبوس', 'basbousa', 'basbusa'],
            'غريبة' => ['غريبة', 'غريبه', 'غريب', 'ghribia', 'ghriba'],
            'مقروطة' => ['مقروطة', 'مقروطه', 'مقروط', 'مكروطة', 'مكروطه', 'maqroud', 'makroud'],
            'تشيز كيك' => ['تشيز كيك', 'تشيزكيك', 'تشيز', 'تشيزكايك', 'cheesecake', 'cheese cake'],
            'براونيز' => ['براونيز', 'براوني', 'براونيس', 'brownies', 'brownie'],
            'صاموسة' => ['صاموسة', 'صاموسه', 'سمبوسة', 'سمبوسه', 'سمبوسك', 'samosa'],
            'عصير' => ['عصير', 'عصاير', 'juice'],
            'كب كيك' => ['كب كيك', 'كبكيك', 'كبكايك', 'cupcake', 'cup cake'],
            'فطيرة' => ['فطيرة', 'فطيره', 'فطائر', 'fatayer'],
            'شاي' => ['شاي', 'شاهي', 'tea'],
        ];
    }

    /** @return list<string> */
    protected function tokens(string $text): array
    {
        $parts = preg_split('/\s+/u', $this->normalize($text)) ?: [];

        return array_values(array_filter($parts, fn ($p) => $p !== ''));
    }

    protected function isStopWord(string $token): bool
    {
        $stop = [
            'نبي', 'أبي', 'ابي', 'أريد', 'اريد', 'عندي', 'عندكم', 'عندك', 'شن', 'شو', 'وش',
            'من', 'على', 'الي', 'إلى', 'في', 'هذا', 'هذي', 'هذه', 'لي', 'لها', 'لهم',
            'كم', 'بكم', 'وشن', 'أنواع', 'انواع', 'صنف', 'أصناف', 'حلويات', 'حلو',
            'شخص', 'اشخاص', 'أشخاص', 'ضيف', 'ضيوف', 'ميزانية', 'دينار', 'عرض', 'عروض',
            'باقة', 'باقات', 'مناسب', 'مناسبة', 'وريني', 'قلي', 'قولي',
        ];

        return in_array($this->normalize($token), $stop, true);
    }

    /**
     * تحويل نص عربي لمفتاح بايتات ثابت الطول تقريباً من أجل levenshtein.
     */
    protected function toByteKey(string $text): string
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $out = '';
        foreach ($chars as $i => $ch) {
            // رمز ثابت لكل حرف عبر crc قصير
            $out .= chr(65 + (abs(crc32($ch)) % 50));
            if ($i > 40) {
                break;
            }
        }

        return $out;
    }
}
