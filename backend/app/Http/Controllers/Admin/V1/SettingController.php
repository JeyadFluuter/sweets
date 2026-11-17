<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\Setting\UpdateSettingsRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Settings\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(private readonly SettingService $settingService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Setting::class);

        $includeSensitive = $request->user()->can('settings.sensitive');

        return ApiResponse::success(
            $this->settingService->all($request->string('group')->toString() ?: null, $includeSensitive)
        );
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $this->authorize('update', \App\Models\Setting::class);

        $settings = $this->settingService->upsertMany($request->validated('settings'));

        return ApiResponse::success($settings, __('messages.settings.updated'));
    }
}
