<?php

namespace App\Services\Coupons;

use App\Enums\CouponScope;
use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CouponService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Coupon::query()
            ->with(['categories', 'products'])
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where('code', 'like', "%{$search}%"))
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function create(array $data): Coupon
    {
        return DB::transaction(function () use ($data) {
            $categoryIds = $data['category_ids'] ?? [];
            $productIds = $data['product_ids'] ?? [];
            unset($data['category_ids'], $data['product_ids']);

            $data['code'] = Str::upper($data['code']);
            $coupon = Coupon::query()->create($data);
            $coupon->categories()->sync($categoryIds);
            $coupon->products()->sync($productIds);

            activity()->performedOn($coupon)->log('إنشاء كوبون');

            return $coupon->load(['categories', 'products']);
        });
    }

    public function update(Coupon $coupon, array $data): Coupon
    {
        return DB::transaction(function () use ($coupon, $data) {
            $categoryIds = $data['category_ids'] ?? null;
            $productIds = $data['product_ids'] ?? null;
            unset($data['category_ids'], $data['product_ids']);

            if (isset($data['code'])) {
                $data['code'] = Str::upper($data['code']);
            }

            $coupon->update($data);

            if (is_array($categoryIds)) {
                $coupon->categories()->sync($categoryIds);
            }

            if (is_array($productIds)) {
                $coupon->products()->sync($productIds);
            }

            activity()->performedOn($coupon)->log('تحديث كوبون');

            return $coupon->fresh(['categories', 'products']);
        });
    }

    public function delete(Coupon $coupon): void
    {
        DB::transaction(function () use ($coupon) {
            activity()->performedOn($coupon)->log('حذف كوبون');
            $coupon->delete();
        });
    }

    /**
     * @param  array{items?: list<array{product_id:int,quantity?:int,unit_price?:float|string}>, subtotal?: float|string}  $cart
     * @return array{valid: bool, message: string, discount: float, coupon: ?Coupon}
     */
    public function validate(string $code, array $cart, ?Customer $customer = null): array
    {
        $coupon = Coupon::query()
            ->with(['categories', 'products'])
            ->where('code', Str::upper(trim($code)))
            ->first();

        if (! $coupon) {
            return $this->reject('كود الخصم غير موجود.');
        }

        if (! $coupon->is_active) {
            return $this->reject('كود الخصم غير مفعّل.');
        }

        $now = now();

        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return $this->reject('كود الخصم لم يبدأ بعد.');
        }

        if ($coupon->ends_at && $coupon->ends_at->isPast()) {
            return $this->reject('انتهت صلاحية كود الخصم.');
        }

        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            return $this->reject('تم استنفاد الحد الأقصى لاستخدام هذا الكوبون.');
        }

        if ($customer && $coupon->usage_limit_per_customer !== null) {
            $customerUsages = $coupon->usages()->where('customer_id', $customer->id)->count();
            if ($customerUsages >= $coupon->usage_limit_per_customer) {
                return $this->reject('لقد استخدمت هذا الكوبون بالحد الأقصى المسموح.');
            }
        }

        $items = $cart['items'] ?? [];
        $eligibleSubtotal = $this->eligibleSubtotal($coupon, $items);
        $cartSubtotal = (float) ($cart['subtotal'] ?? $eligibleSubtotal);

        if ($cartSubtotal < (float) $coupon->min_order_amount) {
            return $this->reject(
                'الحد الأدنى للطلب لاستخدام هذا الكوبون هو '.$coupon->min_order_amount.' د.ل.'
            );
        }

        if ($coupon->scope !== CouponScope::All && $eligibleSubtotal <= 0) {
            return $this->reject('هذا الكوبون لا ينطبق على المنتجات الموجودة في سلتك.');
        }

        $base = $coupon->scope === CouponScope::All ? $cartSubtotal : $eligibleSubtotal;
        $discount = $this->calculateDiscount($coupon, $base);

        if ($discount <= 0) {
            return $this->reject('لا يمكن تطبيق خصم على هذه السلة.');
        }

        return [
            'valid' => true,
            'message' => 'تم تطبيق كود الخصم بنجاح.',
            'discount' => round($discount, 2),
            'coupon' => $coupon,
        ];
    }

    /**
     * @param  list<array{product_id:int,quantity?:int,unit_price?:float|string}>  $items
     */
    protected function eligibleSubtotal(Coupon $coupon, array $items): float
    {
        if ($items === []) {
            return 0.0;
        }

        $productIds = collect($items)->pluck('product_id')->filter()->unique()->values();
        $products = Product::query()
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $allowedCategoryIds = $coupon->categories->pluck('id')->all();
        $allowedProductIds = $coupon->products->pluck('id')->all();

        $total = 0.0;

        foreach ($items as $item) {
            $product = $products->get($item['product_id'] ?? null);
            if (! $product) {
                continue;
            }

            $eligible = match ($coupon->scope) {
                CouponScope::All => true,
                CouponScope::Categories => in_array($product->category_id, $allowedCategoryIds, true),
                CouponScope::Products => in_array($product->id, $allowedProductIds, true),
            };

            if (! $eligible) {
                continue;
            }

            $qty = (int) ($item['quantity'] ?? 1);
            $unit = (float) ($item['unit_price'] ?? $product->effectivePrice());
            $total += $qty * $unit;
        }

        return $total;
    }

    protected function calculateDiscount(Coupon $coupon, float $base): float
    {
        $discount = match ($coupon->type) {
            CouponType::Percent => $base * ((float) $coupon->value / 100),
            CouponType::Fixed => (float) $coupon->value,
        };

        if ($coupon->type === CouponType::Percent && $coupon->max_discount !== null) {
            $discount = min($discount, (float) $coupon->max_discount);
        }

        return min($discount, $base);
    }

    /**
     * @return array{valid: bool, message: string, discount: float, coupon: null}
     */
    protected function reject(string $message): array
    {
        return [
            'valid' => false,
            'message' => $message,
            'discount' => 0.0,
            'coupon' => null,
        ];
    }
}
