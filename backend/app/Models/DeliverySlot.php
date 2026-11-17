<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliverySlot extends Model
{
    protected $fillable = ['start_time', 'end_time', 'max_orders', 'type', 'is_active'];

    protected function casts(): array
    {
        return [
            'max_orders' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
