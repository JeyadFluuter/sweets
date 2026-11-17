<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CollectionResource;
use App\Http\Responses\ApiResponse;
use App\Models\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $limit = min(100, max(1, $request->integer('limit', 50)));

        $collections = Collection::query()
            ->visible()
            ->with(['items.product.media', 'items.product.tags', 'media'])
            ->orderBy('sort_order')
            ->limit($limit)
            ->get();

        return ApiResponse::success(CollectionResource::collection($collections));
    }

    public function show(Collection $collection): JsonResponse
    {
        abort_unless($collection->is_active, 404);

        $now = now();
        if ($collection->starts_at && $collection->starts_at->isFuture()) {
            abort(404);
        }
        if ($collection->ends_at && $collection->ends_at->isPast()) {
            abort(404);
        }

        $collection->load(['items.product.category', 'items.product.tags', 'items.product.media', 'media']);

        return ApiResponse::success(new CollectionResource($collection));
    }
}
