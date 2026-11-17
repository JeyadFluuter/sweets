<?php

namespace App\Services\Settings;

use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SettingService
{
    public function all(?string $group = null, bool $includeSensitive = false): Collection
    {
        return Setting::query()
            ->when($group, fn ($q) => $q->where('group', $group))
            ->when(! $includeSensitive, fn ($q) => $q->where('is_sensitive', false))
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->map(function (Setting $setting) {
                return [
                    'key' => $setting->key,
                    'value' => $setting->is_sensitive ? null : $setting->typedValue(),
                    'group' => $setting->group,
                    'type' => $setting->type,
                    'is_sensitive' => $setting->is_sensitive,
                ];
            });
    }

    /**
     * @param  array<string, mixed>  $items
     */
    public function upsertMany(array $items): Collection
    {
        return DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                Setting::setValue(
                    $item['key'],
                    $item['value'],
                    $item['group'] ?? 'general',
                    $item['type'] ?? 'string',
                    (bool) ($item['is_sensitive'] ?? false),
                );
            }

            activity()->log('تحديث إعدادات النظام');

            Setting::forgetCache();

            return $this->all();
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Setting::getValue($key, $default);
    }
}
