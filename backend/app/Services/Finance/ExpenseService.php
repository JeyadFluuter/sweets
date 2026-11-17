<?php

namespace App\Services\Finance;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function __construct(private readonly AccountingService $accountingService) {}

    public function create(array $data, ?User $user = null): Expense
    {
        return DB::transaction(function () use ($data, $user) {
            $expense = Expense::query()->create([
                ...$data,
                'recorded_by' => $data['recorded_by'] ?? $user?->id,
            ]);

            $this->accountingService->postExpense($expense, $user);

            return $expense->load('category');
        });
    }
}
