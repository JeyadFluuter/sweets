<?php

namespace App\Services\Catalog;

use App\Models\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CollectionService
{
    public function listVisible(int $limit = 12): SupportCollection
    {
        return Collection::query()
            ->visible()
            ->with(['items.product', 'media'])
            ->orderBy('sort_order')
            ->limit($limit)
            ->get();
    }

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Collection::query()
            ->with(['items.product', 'media'])
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('sort_order')
            ->paginate($perPage);
    }

    public function create(array $data): Collection
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $data['slug'] = $data['slug'] ?? Str::slug($data['name']).'-'.Str::random(4);
            if (($data['slug'] ?? '') === '' || str_starts_with($data['slug'] ?? '', '-')) {
                $data['slug'] = 'collection-'.Str::random(8);
            }

            $collection = Collection::query()->create($data);
            $this->syncItems($collection, $items);

            activity()->performedOn($collection)->log('إنشاء باقة');

            return $collection->load(['items.product', 'media']);
        });
    }

    public function update(Collection $collection, array $data): Collection
    {
        return DB::transaction(function () use ($collection, $data) {
            $items = $data['items'] ?? null;
            unset($data['items']);

            if (isset($data['name']) && empty($data['slug'])) {
                $slug = Str::slug($data['name']);
                $data['slug'] = ($slug !== '' ? $slug : 'collection').'-'.$collection->id;
            }

            $collection->update($data);

            if (is_array($items)) {
                $this->syncItems($collection, $items);
            }

            activity()->performedOn($collection)->log('تحديث باقة');

            return $collection->fresh(['items.product', 'media']);
        });
    }

    public function delete(Collection $collection): void
    {
        DB::transaction(function () use ($collection) {
            activity()->performedOn($collection)->log('حذف باقة');
            $collection->delete();
        });
    }

    /**
     * @param  list<array{product_id:int,quantity:int}>  $items
     */
    protected function syncItems(Collection $collection, array $items): void
    {
        $collection->items()->delete();

        foreach ($items as $item) {
            $collection->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'] ?? 1,
            ]);
        }
    }
}
