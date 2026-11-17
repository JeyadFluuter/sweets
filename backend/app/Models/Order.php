<?php

namespace App\Models;

use App\Enums\DeliveryType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Order extends Model
{
    use LogsActivity;

    protected $fillable = [
        'number', 'customer_id', 'delivery_type', 'fulfillment_date', 'fulfillment_time',
        'address_text', 'lat', 'lng', 'delivery_fee', 'recipient_is_self', 'recipient_name',
        'recipient_phone', 'subtotal', 'discount', 'coupon_id', 'total', 'payment_plan',
        'paid_amount', 'remaining_amount', 'payment_status', 'payment_due_at', 'status',
        'capacity_reserved', 'capacity_confirmed', 'reviewed_by', 'reviewed_at',
        'rejection_reason_id', 'admin_note', 'customer_note', 'suggested_date', 'suggested_time',
    ];

    protected function casts(): array
    {
        return [
            'delivery_type' => DeliveryType::class,
            'fulfillment_date' => 'date',
            'delivery_fee' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'payment_plan' => 'integer',
            'payment_status' => PaymentStatus::class,
            'payment_due_at' => 'datetime',
            'status' => OrderStatus::class,
            'capacity_reserved' => 'boolean',
            'capacity_confirmed' => 'boolean',
            'recipient_is_self' => 'boolean',
            'reviewed_at' => 'datetime',
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'suggested_date' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function rejectionReason(): BelongsTo
    {
        return $this->belongsTo(RejectionReason::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(OrderStatusLog::class)->latest();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function maxLeadTimeHours(): int
    {
        return (int) $this->items()->max('lead_time_hours');
    }
}
