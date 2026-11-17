<?php

namespace App\Services\Ai;

use App\Models\Collection;
use App\Models\Product;
use Illuminate\Support\Collection as SupportCollection;

/**
 * يبني عرضاً متناسقاً ومرتبطاً بطلب الزبون الفعلي.
 */
class SuggestionPlanner
{
    /**
     * @return array{message:string,suggestions:list<array<string,mixed>>,people:?int,budget:?float,estimated_total:float}
     */
    public function plan(string $userText): array
    {
        $text = mb_strtolower(trim($userText));
        $peopleGiven = $this->parsePeople($text);
        $budgetGiven = $this->parseBudget($text);
        $occasion = $this->detectOccasion($text);

        $peopleAssumed = $peopleGiven === null;
        $budgetAssumed = $budgetGiven === null;
        $people = $peopleGiven ?? 10;

        $products = Product::query()
            ->available()
            ->where('ai_suggestable', true)
            ->get();

        $collections = Collection::query()->visible()->get();
        $matchedProducts = $this->matchProductsByRequest($products, $text);
        $hasSpecificProductRequest = $matchedProducts->isNotEmpty();
        $preferCollection = $this->wantsCollection($text, $occasion, $hasSpecificProductRequest);

        // بدون ميزانية مذكورة: لا نضيّق الكمية بافتراض صغير — خصوصاً لو طلب صنفاً محدداً
        $budget = $budgetGiven ?? (
            $hasSpecificProductRequest
                ? (float) max(250, $people * 20)
                : (float) max(150, $people * 12)
        );

        $picked = [];
        $estimated = 0.0;

        if ($hasSpecificProductRequest) {
            foreach ($matchedProducts->take(3) as $product) {
                $left = max(0, $budget - $estimated);
                $qty = $this->quantityForProduct($product, $people, $left > 0 ? $left : $budget);
                $line = (float) $product->effectivePrice() * $qty;
                $picked[] = [
                    'type' => 'product',
                    'id' => $product->id,
                    'quantity' => $qty,
                    'reason' => $this->productReason($product, $people, $qty, $occasion, true),
                ];
                $estimated += $line;
            }
        } else {
            if ($preferCollection && $collections->isNotEmpty()) {
                $collection = $this->pickRelevantCollection($collections, $text, $occasion, $budget);
                if ($collection) {
                    $qty = max(1, (int) ceil($people / 15));
                    $line = (float) $collection->price * $qty;
                    if ($line <= $budget * 1.15 || $estimated === 0.0) {
                        $picked[] = [
                            'type' => 'collection',
                            'id' => $collection->id,
                            'quantity' => $qty,
                            'reason' => $this->collectionReason($occasion, $people, $qty),
                        ];
                        $estimated += $line;
                    }
                }
            }

            $remainingBudget = max(0, $budget - $estimated);
            $remainingPeople = $preferCollection ? max(4, (int) ceil($people * 0.5)) : $people;
            $selectedProducts = $this->pickProducts(
                $products,
                $text,
                $occasion,
                $remainingPeople,
                $remainingBudget > 0 ? $remainingBudget : $budget,
                $preferCollection ? 2 : 3,
                $people
            );

            foreach ($selectedProducts as $row) {
                $picked[] = $row['suggestion'];
                $estimated += $row['line_total'];
            }
        }

        if ($picked === []) {
            foreach ($products->shuffle()->take(2) as $product) {
                $qty = $this->quantityForProduct($product, $people, $budget);
                $picked[] = [
                    'type' => 'product',
                    'id' => $product->id,
                    'quantity' => $qty,
                    'reason' => $this->productReason($product, $people, $qty, $occasion, false),
                ];
                $estimated += (float) $product->effectivePrice() * $qty;
            }
        }

        $focusNames = $matchedProducts->take(2)->pluck('name')->all();
        $message = $this->buildMessage(
            $occasion,
            $people,
            $budget,
            $estimated,
            $peopleAssumed,
            $budgetAssumed,
            $focusNames,
        );

        return [
            'message' => $message,
            'suggestions' => $picked,
            'people' => $peopleGiven,
            'budget' => $budgetGiven,
            'estimated_total' => round($estimated, 2),
        ];
    }

