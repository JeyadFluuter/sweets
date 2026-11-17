<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Collection */
class CollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isAdmin = str_contains($request->path(), 'admin/');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'is_active' => $this->is_active,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'image_url' => ($url = $this->getFirstMediaUrl('images')) !== '' ? $url : null,
            'images' => $this->getMedia('images')->map(fn ($media) => [
                'id' => $media->id,
                'url' => $media->getUrl(),
            ])->values(),
            // المحتويات للإدارة فقط — التطبيق يعرض الباقة كوحدة كاملة
            'items' => $this->when($isAdmin && $this->relationLoaded('items'), function () {
                return $this->items->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'product' => $item->relationLoaded('product') && $item->product
                        ? new ProductResource($item->product)
                        : null,
                ])->values();
            }),
        ];
    }
}
