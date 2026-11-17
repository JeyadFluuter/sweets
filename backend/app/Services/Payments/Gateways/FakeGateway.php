<?php

namespace App\Services\Payments\Gateways;

use App\Models\Payment;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaymentResult;
use App\Services\Payments\PaymentSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FakeGateway implements PaymentGateway
{
    public function initiate(Payment $payment): PaymentSession
    {
        return new PaymentSession(
            reference: $payment->reference ?: 'FAKE-'.Str::upper(Str::random(10)),
            status: 'paid',
            meta: ['driver' => 'fake', 'auto_confirm' => true],
        );
    }

    public function verifyWebhook(Request $request): PaymentResult
    {
        return new PaymentResult(
            success: true,
            reference: (string) $request->input('reference'),
            status: 'paid',
            idempotencyKey: $request->input('idempotency_key') ?: 'fake-'.$request->input('reference'),
            meta: ['driver' => 'fake'],
        );
    }
}
