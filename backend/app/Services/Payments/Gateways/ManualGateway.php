<?php

namespace App\Services\Payments\Gateways;

use App\Models\Payment;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaymentResult;
use App\Services\Payments\PaymentSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ManualGateway implements PaymentGateway
{
    public function initiate(Payment $payment): PaymentSession
    {
        return new PaymentSession(
            reference: $payment->reference ?: 'MAN-'.Str::upper(Str::random(10)),
            status: 'pending',
            meta: ['driver' => 'manual'],
        );
    }

    public function verifyWebhook(Request $request): PaymentResult
    {
        return new PaymentResult(
            success: false,
            reference: (string) $request->input('reference', ''),
            status: 'ignored',
            idempotencyKey: $request->input('idempotency_key'),
            meta: ['message' => 'البوابة اليدوية لا تستقبل webhooks'],
        );
    }
}
