<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Http\Request;

interface PaymentGateway
{
    public function initiate(Payment $payment): PaymentSession;

    public function verifyWebhook(Request $request): PaymentResult;
}
