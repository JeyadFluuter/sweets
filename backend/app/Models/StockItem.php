<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockItem extends Model
{
    protected $fillable = ['name', 'unit', 'quantity', 'min_quantity', 'is_active'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'min_quantity' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isLow(): bool
    {
        return (float) $this->quantity <= (float) $this->min_quantity;
    }
}
