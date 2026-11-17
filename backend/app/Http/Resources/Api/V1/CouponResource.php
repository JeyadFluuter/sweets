<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Coupon */
class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'type' => $this->type?->value,
            'value' => $this->value,
            'max_discount' => $this->max_discount,
            'min_order_amount' => $this->min_order_amount,
            'usage_limit' => $this->usage_limit,
            'usage_limit_per_customer' => $this->usage_limit_per_customer,
            'used_count' => $this->used_count,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'scope' => $this->scope?->value,
            'is_active' => $this->is_active,
            'category_ids' => $this->whenLoaded('categories', fn () => $this->categories->pluck('id')->values()),
            'product_ids' => $this->whenLoaded('products', fn () => $this->products->pluck('id')->values()),
        ];
    }
}
