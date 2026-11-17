<?php

namespace App\Filament\Resources\SettingResource\Pages;

use App\Filament\Resources\SettingResource;
use App\Models\Setting;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSettings extends ManageRecords
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة إعداد')
                ->modalHeading('إعداد جديد')
                ->modalWidth('2xl')
                ->mutateFormDataUsing(fn (array $data) => $this->normalizeSettingData($data))
                ->after(fn () => Setting::forgetCache()),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeSettingData(array $data): array
    {
        if (($data['type'] ?? null) === 'boolean') {
            $data['value'] = ! empty($data['value_bool']) ? '1' : '0';
        }
        if (($data['type'] ?? null) === 'json' && array_key_exists('value_json', $data)) {
            $data['value'] = $data['value_json'];
        }
        unset($data['value_bool'], $data['value_json'], $data['key_ar']);

        return $data;
    }
}
