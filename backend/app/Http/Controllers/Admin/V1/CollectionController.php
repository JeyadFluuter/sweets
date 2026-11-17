<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\Collection\StoreCollectionRequest;
use App\Http\Requests\Admin\V1\Collection\UpdateCollectionRequest;
use App\Http\Resources\Api\V1\CollectionResource;
use App\Http\Responses\ApiResponse;
use App\Models\Collection;
use App\Services\Catalog\CollectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function __construct(private readonly CollectionService $collectionService)
    {
        $this->authorizeResource(Collection::class, 'collection');
    }

    public function index(Request $request): JsonResponse
    {
        $collections = $this->collectionService->list(
            $request->only(['search', 'is_active']),
            $request->integer('per_page', 15)
        );

        return ApiResponse::success(CollectionResource::collection($collections)->response()->getData(true));
    }

    public function store(StoreCollectionRequest $request): JsonResponse
    {
        $collection = $this->collectionService->create($request->validated());

        return ApiResponse::success(new CollectionResource($collection), __('messages.collections.created'), 201);
    }

    public function show(Collection $collection): JsonResponse
    {
        return ApiResponse::success(new CollectionResource($collection->load(['items.product', 'media'])));
    }

    public function update(UpdateCollectionRequest $request, Collection $collection): JsonResponse
    {
        $collection = $this->collectionService->update($collection, $request->validated());

        return ApiResponse::success(new CollectionResource($collection), __('messages.collections.updated'));
    }

    public function destroy(Collection $collection): JsonResponse
    {
        $this->collectionService->delete($collection);

        return ApiResponse::success(null, __('messages.collections.deleted'));
    }
}
