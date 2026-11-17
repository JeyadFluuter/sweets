<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSetting extends Model
{
    protected $fillable = [
        'is_enabled', 'provider', 'fallback_provider', 'base_url', 'model',
        'api_key_encrypted', 'temperature', 'max_tokens', 'daily_message_limit',
        'system_prompt', 'last_tested_at', 'last_test_status', 'last_error',
    ];

    protected $hidden = ['api_key_encrypted'];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'temperature' => 'float',
            'max_tokens' => 'integer',
            'daily_message_limit' => 'integer',
            'api_key_encrypted' => 'encrypted',
            'last_tested_at' => 'datetime',
        ];
    }

    public function maskedApiKey(): ?string
    {
        $key = $this->api_key_encrypted;
        if (! $key || strlen($key) < 8) {
            return $key ? '••••' : null;
        }

        return substr($key, 0, 3).'••••'.substr($key, -4);
    }
}
