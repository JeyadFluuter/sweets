<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Cart\StoreCartItemRequest;
use App\Http\Requests\Api\V1\Cart\UpdateCartItemRequest;
use App\Http\Resources\Api\V1\CartResource;
use App\Http\Responses\ApiResponse;
use App\Models\CartItem;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cartService) {}

    public function show(Request $request): JsonResponse
    {
        $cart = $this->cartService->getOrCreate($request->user());

        return ApiResponse::success(new CartResource($this->cartService->summary($cart)));
    }

    public function storeItem(StoreCartItemRequest $request): JsonResponse
    {
        $data = $request->validated();
        $cart = $this->cartService->getOrCreate($request->user());
        $cart = $this->cartService->addItem($cart, $data['item_type'], (int) $data['item_id'], (int) ($data['quantity'] ?? 1));

        return ApiResponse::success(new CartResource($this->cartService->summary($cart)), 'تمت إضافة العنصر للسلة', 201);
    }

    public function updateItem(UpdateCartItemRequest $request, CartItem $item): JsonResponse
    {
        abort_unless($item->cart->customer_id === $request->user()->id, 403);
        $cart = $this->cartService->updateItem($item, (int) $request->validated('quantity'));

        return ApiResponse::success(new CartResource($this->cartService->summary($cart)), 'تم تحديث السلة');
    }

    public function destroyItem(Request $request, CartItem $item): JsonResponse
    {
        abort_unless($item->cart->customer_id === $request->user()->id, 403);
        $cart = $this->cartService->removeItem($item);

        return ApiResponse::success(new CartResource($this->cartService->summary($cart)), 'تم حذف العنصر');
    }

    public function destroy(Request $request): JsonResponse
    {
        $cart = $this->cartService->getOrCreate($request->user());
        $this->cartService->clear($cart);
        $cart->refresh();
        $cart->unsetRelation('items');
        $cart->unsetRelation('coupon');

        return ApiResponse::success(
            new CartResource($this->cartService->summary($cart)),
            'تم تفريغ السلة'
        );
    }

    public function applyCoupon(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:50']]);
        $cart = $this->cartService->getOrCreate($request->user());
        $cart = $this->cartService->applyCoupon($cart, (string) $request->input('code'));

        return ApiResponse::success(new CartResource($this->cartService->summary($cart)), 'تم تطبيق الكوبون');
    }

    public function removeCoupon(Request $request): JsonResponse
    {
        $cart = $this->cartService->getOrCreate($request->user());
        $cart = $this->cartService->removeCoupon($cart);

        return ApiResponse::success(new CartResource($this->cartService->summary($cart)), 'تم إزالة الكوبون');
    }
}
