<?php

namespace Database\Seeders;

use App\Enums\BannerLinkType;
use App\Enums\CouponScope;
use App\Enums\CouponType;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Database\Seeder;

class MarketingSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::query()->orderBy('sort_order')->get();
        $products = Product::query()->orderBy('sort_order')->get();

        $banners = [
            ['عرض الافتتاح', BannerLinkType::Category, $categories->first()?->id, 1],
            ['باقات المناسبات', BannerLinkType::None, null, 2],
            ['توصيل طرابلس', BannerLinkType::None, null, 3],
        ];

        foreach ($banners as [$title, $type, $linkId, $sort]) {
            Banner::query()->updateOrCreate(
                ['title' => $title],
                [
                    'link_type' => $type,
                    'link_id' => $linkId,
                    'sort_order' => $sort,
                    'starts_at' => now()->subDays(3),
                    'ends_at' => now()->addMonths(2),
                    'is_active' => true,
                ]
            );
        }

        if ($products->count() >= 3) {
            $bundles = [
                ['باقة العيد', 'eid-bundle', 120, $products->take(3)],
                ['باقة الخطوبة', 'engagement-bundle', 180, $products->skip(1)->take(3)],
                ['باقة العزومة', 'gathering-bundle', 95, $products->take(2)],
            ];

            foreach ($bundles as $i => [$name, $slug, $price, $items]) {
                $collection = Collection::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'description' => "{$name} جاهزة للمناسبات — سعر ثابت",
                        'price' => $price,
                        'is_active' => true,
                        'starts_at' => now()->subDay(),
                        'ends_at' => now()->addMonths(3),
                        'sort_order' => $i + 1,
                    ]
                );

                $collection->items()->delete();
                foreach ($items->values() as $index => $product) {
                    $collection->items()->create([
                        'product_id' => $product->id,
                        'quantity' => $index === 0 ? 2 : 1,
                    ]);
                }
            }
        }

        Coupon::query()->updateOrCreate(
            ['code' => 'WELCOME10'],
            [
                'type' => CouponType::Percent,
                'value' => 10,
                'max_discount' => 25,
                'min_order_amount' => 30,
                'usage_limit' => 500,
                'usage_limit_per_customer' => 2,
                'used_count' => 0,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addMonths(3),
                'scope' => CouponScope::All,
                'is_active' => true,
            ]
        );

        Coupon::query()->updateOrCreate(
            ['code' => 'FIXED15'],
            [
                'type' => CouponType::Fixed,
                'value' => 15,
                'max_discount' => null,
                'min_order_amount' => 50,
                'usage_limit' => 100,
                'usage_limit_per_customer' => 1,
                'used_count' => 0,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addMonth(),
                'scope' => CouponScope::All,
                'is_active' => true,
            ]
        );
    }
}
