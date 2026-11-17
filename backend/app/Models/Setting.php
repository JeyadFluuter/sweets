<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'is_sensitive',
    ];

    protected function casts(): array
    {
        return [
            'is_sensitive' => 'boolean',
        ];
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $settings = Cache::rememberForever('app.settings.map', function () {
            return static::query()
                ->get(['key', 'value', 'type'])
                ->keyBy('key')
                ->map(fn (self $setting) => [
                    'value' => $setting->value,
                    'type' => $setting->type,
                ])
                ->all();
        });

        if (! array_key_exists($key, $settings)) {
            return $default;
        }

        return static::castValue($settings[$key]['value'], $settings[$key]['type'] ?? 'string');
    }

    public static function setValue(string $key, mixed $value, string $group = 'general', string $type = 'string', bool $isSensitive = false): self
    {
        $setting = static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) || is_object($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value,
                'group' => $group,
                'type' => $type,
                'is_sensitive' => $isSensitive,
            ]
        );

        static::forgetCache();

        return $setting;
    }

    public static function forgetCache(): void
    {
        Cache::forget('app.settings.map');
        Cache::forget('app.settings');
    }

    public function typedValue(): mixed
    {
        return static::castValue($this->value, $this->type);
    }

    protected static function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer', 'int' => (int) $value,
            'float', 'decimal' => (float) $value,
            'json', 'array' => json_decode((string) $value, true),
            default => $value,
        };
    }
}
