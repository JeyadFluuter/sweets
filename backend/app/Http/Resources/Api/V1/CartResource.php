<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var array $data */
        $data = $this->resource;

        return [
            'id' => $data['id'] ?? null,
            'items' => $data['items'] ?? [],
            'subtotal' => $data['subtotal'] ?? '0.00',
            'discount' => $data['discount'] ?? '0.00',
            'total' => $data['total'] ?? '0.00',
            'coupon' => $data['coupon'] ?? null,
            'currency' => $data['currency'] ?? 'LYD',
        ];
    }
}
