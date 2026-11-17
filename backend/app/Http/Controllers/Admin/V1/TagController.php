<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\Tag\StoreTagRequest;
use App\Http\Requests\Admin\V1\Tag\UpdateTagRequest;
use App\Http\Resources\Api\V1\TagResource;
use App\Http\Responses\ApiResponse;
use App\Models\Tag;
use App\Services\Catalog\TagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function __construct(private readonly TagService $tagService)
    {
        $this->authorizeResource(Tag::class, 'tag');
    }

    public function index(Request $request): JsonResponse
    {
        $tags = $this->tagService->list(
            $request->only(['search', 'type', 'is_active']),
            $request->integer('per_page', 50)
        );

        return ApiResponse::success(TagResource::collection($tags)->response()->getData(true));
    }

    public function store(StoreTagRequest $request): JsonResponse
    {
        $tag = $this->tagService->create($request->validated());

        return ApiResponse::success(new TagResource($tag), __('messages.tags.created'), 201);
    }

    public function show(Tag $tag): JsonResponse
    {
        return ApiResponse::success(new TagResource($tag));
    }

    public function update(UpdateTagRequest $request, Tag $tag): JsonResponse
    {
        $tag = $this->tagService->update($tag, $request->validated());

        return ApiResponse::success(new TagResource($tag), __('messages.tags.updated'));
    }

    public function destroy(Tag $tag): JsonResponse
    {
        $this->tagService->delete($tag);

        return ApiResponse::success(null, __('messages.tags.deleted'));
    }
}
