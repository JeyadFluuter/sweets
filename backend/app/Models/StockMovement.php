<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'stock_item_id', 'type', 'quantity', 'reference_type', 'reference_id', 'note', 'created_by',
    ];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3'];
    }

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class);
    }
}
