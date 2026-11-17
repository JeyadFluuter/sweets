<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Ai\ChatRequest;
use App\Http\Resources\Api\V1\AiConversationResource;
use App\Http\Responses\ApiResponse;
use App\Models\AiConversation;
use App\Services\Ai\AssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function __construct(
        private readonly AssistantService $assistantService,
        private readonly \App\Services\Ai\AiManager $aiManager,
    ) {}

    public function status(): JsonResponse
    {
        $settings = $this->aiManager->settings();
        $enabled = (bool) $settings->is_enabled;
        $connected = $settings->last_test_status === 'connected';

        return ApiResponse::success([
            // يظهر/يختفي تبويب المساعد في التطبيق حسب هذا الحقل
            'enabled' => $enabled,
            'connected' => $connected,
            'ready' => $enabled && $connected,
            'provider' => $settings->provider,
            'daily_message_limit' => (int) $settings->daily_message_limit,
        ]);
    }

    public function chat(ChatRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->assistantService->chat(
            $request->user(),
            $data['message'],
            $data['conversation_id'] ?? null
        );

        return ApiResponse::success($result);
    }

    public function conversations(Request $request): JsonResponse
    {
        $items = AiConversation::query()
            ->where('customer_id', $request->user()->id)
            ->with(['messages' => fn ($q) => $q->latest()->limit(20)])
            ->latest()
            ->paginate(20);

        return ApiResponse::success(AiConversationResource::collection($items)->response()->getData(true));
    }
}
