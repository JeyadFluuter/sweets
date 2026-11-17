<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = ['cart_id', 'item_type', 'item_id', 'quantity'];

    protected function casts(): array
    {
        return [
            'item_id' => 'integer',
            'quantity' => 'integer',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function resolveItem(): ?Model
    {
        return match ($this->item_type) {
            'product' => Product::query()->find($this->item_id),
            'collection' => Collection::query()->find($this->item_id),
            default => null,
        };
    }
}
