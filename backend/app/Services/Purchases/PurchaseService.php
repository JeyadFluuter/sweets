<?php

namespace App\Services\Purchases;

use App\Models\Purchase;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Finance\AccountingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseService
{
    public function __construct(private readonly AccountingService $accountingService) {}

    public function create(array $data, ?User $user = null): Purchase
    {
        return DB::transaction(function () use ($data, $user) {
            $purchase = Purchase::query()->create([
                'number' => 'PU-'.now()->format('ymd').'-'.Str::upper(Str::random(4)),
                'supplier_id' => $data['supplier_id'],
                'purchased_at' => $data['purchased_at'] ?? now()->toDateString(),
                'notes' => $data['notes'] ?? null,
                'status' => 'confirmed',
                'created_by' => $user?->id,
                'total' => 0,
            ]);

            $total = 0;
            foreach ($data['items'] as $row) {
                $line = round(((float) $row['quantity']) * ((float) $row['unit_cost']), 2);
                $purchase->items()->create([
                    'stock_item_id' => $row['stock_item_id'],
                    'quantity' => $row['quantity'],
                    'unit_cost' => $row['unit_cost'],
                    'line_total' => $line,
                ]);
                $total += $line;

                $stock = StockItem::query()->whereKey($row['stock_item_id'])->lockForUpdate()->firstOrFail();
                $stock->update(['quantity' => (float) $stock->quantity + (float) $row['quantity']]);

                StockMovement::query()->create([
                    'stock_item_id' => $stock->id,
                    'type' => 'in',
                    'quantity' => $row['quantity'],
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'note' => 'فاتورة شراء '.$purchase->number,
                    'created_by' => $user?->id,
                ]);
            }

            $purchase->update(['total' => $total]);
            $purchase = $purchase->load(['items.stockItem', 'supplier']);
            $this->accountingService->postPurchase($purchase, $user);

            return $purchase;
        });
    }
}
