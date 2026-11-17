<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory, InteractsWithMedia, LogsActivity;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'discount_price',
        'discount_starts_at',
        'discount_ends_at',
        'category_id',
        'is_available',
        'min_order_qty',
        'max_order_qty',
        'ingredients',
        'allergen_alerts',
        'ai_suggestable',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'discount_starts_at' => 'datetime',
            'discount_ends_at' => 'datetime',
            'is_available' => 'boolean',
            'min_order_qty' => 'integer',
            'max_order_qty' => 'integer',
            'ai_suggestable' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->useDisk('public')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeOnDiscount($query)
    {
        $now = now();

        return $query
            ->whereNotNull('discount_price')
            ->where(function ($q) use ($now) {
                $q->whereNull('discount_starts_at')->orWhere('discount_starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('discount_ends_at')->orWhere('discount_ends_at', '>=', $now);
            });
    }

    public function hasActiveDiscount(): bool
    {
        if ($this->discount_price === null) {
            return false;
        }

        $now = now();

        if ($this->discount_starts_at && $this->discount_starts_at->isFuture()) {
            return false;
        }

        if ($this->discount_ends_at && $this->discount_ends_at->isPast()) {
            return false;
        }

        return true;
    }

    public function effectivePrice(): string
    {
        return $this->hasActiveDiscount()
            ? (string) $this->discount_price
            : (string) $this->price;
    }

    public function minOrderQty(): int
    {
        return max(1, (int) ($this->min_order_qty ?: 1));
    }

    public function maxOrderQty(): int
    {
        $max = (int) ($this->max_order_qty ?: 99);

        return max($this->minOrderQty(), $max);
    }

    public function clampOrderQty(int $quantity): int
    {
        return max($this->minOrderQty(), min($this->maxOrderQty(), $quantity));
    }
}
