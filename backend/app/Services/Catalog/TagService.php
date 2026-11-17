<?php

namespace App\Services\Catalog;

use App\Models\Tag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TagService
{
    public function list(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        return Tag::query()
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(array $data): Tag
    {
        return DB::transaction(function () use ($data) {
            $data['slug'] = $data['slug'] ?? Str::slug($data['name']).'-'.Str::random(4);
            $tag = Tag::query()->create($data);
            activity()->performedOn($tag)->log('إنشاء وسم');

            return $tag;
        });
    }

    public function update(Tag $tag, array $data): Tag
    {
        return DB::transaction(function () use ($tag, $data) {
            if (isset($data['name']) && empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']).'-'.$tag->id;
            }

            $tag->update($data);
            activity()->performedOn($tag)->log('تحديث وسم');

            return $tag->fresh();
        });
    }

    public function delete(Tag $tag): void
    {
        DB::transaction(function () use ($tag) {
            activity()->performedOn($tag)->log('حذف وسم');
            $tag->delete();
        });
    }
}
