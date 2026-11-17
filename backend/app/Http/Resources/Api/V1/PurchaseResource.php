<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Purchase */
class PurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'supplier' => $this->whenLoaded('supplier', fn () => [
                'id' => $this->supplier->id,
                'name' => $this->supplier->name,
            ]),
            'purchased_at' => $this->purchased_at?->toDateString(),
            'total' => $this->total,
            'status' => $this->status,
            'notes' => $this->notes,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'stock_item_id' => $item->stock_item_id,
                'stock_item' => $item->relationLoaded('stockItem') ? $item->stockItem?->name : null,
                'quantity' => $item->quantity,
                'unit_cost' => $item->unit_cost,
                'line_total' => $item->line_total,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
