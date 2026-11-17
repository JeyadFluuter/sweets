<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Device\DestroyDeviceRequest;
use App\Http\Requests\Api\V1\Device\StoreDeviceRequest;
use App\Http\Responses\ApiResponse;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;

class DeviceController extends Controller
{
    public function store(StoreDeviceRequest $request): JsonResponse
    {
        $data = $request->validated();

        DeviceToken::query()->updateOrCreate(
            ['token' => $data['token']],
            [
                'customer_id' => $request->user()->id,
                'platform' => $data['platform'] ?? null,
                'last_used_at' => now(),
            ]
        );

        return ApiResponse::success(null, 'تم تسجيل الجهاز', 201);
    }

    public function destroy(DestroyDeviceRequest $request): JsonResponse
    {
        $data = $request->validated();
        DeviceToken::query()
            ->where('customer_id', $request->user()->id)
            ->where('token', $data['token'])
            ->delete();

        return ApiResponse::success(null, 'تم حذف توكن الجهاز');
    }
}
