<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\Customer\BlockCustomerRequest;
use App\Http\Resources\Admin\V1\CustomerResource;
use App\Http\Responses\ApiResponse;
use App\Models\Customer;
use App\Services\Customers\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(private readonly CustomerService $customerService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $customers = $this->customerService->list(
            $request->only(['search', 'is_blocked', 'is_guest']),
            $request->integer('per_page', 15)
        );

        return ApiResponse::success(CustomerResource::collection($customers)->response()->getData(true));
    }

    public function show(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        return ApiResponse::success(new CustomerResource($customer));
    }

    public function block(BlockCustomerRequest $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $customer = $this->customerService->setBlocked(
            $customer,
            (bool) $request->validated('is_blocked'),
            $request->validated('internal_notes')
        );

        $message = $customer->is_blocked
            ? __('messages.customers.blocked')
            : __('messages.customers.unblocked');

        return ApiResponse::success(new CustomerResource($customer), $message);
    }
}
