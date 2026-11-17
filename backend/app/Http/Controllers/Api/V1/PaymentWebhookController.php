<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService) {}

    public function __invoke(Request $request): JsonResponse
    {
        $result = $this->paymentService->handleWebhook($request);

        return ApiResponse::success([
            'success' => $result->success,
            'reference' => $result->reference,
            'status' => $result->status,
        ], $result->success ? 'تم التحقق من الدفعة' : 'لم يتم قبول الـ webhook');
    }
}
