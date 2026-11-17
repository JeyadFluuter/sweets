<?php

namespace Database\Seeders;

use App\Enums\TagType;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'شرقيات' => [
                ['بقلاوة بالفستق', 45, 6, 'فستق سوري وزبدة'],
                ['كنافة نابلسية', 55, 8, 'جبنة عكاوي وقطر'],
                ['بسبوسة لوز', 30, 4, 'سميد ولوز'],
                ['مقروطة', 35, 5, 'تمر ومكسرات'],
                ['غريبة', 25, 3, 'سمن وطحين'],
            ],
            'غربيات' => [
                ['تشيز كيك فراولة', 70, 12, 'كريمة وجبنة'],
                ['براونيز شوكولاتة', 40, 6, 'كاكاو وجوز'],
                ['كب كيك فانيلا', 8, 2, 'حبة واحدة'],
            ],
            'مالحة' => [
                ['صاموسة لحم', 3.5, 2, 'حبة'],
                ['صاموسة جبن', 3, 2, 'حبة'],
                ['فطيرة زعتر', 12, 3, 'فردية'],
            ],
            'مشروبات' => [
                ['عصير برتقال طازج', 10, 1, 'كوب 400مل'],
                ['عصير ليمون بالنعناع', 9, 1, 'كوب 400مل'],
                ['شاي أحمر', 3, 1, 'كأس'],
            ],
        ];

        $tags = [
            ['زواج', TagType::Occasion],
            ['عيد ميلاد', TagType::Occasion],
            ['تخرج', TagType::Occasion],
            ['عزاء', TagType::Occasion],
            ['عزومة', TagType::Occasion],
            ['رمضان', TagType::Occasion],
            ['مكسرات', TagType::Attribute],
            ['بدون سكر', TagType::Attribute],
            ['حلال', TagType::Attribute],
        ];

        $slugify = static function (string $value): string {
            $slug = Str::slug($value);

            return $slug !== '' ? $slug : 'item-'.substr(md5($value), 0, 8);
        };

        $tagModels = collect($tags)->mapWithKeys(function (array $tag) use ($slugify) {
            [$name, $type] = $tag;
            $model = Tag::query()->updateOrCreate(
                ['slug' => $slugify($name)],
                ['name' => $name, 'type' => $type, 'is_active' => true]
            );

            return [$name => $model];
        });

        $sort = 1;
        foreach ($categories as $categoryName => $products) {
            $category = Category::query()->updateOrCreate(
                ['slug' => $slugify($categoryName)],
                [
                    'name' => $categoryName,
                    'sort_order' => $sort++,
                    'is_active' => true,
                ]
            );

            foreach ($products as $index => [$productName, $price, $lead, $ingredients]) {
                $product = Product::query()->updateOrCreate(
                    ['slug' => $slugify($productName)],
                    [
                        'name' => $productName,
                        'description' => "منتج مميز من معمل الحلويات: {$productName}",
                        'price' => $price,
                        'category_id' => $category->id,
                        'is_available' => true,
                        'ingredients' => $ingredients,
                        'allergen_alerts' => str_contains($ingredients, 'لوز') || str_contains($ingredients, 'فستق') || str_contains($ingredients, 'جوز')
                            ? 'يحتوي على مكسرات'
                            : null,
                        'ai_suggestable' => true,
                        'sort_order' => $index + 1,
                    ]
                );

                $attach = $tagModels->random(min(2, $tagModels->count()))->pluck('id')->all();
                $product->tags()->sync($attach);
            }
        }

        // عرض على أول منتج
        $featured = Product::query()->where('slug', $slugify('بقلاوة بالفستق'))->first();
        if ($featured) {
            $featured->update([
                'discount_price' => 38,
                'discount_starts_at' => now()->subDay(),
                'discount_ends_at' => now()->addMonth(),
            ]);
        }
    }
}
