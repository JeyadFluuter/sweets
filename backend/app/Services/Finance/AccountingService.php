<?php

namespace App\Services\Finance;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountingService
{
    /**
     * @param  list<array{account_code:string,debit?:float|int|string,credit?:float|int|string,memo?:string|null}>  $lines
     */
    public function post(
        string $memo,
        array $lines,
        string $entryDate,
        ?string $sourceKey = null,
        ?ModelSource $source = null,
        ?User $user = null,
    ): JournalEntry {
        if ($sourceKey && JournalEntry::query()->where('source_key', $sourceKey)->exists()) {
            return JournalEntry::query()->where('source_key', $sourceKey)->with('lines.account')->firstOrFail();
        }

        $normalized = [];
        $debitTotal = 0.0;
        $creditTotal = 0.0;

        foreach ($lines as $line) {
            $debit = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);
            if ($debit <= 0 && $credit <= 0) {
                continue;
            }
            if ($debit > 0 && $credit > 0) {
                throw ValidationException::withMessages(['lines' => ['لا يمكن أن يحتوي السطر على مدين ودائن معاً.']]);
            }

            $account = Account::byCode($line['account_code']);
            $normalized[] = [
                'account_id' => $account->id,
                'debit' => $debit,
                'credit' => $credit,
                'memo' => $line['memo'] ?? null,
            ];
            $debitTotal += $debit;
            $creditTotal += $credit;
        }

        if ($normalized === []) {
            throw ValidationException::withMessages(['lines' => ['القيد فارغ.']]);
        }

        if (round($debitTotal, 2) !== round($creditTotal, 2)) {
            throw ValidationException::withMessages([
                'lines' => ["القيد غير متوازن: مدين {$debitTotal} ≠ دائن {$creditTotal}"],
            ]);
        }

