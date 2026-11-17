<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Services\Media\MediaSync;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    public function __construct(private readonly MediaSync $mediaSync) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Product::query()
            ->with(['category', 'tags', 'media'])
            ->when($filters['category_id'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->when(isset($filters['is_available']), fn ($q) => $q->where('is_available', $filters['is_available']))
            ->when($filters['tag_id'] ?? null, function ($q, $tagId) {
                $q->whereHas('tags', fn ($tq) => $tq->where('tags.id', $tagId));
            })
            ->when($filters['on_discount'] ?? false, fn ($q) => $q->onDiscount())
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $tagIds = $data['tag_ids'] ?? $data['tags'] ?? [];
            $images = $data['images'] ?? null;
            unset($data['tag_ids'], $data['tags'], $data['images']);

            $data['slug'] = $data['slug'] ?? Str::slug($data['name']).'-'.Str::random(4);

            $product = Product::query()->create($data);
            if (is_array($tagIds)) {
                $product->tags()->sync($tagIds);
            }

            $this->mediaSync->syncMany($product, $images, 'images', 'products');

            activity()->performedOn($product)->log('إنشاء منتج');

            return $product->load(['category', 'tags', 'media']);
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $tagIds = $data['tag_ids'] ?? $data['tags'] ?? null;
            $images = array_key_exists('images', $data) ? $data['images'] : null;
            unset($data['tag_ids'], $data['tags'], $data['images']);

            if (isset($data['name']) && empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']).'-'.$product->id;
            }

            $product->update($data);

            if (is_array($tagIds)) {
                $product->tags()->sync($tagIds);
            }

            if ($images !== null) {
                $this->mediaSync->syncMany($product, $images, 'images', 'products');
            }

            activity()->performedOn($product)->log('تحديث منتج');

            return $product->fresh(['category', 'tags', 'media']);
        });
    }

    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product) {
            activity()->performedOn($product)->log('حذف منتج');
            $product->clearMediaCollection('images');
            $product->delete();
        });
    }
}
