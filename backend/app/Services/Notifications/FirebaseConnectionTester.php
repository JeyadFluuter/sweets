<?php

namespace App\Services\Notifications;

class FirebaseConnectionTester
{
    public function __construct(private readonly FirebaseCredentialsProvider $credentials) {}

    /**
     * @return array{ok:bool,steps:list<array{name:string,ok:bool,message:string}>,status:string}
     */
    public function test(?string $deviceToken = null): array
    {
        $steps = [];
        $path = $this->credentials->path();

        $steps[] = $this->step('وجود الملف', (bool) $path, $path ? 'الملف موجود' : 'لا يوجد ملف اعتماد');
        if (! $path) {
            return $this->result(false, $steps);
        }

        $raw = @file_get_contents($path);
        $json = $raw ? json_decode($raw, true) : null;
        $valid = is_array($json)
            && ($json['type'] ?? null) === 'service_account'
            && ! empty($json['project_id'])
            && ! empty($json['private_key'])
            && ! empty($json['client_email']);
        $steps[] = $this->step('بنية JSON', $valid, $valid ? 'البنية صحيحة' : 'بنية الملف غير صالحة');

        if (! $valid) {
            return $this->result(false, $steps);
        }

        // بدون kreait مثبتاً نكتفي بالتحقق المحلي، ونُبقي خطوة الشبكة اختيارية
        $hasKreait = class_exists(\Kreait\Firebase\Factory::class);
        if (! $hasKreait) {
            $steps[] = $this->step('حزمة Firebase', false, 'ثبّت kreait/laravel-firebase لتفعيل اختبار الاتصال الكامل');
            $steps[] = $this->step('Access Token', false, 'يتطلب حزمة Firebase');
            $steps[] = $this->step('validate_only', false, 'يتطلب حزمة Firebase');

            return $this->result(false, $steps, 'failed');
        }

        try {
            $factory = (new \Kreait\Firebase\Factory)->withServiceAccount($path);
            $auth = $factory->createAuth();
            // مجرد إنشاء العميل يثبت قراءة المفتاح
            $steps[] = $this->step('Access Token', true, 'تم بناء عميل Firebase بنجاح');
            $messaging = $factory->createMessaging();
            $message = [
                'topic' => 'connection_test_unused',
                'notification' => ['title' => 'test', 'body' => 'test'],
            ];
            try {
                $messaging->send($message, true);
                $steps[] = $this->step('validate_only', true, 'صلاحيات المراسلة سليمة');
            } catch (\Throwable $e) {
                // validate_only على topic قد يفشل لأسباب صلاحية - نسجل الرسالة
                $steps[] = $this->step('validate_only', false, $e->getMessage());
            }

            if ($deviceToken) {
                try {
                    $messaging->send([
                        'token' => $deviceToken,
                        'notification' => [
                            'title' => 'تجربة الاتصال',
                            'body' => 'تم اختبار اتصال Firebase بنجاح',
                        ],
                    ]);
                    $steps[] = $this->step('إشعار تجريبي', true, 'تم الإرسال للجهاز');
                } catch (\Throwable $e) {
                    $steps[] = $this->step('إشعار تجريبي', false, $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            $steps[] = $this->step('Access Token', false, $e->getMessage());

            return $this->result(false, $steps);
        }

        $ok = collect($steps)->every(fn ($s) => $s['ok'] || str_contains($s['name'], 'إشعار'));

        return $this->result($ok, $steps);
    }

    protected function step(string $name, bool $ok, string $message): array
    {
        return compact('name', 'ok', 'message');
    }

    protected function result(bool $ok, array $steps, ?string $status = null): array
    {
        $status ??= $ok ? 'connected' : 'failed';
        \App\Models\IntegrationSetting::put('firebase_last_test', json_encode([
            'status' => $status,
            'tested_at' => now()->toIso8601String(),
            'steps' => $steps,
        ], JSON_UNESCAPED_UNICODE));

        return ['ok' => $ok, 'steps' => $steps, 'status' => $status];
    }
}
