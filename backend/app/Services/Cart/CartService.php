<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Coupons\CouponService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function __construct(private readonly CouponService $couponService) {}

    public function getOrCreate(Customer $customer): Cart
    {
        return Cart::query()->firstOrCreate(['customer_id' => $customer->id]);
    }

    public function summary(Cart $cart): array
    {
        $cart->load('items', 'coupon');
        $lines = [];
        $subtotal = 0.0;

        foreach ($cart->items as $item) {
            try {
                $resolved = $this->resolveSellable($item->item_type, $item->item_id);
            } catch (ValidationException) {
                // صنف لم يعد متاحاً — نحذفه من السلة بدل كسر GET /cart
                $item->delete();
                continue;
            }

            $unit = (float) $resolved['unit_price'];
            $lineTotal = $unit * $item->quantity;
            $subtotal += $lineTotal;

            $lines[] = [
                'id' => $item->id,
                'item_type' => $item->item_type,
                'item_id' => $item->item_id,
                'name' => $resolved['name'],
                'subtitle' => $resolved['subtitle'],
                'image_url' => $resolved['image_url'],
                'quantity' => $item->quantity,
                'unit_price' => number_format($unit, 2, '.', ''),
                'line_total' => number_format($lineTotal, 2, '.', ''),
                'min_order_qty' => $resolved['min_order_qty'],
                'max_order_qty' => $resolved['max_order_qty'],
            ];
        }

        $discount = 0.0;
        $couponPayload = null;
        if ($cart->coupon) {
            $validation = $this->couponService->validate($cart->coupon->code, [
                'subtotal' => $subtotal,
                'items' => collect($lines)->map(fn ($l) => [
                    'product_id' => $l['item_type'] === 'product' ? $l['item_id'] : null,
                    'quantity' => $l['quantity'],
                    'unit_price' => $l['unit_price'],
                ])->filter(fn ($l) => $l['product_id'])->values()->all(),
            ], $cart->customer);

            if ($validation['valid']) {
                $discount = (float) $validation['discount'];
                $couponPayload = [
                    'id' => $cart->coupon->id,
                    'code' => $cart->coupon->code,
                    'discount' => number_format($discount, 2, '.', ''),
                ];
            }
        }

        return [
            'id' => $cart->id,
            'items' => $lines,
            'subtotal' => number_format($subtotal, 2, '.', ''),
            'discount' => number_format($discount, 2, '.', ''),
            'total' => number_format(max(0, $subtotal - $discount), 2, '.', ''),
            'coupon' => $couponPayload,
            'currency' => Setting::getValue('currency', 'LYD'),
        ];
    }

    public function addItem(Cart $cart, string $type, int $id, int $quantity = 1): Cart
    {
        $resolved = $this->resolveSellable($type, $id);
        $min = (int) $resolved['min_order_qty'];
        $max = (int) $resolved['max_order_qty'];

        return DB::transaction(function () use ($cart, $type, $id, $quantity, $min, $max) {
            $item = CartItem::query()->firstOrNew([
                'cart_id' => $cart->id,
                'item_type' => $type,
                'item_id' => $id,
            ]);

            $next = ($item->exists ? $item->quantity : 0) + max(1, $quantity);
            if (! $item->exists) {
                $next = max($min, $quantity);
            }

            if ($next < $min) {
                throw ValidationException::withMessages([
                    'quantity' => ["أقل كمية مسموحة لهذا الصنف هي {$min}."],
                ]);
            }

            if ($next > $max) {
                throw ValidationException::withMessages([
                    'quantity' => ["أكثر كمية مسموحة لهذا الصنف هي {$max}."],
                ]);
            }

            $item->quantity = $next;
            $item->save();

            return $cart->fresh('items');
        });
    }

    public function updateItem(CartItem $item, int $quantity): Cart
    {
        if ($quantity <= 0) {
            $cart = $item->cart;
            $item->delete();

            return $cart->fresh('items');
        }

        $resolved = $this->resolveSellable($item->item_type, $item->item_id);
        $min = (int) $resolved['min_order_qty'];
        $max = (int) $resolved['max_order_qty'];

        if ($quantity < $min) {
            throw ValidationException::withMessages([
                'quantity' => ["أقل كمية مسموحة لهذا الصنف هي {$min}."],
            ]);
        }

        if ($quantity > $max) {
            throw ValidationException::withMessages([
                'quantity' => ["أكثر كمية مسموحة لهذا الصنف هي {$max}."],
            ]);
        }

        $item->update(['quantity' => $quantity]);

        return $item->cart->fresh('items');
    }

    public function removeItem(CartItem $item): Cart
    {
        $cart = $item->cart;
        $item->delete();

        return $cart->fresh('items');
    }

    public function applyCoupon(Cart $cart, string $code): Cart
    {
        $summary = $this->summary($cart);
        $result = $this->couponService->validate($code, [
            'subtotal' => (float) $summary['subtotal'],
            'items' => collect($summary['items'])->map(fn ($l) => [
                'product_id' => $l['item_type'] === 'product' ? $l['item_id'] : null,
                'quantity' => $l['quantity'],
                'unit_price' => $l['unit_price'],
            ])->filter(fn ($l) => $l['product_id'])->values()->all(),
        ], $cart->customer);

        if (! $result['valid']) {
            throw ValidationException::withMessages(['code' => [$result['message']]]);
        }

        $cart->update(['coupon_id' => $result['coupon']->id]);

        return $cart->fresh('items', 'coupon');
    }

    public function removeCoupon(Cart $cart): Cart
    {
        $cart->update(['coupon_id' => null]);

        return $cart->fresh('items', 'coupon');
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
        $cart->update(['coupon_id' => null]);
    }

    /**
     * @return array{name:string,subtitle:?string,image_url:?string,unit_price:float|string,min_order_qty:int,max_order_qty:int}
     */
    public function resolveSellable(string $type, int $id): array
    {
        if ($type === 'product') {
            $product = Product::query()->available()->with('category')->find($id);
            if (! $product) {
                throw ValidationException::withMessages(['item_id' => ['المنتج غير متاح.']]);
            }

            $image = $product->getFirstMediaUrl('images') ?: null;

            return [
                'name' => $product->name,
                'subtitle' => $product->category?->name,
                'image_url' => $image !== '' ? $image : null,
                'unit_price' => $product->effectivePrice(),
                'min_order_qty' => $product->minOrderQty(),
                'max_order_qty' => $product->maxOrderQty(),
            ];
        }

        if ($type === 'collection') {
            $collection = Collection::query()->visible()->with('items.product')->find($id);
            if (! $collection) {
                throw ValidationException::withMessages(['item_id' => ['الباقة غير متاحة.']]);
            }

            $image = $collection->getFirstMediaUrl('images') ?: null;
            $count = $collection->items->count();

            return [
                'name' => $collection->name,
                'subtitle' => $count > 0 ? "باقة · {$count} أصناف" : 'باقة',
                'image_url' => $image !== '' ? $image : null,
                'unit_price' => $collection->price,
                'min_order_qty' => 1,
                'max_order_qty' => 99,
            ];
        }

        throw ValidationException::withMessages(['item_type' => ['نوع العنصر غير مدعوم.']]);
    }
}
