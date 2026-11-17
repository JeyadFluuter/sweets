<?php

namespace App\Services\Ai;

use App\Models\Collection;
use App\Models\Product;

/**
 * يتحقق برمجياً من IDs والأسعار والكميات والإجمالي — المصدر الوحيد الموثوق للأسعار.
 */
class OfferValidator
{
    /**
     * @param  list<array<string,mixed>>  $raw
     * @param  list<string>  $excludeKeys  لتجنب تكرار نفس التشكيلة
     * @return array{suggestions:list<array<string,mixed>>,estimated_total:float,budget_adjusted:bool,empty_catalog:bool}
     */
    public function validate(
        array $raw,
        ?float $budget = null,
        array $excludeKeys = [],
        bool $allowRepeat = false,
    ): array {
        $productsExist = Product::query()->available()->where('ai_suggestable', true)->exists();
        $collectionsExist = Collection::query()->visible()->exists();
        if (! $productsExist && ! $collectionsExist) {
            return [
                'suggestions' => [],
                'estimated_total' => 0.0,
                'budget_adjusted' => false,
                'empty_catalog' => true,
            ];
        }

        $clean = [];
        foreach ($raw as $item) {
            $type = (string) ($item['type'] ?? '');
            $id = (int) ($item['id'] ?? 0);
            $key = $type.':'.$id;

            if (! $allowRepeat && $excludeKeys !== [] && in_array($key, $excludeKeys, true)) {
                continue;
            }

            if ($type === 'product') {
                $row = $this->normalizeProduct($id, (int) ($item['quantity'] ?? 1), (string) ($item['reason'] ?? ''));
                if ($row) {
                    $clean[] = $row;
                }
            }

            if ($type === 'collection') {
                $row = $this->normalizeCollection($id, (int) ($item['quantity'] ?? 1), (string) ($item['reason'] ?? ''));
                if ($row) {
                    $clean[] = $row;
                }
            }
        }

        // إذا استُبعد الكل بسبب منع التكرار، اسمح بإعادة استخدام نفس العناصر مع تعديل الكمية لاحقاً
        if ($clean === [] && $raw !== [] && $excludeKeys !== [] && ! $allowRepeat) {
            return $this->validate($raw, $budget, [], true);
        }

        $adjusted = false;
        if ($budget !== null && $budget > 0) {
            [$clean, $adjusted] = $this->fitBudget($clean, $budget);
        }

        $total = 0.0;
        foreach ($clean as $row) {
            $total += (float) $row['unit_price'] * (int) $row['quantity'];
        }

        return [
            'suggestions' => array_values($clean),
            'estimated_total' => round($total, 2),
            'budget_adjusted' => $adjusted,
            'empty_catalog' => false,
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    protected function normalizeProduct(int $id, int $qty, string $reason): ?array
    {
        $product = Product::query()->available()->where('ai_suggestable', true)->find($id);
        if (! $product) {
            return null;
        }

        $qty = $this->clampQty($product->min_order_qty, $product->max_order_qty, $qty);
        $image = $product->getFirstMediaUrl('images') ?: null;

        return [
            'type' => 'product',
            'id' => $product->id,
            'name' => $product->name,
            'quantity' => $qty,
            'unit_price' => (float) $product->effectivePrice(),
            'image_url' => $image !== '' ? $image : null,
            'reason' => $reason,
            'min_qty' => max(1, (int) ($product->min_order_qty ?: 1)),
            'max_qty' => (int) ($product->max_order_qty ?: 999),
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    protected function normalizeCollection(int $id, int $qty, string $reason): ?array
    {
        $collection = Collection::query()->visible()->find($id);
        if (! $collection) {
            return null;
        }

        $qty = max(1, $qty);
        $image = $collection->getFirstMediaUrl('images') ?: null;

        return [
            'type' => 'collection',
            'id' => $collection->id,
            'name' => $collection->name,
            'quantity' => $qty,
            'unit_price' => (float) $collection->price,
            'image_url' => $image !== '' ? $image : null,
            'reason' => $reason,
            'min_qty' => 1,
            'max_qty' => 99,
        ];
    }

    protected function clampQty(mixed $min, mixed $max, int $qty): int
    {
        $minQty = max(1, (int) ($min ?: 1));
        $maxQty = (int) ($max ?: 999);
        if ($maxQty < $minQty) {
            $maxQty = $minQty;
        }

        return max($minQty, min($maxQty, max(1, $qty)));
    }

    /**
     * يقلّل الكميات تدريجياً للبقاء ضمن الميزانية بدل اقتراح أصناف عشوائية.
     *
     * @param  list<array<string,mixed>>  $items
     * @return array{0:list<array<string,mixed>>,1:bool}
     */
    protected function fitBudget(array $items, float $budget): array
    {
        if ($items === []) {
            return [[], false];
        }

        $total = $this->sum($items);
        if ($total <= $budget * 1.02) {
            return [$items, false];
        }

        $adjusted = false;
        $guard = 0;
        while ($this->sum($items) > $budget && $guard < 200) {
            $guard++;
            // خفّض أكبر بند أولاً مع احترام min_qty
            usort($items, fn ($a, $b) => ((float) $b['unit_price'] * $b['quantity']) <=> ((float) $a['unit_price'] * $a['quantity']));
            $reduced = false;
            foreach ($items as $i => $item) {
                $min = (int) ($item['min_qty'] ?? 1);
                if ((int) $item['quantity'] > $min) {
                    $items[$i]['quantity'] = (int) $item['quantity'] - 1;
                    $items[$i]['reason'] = trim(($item['reason'] ?? '').' · معدّل ضمن الميزانية');
                    $adjusted = true;
                    $reduced = true;
                    break;
                }
            }

            if (! $reduced) {
                // احذف أغلى صنف إذا ما عاد يمكن تخفيضه
                array_shift($items);
                $adjusted = true;
                if ($items === []) {
                    break;
                }
            }
        }

        return [array_values($items), $adjusted];
    }

    /** @param  list<array<string,mixed>>  $items */
    protected function sum(array $items): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            $total += (float) $item['unit_price'] * (int) $item['quantity'];
        }

        return $total;
    }
}
