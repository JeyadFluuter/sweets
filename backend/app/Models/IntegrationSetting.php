<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class IntegrationSetting extends Model
{
    protected $fillable = ['key', 'value', 'is_encrypted', 'meta'];

    protected $hidden = ['value'];

    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
            'meta' => 'array',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::query()->where('key', $key)->first();
        if (! $row) {
            return $default;
        }

        if ($row->is_encrypted && $row->value) {
            try {
                return Crypt::decryptString($row->value);
            } catch (\Throwable) {
                return $default;
            }
        }

        return $row->value ?? $default;
    }

    public static function put(string $key, mixed $value, bool $encrypt = false, array $meta = []): self
    {
        return static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $encrypt ? Crypt::encryptString((string) $value) : $value,
                'is_encrypted' => $encrypt,
                'meta' => $meta ?: null,
            ]
        );
    }
}
