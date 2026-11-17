<?php

namespace App\Services\Catalog;

use App\Models\Category;
use App\Services\Media\MediaSync;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryService
{
    public function __construct(private readonly MediaSync $mediaSync) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Category::query()
            ->with(['parent', 'media'])
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when($filters['parent_id'] ?? null, fn ($q, $parentId) => $q->where('parent_id', $parentId))
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%");
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(array $data): Category
    {
        return DB::transaction(function () use ($data) {
            $image = $data['image'] ?? null;
            unset($data['image']);

            $data['slug'] = $data['slug'] ?? Str::slug($data['name']).'-'.Str::random(4);

            $category = Category::query()->create($data);
            $this->mediaSync->syncSingle($category, $image, 'image', 'categories');

            activity()->performedOn($category)->log('إنشاء قسم');

            return $category->load(['parent', 'media']);
        });
    }

    public function update(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data) {
            $image = $data['image'] ?? null;
            unset($data['image']);

            if (isset($data['name']) && empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']).'-'.$category->id;
            }

            $category->update($data);

            if ($image !== null) {
                $this->mediaSync->syncSingle($category, $image, 'image', 'categories');
            }

            activity()->performedOn($category)->log('تحديث قسم');

            return $category->fresh(['parent', 'media']);
        });
    }

    public function delete(Category $category): void
    {
        DB::transaction(function () use ($category) {
            activity()->performedOn($category)->log('حذف قسم');
            $category->clearMediaCollection('image');
            $category->delete();
        });
    }
}
