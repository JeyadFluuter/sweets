<?php

namespace App\Services\Catalog;

use App\Models\Category;
use App\Models\Product;

class HomeService
{
    public function __construct(
        private readonly BannerService $bannerService,
        private readonly CollectionService $collectionService,
        private readonly PopularProductsService $popularProductsService,
    ) {}

    public function payload(): array
    {
        $categories = Category::query()
            ->active()
            ->with(['media'])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        $offers = Product::query()
            ->available()
            ->onDiscount()
            ->with(['category', 'tags', 'media'])
            ->orderBy('sort_order')
            ->limit(12)
            ->get();

        return [
            'banners' => $this->bannerService->listActive(),
            'categories' => $categories,
            'popular_products' => $this->popularProductsService->get(8),
            'offers' => $offers,
            'collections' => $this->collectionService->listVisible(6),
        ];
    }
}
