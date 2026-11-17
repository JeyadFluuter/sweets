<?php

namespace App\Services\Customers;

use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Customer::query()
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(isset($filters['is_blocked']), fn ($q) => $q->where('is_blocked', $filters['is_blocked']))
            ->when(isset($filters['is_guest']), fn ($q) => $q->where('is_guest', $filters['is_guest']))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function setBlocked(Customer $customer, bool $blocked, ?string $note = null): Customer
    {
        return DB::transaction(function () use ($customer, $blocked, $note) {
            $customer->update([
                'is_blocked' => $blocked,
                'internal_notes' => $note ?? $customer->internal_notes,
            ]);

            activity()
                ->performedOn($customer)
                ->log($blocked ? 'حظر عميل' : 'فك حظر عميل');

            return $customer->fresh();
        });
    }

    public function updateNotes(Customer $customer, string $notes): Customer
    {
        $customer->update(['internal_notes' => $notes]);
        activity()->performedOn($customer)->log('تحديث ملاحظات العميل');

        return $customer->fresh();
    }
}