    public function parsePeople(string $text): ?int
    {
        if (preg_match('/(\d+)\s*(?:شخص|أشخاص|ضيف|ضيوف|نفر)/u', $text, $m)) {
            return max(1, min(200, (int) $m[1]));
        }

        if (preg_match('/لـ\s*(\d+)\s*(?:شخص|أشخاص|ضيف|ضيوف)?/u', $text, $m)) {
            return max(1, min(200, (int) $m[1]));
        }

        return null;
    }

    public function parseBudget(string $text): ?float
    {
        if (preg_match('/(?:ميزانية|بميزانية|بـ)\s*(\d+(?:\.\d+)?)\s*(?:د\.?ل|دينار|دنانير)?/u', $text, $m)) {
            return max(10, (float) $m[1]);
        }

        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:د\.?ل|دينار|دنانير)/u', $text, $m)) {
            return max(10, (float) $m[1]);
        }

        return null;
    }

    public function detectOccasion(string $text): ?string
    {
        return match (true) {
            str_contains($text, 'عيد ميلاد') || str_contains($text, 'ميلاد') => 'عيد ميلاد',
            str_contains($text, 'خطوبة') => 'خطوبة',
            str_contains($text, 'زواج') || str_contains($text, 'فرح') => 'فرح',
            str_contains($text, 'عزومة') || str_contains($text, 'ضيافة') => 'عزومة عائلية',
            str_contains($text, 'رمضان') => 'رمضان',
            str_contains($text, 'عيد') && ! str_contains($text, 'عيد ميلاد') => 'عيد',
            default => null,
        };
    }

    /**
     * باقة فقط إذا طلبها صراحة أو مناسبة كبيرة بدون صنف محدد.
     * ملاحظة: كلمة "عروض" وحدها لا تعني باقة.
     */
    protected function wantsCollection(string $text, ?string $occasion, bool $hasSpecificProductRequest): bool
    {
        if ($hasSpecificProductRequest) {
            return str_contains($text, 'باقة') || str_contains($text, 'باقات') || str_contains($text, 'بكج');
        }

        if (str_contains($text, 'باقة') || str_contains($text, 'باقات') || str_contains($text, 'بكج')) {
            return true;
        }

        return in_array($occasion, ['خطوبة', 'فرح', 'عيد ميلاد'], true);
    }

    /**
     * @param  SupportCollection<int, Product>  $products
     * @return SupportCollection<int, Product>
     */
    protected function matchProductsByRequest(SupportCollection $products, string $text): SupportCollection
    {
        $scored = $products->map(function (Product $product) use ($text) {
            $name = mb_strtolower($product->name);
            $hay = $name.' '.mb_strtolower((string) ($product->description ?? '')).' '.mb_strtolower((string) ($product->ingredients ?? ''));
            $score = 0;

            // تطابق قوي مع اسم الصنف أو جزء منه
            if (mb_strlen($name) >= 3 && str_contains($text, $name)) {
                $score += 50;
            }

            foreach ($this->productTokens($name) as $token) {
                if (mb_strlen($token) >= 3 && str_contains($text, $token)) {
                    $score += 20;
                }
            }

            // مرادفات شائعة
            foreach ($this->requestAliases($text) as $alias) {
                if (str_contains($hay, $alias)) {
                    $score += 25;
                }
            }

            return ['product' => $product, 'score' => $score];
        })
            ->filter(fn (array $row) => $row['score'] > 0)
            ->sortByDesc('score')
            ->values();

        return $scored->pluck('product')->values();
    }

    /** @return list<string> */
    protected function productTokens(string $name): array
    {
        $parts = preg_split('/\s+/u', $name) ?: [];

        return array_values(array_filter($parts, fn ($p) => mb_strlen($p) >= 3));
    }

    /** @return list<string> */
    protected function requestAliases(string $text): array
    {
        $aliases = [];
        if (str_contains($text, 'بقلاوة') || str_contains($text, 'بقلاوه') || str_contains($text, 'بقلاو')) {
            $aliases[] = 'بقلاوة';
        }
        if (str_contains($text, 'كنافة') || str_contains($text, 'كنافه')) {
            $aliases[] = 'كنافة';
        }
        if (str_contains($text, 'بسبوسة') || str_contains($text, 'بسبوسه')) {
            $aliases[] = 'بسبوسة';
        }
        if (str_contains($text, 'غريبة') || str_contains($text, 'غريبه')) {
            $aliases[] = 'غريبة';
        }
        if (str_contains($text, 'مقروطة') || str_contains($text, 'مقروطه')) {
            $aliases[] = 'مقروطة';
        }
        if (str_contains($text, 'تشيز') || str_contains($text, 'كيك')) {
            $aliases[] = 'تشيز';
            $aliases[] = 'كيك';
        }

        return $aliases;
    }

    /**
     * @param  SupportCollection<int, Collection>  $collections
     */
    protected function pickRelevantCollection(
        SupportCollection $collections,
        string $text,
        ?string $occasion,
        float $budget
    ): ?Collection {
        $scored = $collections->map(function (Collection $collection) use ($text, $occasion) {
            $name = mb_strtolower($collection->name);
            $score = 0;
            if ($occasion && str_contains($name, mb_strtolower($occasion))) {
                $score += 10;
            }
            foreach (preg_split('/\s+/u', $text) ?: [] as $word) {
                if (mb_strlen($word) >= 3 && str_contains($name, $word)) {
                    $score += 4;
                }
            }

            return ['collection' => $collection, 'score' => $score];
        })->sortByDesc('score')->values();

        $best = $scored->first();
        if ($best && $best['score'] > 0) {
            return $best['collection'];
        }

        return $this->pickAffordableCollection($collections, $budget);
    }

    /**
     * @param  SupportCollection<int, Collection>  $collections
     */
    protected function pickAffordableCollection(SupportCollection $collections, float $budget): ?Collection
    {
        $sorted = $collections->sortBy(fn (Collection $c) => abs((float) $c->price - ($budget * 0.45)))->values();

        foreach ($sorted as $collection) {
            if ((float) $collection->price <= $budget) {
                return $collection;
            }
        }

        return $sorted->first();
    }

    /**
     * @param  SupportCollection<int, Product>  $products
     * @return list<array{suggestion:array<string,mixed>,line_total:float}>
     */
    protected function pickProducts(
        SupportCollection $products,
        string $text,
        ?string $occasion,
        int $peopleForQty,
        float $budget,
        int $limit,
        int $totalPeople
    ): array {
        if ($products->isEmpty() || $budget <= 0) {
            return [];
        }

        $scored = $products->map(function (Product $product) use ($text, $occasion) {
            $hay = mb_strtolower($product->name.' '.($product->description ?? '').' '.($product->ingredients ?? ''));
            $score = 0;
            foreach (preg_split('/\s+/u', $text) ?: [] as $word) {
                if (mb_strlen($word) >= 3 && str_contains($hay, $word)) {
                    $score += 3;
                }
            }
            if ($occasion && str_contains($hay, mb_strtolower($occasion))) {
                $score += 2;
            }
            if ($product->discount_price !== null) {
                $score += 1;
            }

            return ['product' => $product, 'score' => $score];
        })->sortByDesc('score')->values();

        $pool = $scored->pluck('product')->values();
        $affordable = $pool->filter(fn (Product $p) => (float) $p->effectivePrice() <= $budget)->values();
        if ($affordable->isEmpty()) {
            $affordable = $pool;
        }

        // حافظ على أعلى تطابق بدل العشوائية الكاملة
        $top = $affordable->take(max($limit, 3));
        $chosen = $top->take($limit)->values();
        $rows = [];
        $spent = 0.0;
        $share = max(1, (int) floor($peopleForQty / max(1, $chosen->count())));

        foreach ($chosen as $index => $product) {
            $leftBudget = max(0, $budget - $spent);
            $qtyPeople = $share + ($index === 0 ? $peopleForQty % max(1, $chosen->count()) : 0);
            $qty = $this->quantityForProduct($product, $qtyPeople, $leftBudget);
            $price = (float) $product->effectivePrice();
            $line = $price * $qty;

            if ($line > $leftBudget && $price > 0) {
                $qty = max((int) ($product->min_order_qty ?: 1), (int) floor($leftBudget / $price));
                $qty = $this->clampQty($product, $qty);
                $line = $price * $qty;
            }

            if ($qty < 1) {
                continue;
            }

            $rows[] = [
                'suggestion' => [
                    'type' => 'product',
                    'id' => $product->id,
                    'quantity' => $qty,
                    'reason' => $this->productReason($product, $totalPeople, $qty, $occasion, false),
                ],
                'line_total' => $line,
            ];
            $spent += $line;
        }

        return $rows;
    }

    protected function quantityForProduct(Product $product, int $people, float $budget): int
    {
        $people = max(1, $people);
        $price = max(0.01, (float) $product->effectivePrice());
        $name = mb_strtolower($product->name);

        $perPerson = match (true) {
            str_contains($name, 'كيك') || str_contains($name, 'تشيز') => max(1, (int) ceil($people / 10)),
            str_contains($name, 'عصير') || str_contains($name, 'شاي') || str_contains($name, 'قهوة') => max(1, $people),
            str_contains($name, 'صاموسة') || str_contains($name, 'بيتزا') => max(1, (int) ceil($people * 1.5)),
            default => max(1, (int) ceil($people * 2)),
        };

        $maxByBudget = max(1, (int) floor($budget / $price));
        $qty = min($perPerson, $maxByBudget);

        return $this->clampQty($product, $qty);
    }

    protected function clampQty(Product $product, int $qty): int
    {
        $min = max(1, (int) ($product->min_order_qty ?: 1));
        $max = (int) ($product->max_order_qty ?: 999);
        if ($max < $min) {
            $max = $min;
        }

        return max($min, min($max, $qty));
    }

    protected function productReason(Product $product, int $people, int $qty, ?string $occasion, bool $focused): string
    {
        $line = rtrim(rtrim(number_format((float) $product->effectivePrice() * $qty, 2, '.', ''), '0'), '.');

        if ($focused) {
            return "حسب طلبك · كمية ×{$qty} · {$line} د.ل";
        }

        $label = $occasion ? "لمناسبة {$occasion}" : 'اقتراح مناسب';

        return "{$label} · ×{$qty} · {$line} د.ل";
    }

    protected function collectionReason(?string $occasion, int $people, int $qty): string
    {
        $label = $occasion ? "باقة {$occasion}" : 'باقة جاهزة';

        return "{$label} · ×{$qty} تقريباً لـ {$people} ضيف";
    }

    /**
     * @param  list<string>  $focusNames
     */
    protected function buildMessage(
        ?string $occasion,
        int $people,
        float $budget,
        float $estimated,
        bool $peopleAssumed,
        bool $budgetAssumed,
        array $focusNames,
    ): string {
        $totalText = rtrim(rtrim(number_format($estimated, 2, '.', ''), '0'), '.');
        $budgetText = rtrim(rtrim(number_format($budget, 2, '.', ''), '0'), '.');
        $focus = $focusNames !== [] ? implode(' و', array_slice($focusNames, 0, 2)) : null;

        if ($focus) {
            $opener = $occasion
                ? "طلبت {$focus} للمناسبة — جهّزت لك عرضاً مركّزاً عليها."
                : "طلبت {$focus} — هذي أفضل العروض المتوفرة لها عندنا.";
        } elseif ($occasion) {
            $opener = "رتّبت لك عرضاً مناسباً لمناسبة {$occasion}.";
        } else {
            $opener = 'رتّبت لك عرضاً من الأصناف المتاحة حسب طلبك.';
        }

        $details = [];
        if (! $peopleAssumed) {
            $details[] = "لـ {$people} ضيف";
        }
        if (! $budgetAssumed) {
            $details[] = "بميزانية {$budgetText} د.ل";
        }
        $details[] = "الإجمالي التقريبي {$totalText} د.ل";

        $body = implode(' · ', $details).'.';

        $ask = match (true) {
            $peopleAssumed && $budgetAssumed => 'كم عدد الضيوف وشن ميزانيتك لأضبط الكميات أدق؟',
            $peopleAssumed => 'كم عدد الضيوف تقريباً؟',
            $budgetAssumed => 'شن الميزانية المناسبة لك بالدينار؟',
            default => 'تحب أزوّد الكمية أو أبدّل لصنف قريب؟',
        };

        return "{$opener} {$body} {$ask}";
    }
}
