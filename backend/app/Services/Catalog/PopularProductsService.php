<?php

namespace App\Services\Catalog;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PopularProductsService
{
    public function get(int $limit = 8): Collection
    {
        return Cache::remember("catalog.popular_products.{$limit}", now()->addHour(), function () use ($limit) {
            return $this->ranked($limit);
        });
    }

    /** قائمة كاملة مرتّبة للقسم «الأكثر طلباً». */
    public function all(int $limit = 50): Collection
    {
        return Cache::remember("catalog.popular_products.all.{$limit}", now()->addHour(), function () use ($limit) {
            $ranked = $this->ranked($limit);

            if ($ranked->isNotEmpty()) {
                return $ranked;
            }

            // إن لم توجد مبيعات مكتملة بعد: أحدث المنتجات المتاحة
            return Product::query()
                ->available()
                ->with(['category', 'tags', 'media'])
                ->orderByDesc('id')
                ->limit($limit)
                ->get();
        });
    }

    protected function ranked(int $limit): Collection
    {
        if (! Schema::hasTable('order_items') || ! Schema::hasTable('orders')) {
            return collect();
        }

        $productIds = \DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'completed')
            ->where('orders.updated_at', '>=', now()->subDays(30))
            ->where('order_items.item_type', 'product')
            ->whereNotNull('order_items.item_id')
            ->select('order_items.item_id')
            ->selectRaw('SUM(order_items.quantity) as total_qty')
            ->groupBy('order_items.item_id')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->pluck('item_id');

        if ($productIds->isEmpty()) {
            return collect();
        }

        return Product::query()
            ->available()
            ->with(['category', 'tags', 'media'])
            ->whereIn('id', $productIds)
            ->get()
            ->sortBy(fn (Product $product) => $productIds->search($product->id))
            ->values();
    }

    public function refresh(): void
    {
        Cache::forget('catalog.popular_products');
        foreach ([8, 10, 50] as $limit) {
            Cache::forget("catalog.popular_products.{$limit}");
            Cache::forget("catalog.popular_products.all.{$limit}");
        }
        $this->get();
    }
}
