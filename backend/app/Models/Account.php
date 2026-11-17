<?php

namespace App\Models;

use App\Enums\AccountType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = ['code', 'name', 'type', 'is_active', 'description'];

    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'is_active' => 'boolean',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function balance(): float
    {
        $debit = (float) $this->lines()->sum('debit');
        $credit = (float) $this->lines()->sum('credit');

        return match ($this->type) {
            AccountType::Asset, AccountType::Expense => $debit - $credit,
            default => $credit - $debit,
        };
    }

    public static function byCode(string $code): self
    {
        return static::query()->where('code', $code)->where('is_active', true)->firstOrFail();
    }
}