        return DB::transaction(function () use ($memo, $normalized, $entryDate, $sourceKey, $source, $user) {
            $entry = JournalEntry::query()->create([
                'number' => $this->nextNumber(),
                'entry_date' => $entryDate,
                'memo' => $memo,
                'source_type' => $source?->type,
                'source_id' => $source?->id,
                'source_key' => $sourceKey,
                'created_by' => $user?->id,
            ]);

            foreach ($normalized as $line) {
                $entry->lines()->create($line);
            }

            return $entry->load('lines.account');
        });
    }

    public function postPayment(Payment $payment, ?User $user = null): ?JournalEntry
    {
        if (! $this->ready() || $payment->status !== 'paid' || (float) $payment->amount <= 0) {
            return null;
        }

        $cashCode = match ($payment->method) {
            'transfer', 'online' => '1100',
            default => '1000',
        };

        $payment->loadMissing('order');

        return $this->post(
            memo: 'تحصيل دفعة '.$payment->reference.' للطلب #'.($payment->order?->number ?? $payment->order_id),
            lines: [
                ['account_code' => $cashCode, 'debit' => $payment->amount, 'memo' => 'تحصيل'],
                ['account_code' => '1200', 'credit' => $payment->amount, 'memo' => 'تخفيض ذمم مدينة'],
            ],
            entryDate: ($payment->paid_at ?? now())->toDateString(),
            sourceKey: 'payment:'.$payment->id,
            source: new ModelSource(Payment::class, $payment->id),
            user: $user ?? $payment->recorder,
        );
    }

    public function postOrderReceivable(Order $order, ?User $user = null): ?JournalEntry
    {
        $amount = (float) $order->total;
        if (! $this->ready() || $amount <= 0) {
            return null;
        }

        return $this->post(
            memo: 'إثبات إيراد وذمم للطلب '.$order->number,
            lines: [
                ['account_code' => '1200', 'debit' => $amount, 'memo' => 'ذمم مدينة'],
                ['account_code' => '4000', 'credit' => $amount, 'memo' => 'إيراد مبيعات'],
            ],
            entryDate: now()->toDateString(),
            sourceKey: 'order-ar:'.$order->id,
            source: new ModelSource(Order::class, $order->id),
            user: $user,
        );
    }

    public function postPurchase(Purchase $purchase, ?User $user = null): ?JournalEntry
    {
        if (! $this->ready()) {
            return null;
        }

        return $this->post(
            memo: 'فاتورة شراء '.$purchase->number,
            lines: [
                ['account_code' => '1300', 'debit' => $purchase->total, 'memo' => 'مخزون'],
                ['account_code' => '1000', 'credit' => $purchase->total, 'memo' => 'صرف نقدي للمورد'],
            ],
            entryDate: $purchase->purchased_at->toDateString(),
            sourceKey: 'purchase:'.$purchase->id,
            source: new ModelSource(Purchase::class, $purchase->id),
            user: $user,
        );
    }

    public function postExpense(Expense $expense, ?User $user = null): ?JournalEntry
    {
        if (! $this->ready()) {
            return null;
        }

        $expense->loadMissing('category');

        return $this->post(
            memo: 'مصروف: '.$expense->title,
            lines: [
                ['account_code' => '5100', 'debit' => $expense->amount, 'memo' => $expense->category?->name],
                ['account_code' => '1000', 'credit' => $expense->amount, 'memo' => 'صرف نقدي'],
            ],
            entryDate: $expense->spent_at->toDateString(),
            sourceKey: 'expense:'.$expense->id,
            source: new ModelSource(Expense::class, $expense->id),
            user: $user,
        );
    }

    /**
     * @return array{assets:float,liabilities:float,equity:float,revenue:float,expenses:float,net_income:float,cash:float,bank:float,receivables:float,inventory:float}
     */
    public function trialBalanceSummary(?string $from = null, ?string $to = null): array
    {
        $accounts = Account::query()->where('is_active', true)->get();

        $lineQuery = function (Account $account) use ($from, $to): array {
            $q = $account->lines()->whereHas('entry', function ($e) use ($from, $to) {
                if ($from) {
                    $e->whereDate('entry_date', '>=', $from);
                }
                if ($to) {
                    $e->whereDate('entry_date', '<=', $to);
                }
            });

            return [
                'debit' => (float) (clone $q)->sum('debit'),
                'credit' => (float) (clone $q)->sum('credit'),
            ];
        };

        $balanceOf = function (Account $account) use ($lineQuery): float {
            $sums = $lineQuery($account);
            $debit = $sums['debit'];
            $credit = $sums['credit'];

            return match ($account->type) {
                AccountType::Asset, AccountType::Expense => $debit - $credit,
                default => $credit - $debit,
            };
        };

        $byCode = fn (string $code) => (float) optional($accounts->firstWhere('code', $code), $balanceOf);
        $revenue = (float) $accounts->where('type', AccountType::Revenue)->sum($balanceOf);
        $expenses = (float) $accounts->where('type', AccountType::Expense)->sum($balanceOf);

        return [
            'assets' => (float) $accounts->where('type', AccountType::Asset)->sum($balanceOf),
            'liabilities' => (float) $accounts->where('type', AccountType::Liability)->sum($balanceOf),
            'equity' => (float) $accounts->where('type', AccountType::Equity)->sum($balanceOf),
            'revenue' => $revenue,
            'expenses' => $expenses,
            'net_income' => $revenue - $expenses,
            'cash' => $byCode('1000'),
            'bank' => $byCode('1100'),
            'receivables' => $byCode('1200'),
            'inventory' => $byCode('1300'),
        ];
    }

    public function readyPublic(): bool
    {
        return $this->ready();
    }

    protected function ready(): bool
    {
        return Account::query()->where('code', '1000')->exists();
    }

    protected function nextNumber(): string
    {
        $seq = JournalEntry::query()->whereDate('created_at', today())->count() + 1;

        return 'JE-'.now()->format('ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
