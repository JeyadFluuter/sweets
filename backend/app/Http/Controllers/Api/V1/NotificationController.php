<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AppNotificationResource;
use App\Http\Responses\ApiResponse;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customerId = $request->user()->id;

        $items = AppNotification::query()
            ->where('customer_id', $customerId)
            ->latest()
            ->paginate($request->integer('per_page', 20));

        $payload = AppNotificationResource::collection($items)->response()->getData(true);
        if (is_array($payload)) {
            $payload['unread_count'] = AppNotification::query()
                ->where('customer_id', $customerId)
                ->whereNull('read_at')
                ->count();
        }

        return ApiResponse::success($payload);
    }

    public function read(Request $request, AppNotification $notification): JsonResponse
    {
        abort_unless($notification->customer_id === $request->user()->id, 403);
        $notification->update(['read_at' => now()]);

        return ApiResponse::success(new AppNotificationResource($notification), 'تم تعليم الإشعار كمقروء');
    }

    public function destroy(Request $request, AppNotification $notification): JsonResponse
    {
        abort_unless($notification->customer_id === $request->user()->id, 403);
        $notification->delete();

        return ApiResponse::success(null, 'تم حذف الإشعار');
    }
}
