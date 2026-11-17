<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationLog extends Model
{
    protected $fillable = [
        'channel', 'type', 'notifiable_type', 'notifiable_id', 'order_id',
        'idempotency_key', 'status', 'error', 'payload',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
