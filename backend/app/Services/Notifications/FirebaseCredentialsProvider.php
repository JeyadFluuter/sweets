<?php

namespace App\Services\Notifications;

use App\Models\IntegrationSetting;
use Illuminate\Support\Facades\Storage;

class FirebaseCredentialsProvider
{
    public function path(): ?string
    {
        $uploaded = IntegrationSetting::query()->where('key', 'firebase_credentials_path')->value('value');
        if ($uploaded && is_file($uploaded)) {
            return $uploaded;
        }

        $env = config('services.firebase.credentials') ?: env('FIREBASE_CREDENTIALS');
        if ($env && is_file($env)) {
            return $env;
        }

        return null;
    }

    public function meta(): array
    {
        $row = IntegrationSetting::query()->where('key', 'firebase_credentials_path')->first();

        return $row?->meta ?? [];
    }

    public function storeUploadedJson(array $json, string $originalName = 'service-account.json'): array
    {
        foreach (['type', 'project_id', 'private_key', 'client_email'] as $field) {
            if (empty($json[$field])) {
                throw new \InvalidArgumentException("ملف الاعتماد ناقص الحقل: {$field}");
            }
        }

        if (($json['type'] ?? null) !== 'service_account') {
            throw new \InvalidArgumentException('الملف يجب أن يكون من نوع service_account.');
        }

        $disk = Storage::disk('local');
        $old = $this->path();
        if ($old && str_contains($old, 'storage/app') && is_file($old)) {
            @unlink($old);
        }

        $filename = 'private/firebase/'.bin2hex(random_bytes(16)).'.json';
        $disk->put($filename, json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $full = storage_path('app/'.$filename);
        @chmod($full, 0600);

        IntegrationSetting::put('firebase_credentials_path', $full, false, [
            'project_id' => $json['project_id'],
            'client_email' => $json['client_email'],
            'uploaded_at' => now()->toIso8601String(),
            'original_name' => $originalName,
        ]);

        activity()->log('رفع اعتماد Firebase');

        return $this->meta();
    }
}
