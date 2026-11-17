<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Product */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'discount_price' => $this->discount_price,
            'effective_price' => $this->effectivePrice(),
            'has_active_discount' => $this->hasActiveDiscount(),
            'discount_starts_at' => $this->discount_starts_at?->toIso8601String(),
            'discount_ends_at' => $this->discount_ends_at?->toIso8601String(),
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'is_available' => $this->is_available,
            'min_order_qty' => $this->minOrderQty(),
            'max_order_qty' => $this->maxOrderQty(),
            'ingredients' => $this->ingredients,
            'allergen_alerts' => $this->allergen_alerts,
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'images' => $this->getMedia('images')->map(fn ($media) => [
                'id' => $media->id,
                'url' => $media->getUrl(),
            ])->values(),
            'image_url' => ($url = $this->getFirstMediaUrl('images')) !== '' ? $url : null,
        ];
    }
}
