<?php

namespace App\Services\Notifications;

use App\Models\Customer;
use App\Models\DeviceToken;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Log;

class NotificationDispatcher
{
    public function __construct(private readonly FirebaseCredentialsProvider $credentials) {}

    public function sendToCustomer(
        Customer $customer,
        string $title,
        string $body,
        array $data = [],
        ?string $idempotencyKey = null,
        ?int $orderId = null
    ): void {
        if ($idempotencyKey && NotificationLog::query()->where('idempotency_key', $idempotencyKey)->exists()) {
            return;
        }

        $tokens = DeviceToken::query()->where('customer_id', $customer->id)->pluck('token');
        if ($tokens->isEmpty() || ! class_exists(\Kreait\Firebase\Factory::class) || ! $this->credentials->path()) {
            NotificationLog::query()->create([
                'channel' => 'fcm',
                'type' => $data['type'] ?? 'push',
                'notifiable_type' => $customer::class,
                'notifiable_id' => $customer->id,
                'order_id' => $orderId,
                'idempotency_key' => $idempotencyKey,
                'status' => 'skipped',
                'error' => 'لا يوجد توكن جهاز أو اعتماد Firebase',
                'payload' => compact('title', 'body', 'data'),
            ]);

            return;
        }

        try {
            $messaging = (new \Kreait\Firebase\Factory)
                ->withServiceAccount($this->credentials->path())
                ->createMessaging();

            foreach ($tokens as $token) {
                try {
                    $messaging->send([
                        'token' => $token,
                        'notification' => compact('title', 'body'),
                        'data' => array_map('strval', $data),
                    ]);
                } catch (\Throwable $e) {
                    if (str_contains($e->getMessage(), 'not-found') || str_contains($e->getMessage(), 'UNREGISTERED')) {
                        DeviceToken::query()->where('token', $token)->delete();
                    }
                    Log::warning('FCM send failed', ['error' => $e->getMessage()]);
                }
            }

            NotificationLog::query()->create([
                'channel' => 'fcm',
                'type' => $data['type'] ?? 'push',
                'notifiable_type' => $customer::class,
                'notifiable_id' => $customer->id,
                'order_id' => $orderId,
                'idempotency_key' => $idempotencyKey,
                'status' => 'sent',
                'payload' => compact('title', 'body', 'data'),
            ]);
        } catch (\Throwable $e) {
            NotificationLog::query()->create([
                'channel' => 'fcm',
                'type' => $data['type'] ?? 'push',
                'notifiable_type' => $customer::class,
                'notifiable_id' => $customer->id,
                'order_id' => $orderId,
                'idempotency_key' => $idempotencyKey,
                'status' => 'failed',
                'error' => $e->getMessage(),
                'payload' => compact('title', 'body', 'data'),
            ]);
        }
    }
}
