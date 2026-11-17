<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\StockItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Finance\ExpenseService;
use App\Services\Purchases\PurchaseService;
use Illuminate\Database\Seeder;

class FinanceSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['رواتب', 'إيجار', 'كهرباء', 'مواد تغليف', 'صيانة', 'نقل وتوصيل', 'تسويق'] as $name) {
            ExpenseCategory::query()->firstOrCreate(['name' => $name], ['is_active' => true]);
        }

        $suppliers = [
            ['مخابز الأمل', '0912222001', 'طرابلس'],
            ['مؤسسة النور للمكسرات', '0912222002', 'مصراتة'],
            ['شركة التعبئة الحديثة', '0912222003', 'بنغازي'],
        ];

        foreach ($suppliers as [$name, $phone, $address]) {
            Supplier::query()->updateOrCreate(
                ['name' => $name],
                ['phone' => $phone, 'address' => $address, 'is_active' => true]
            );
        }

        foreach ([
            ['دقيق فاخر', 'كيس 50كغ', 40, 8],
            ['سكر', 'كيس 50كغ', 25, 5],
            ['لوز', 'كغ', 12, 3],
            ['فستق حلبي', 'كغ', 6, 2],
            ['سمن بلدي', 'كغ', 15, 4],
            ['علب تغليف كبيرة', 'قطعة', 200, 50],
            ['علب تغليف صغيرة', 'قطعة', 350, 80],
        ] as [$name, $unit, $qty, $min]) {
            StockItem::query()->updateOrCreate(
                ['name' => $name],
                ['unit' => $unit, 'quantity' => $qty, 'min_quantity' => $min, 'is_active' => true]
            );
        }

        // مخزون منخفض للتنبيه في الداشبورد
        StockItem::query()->updateOrCreate(
            ['name' => 'قطر جاهز'],
            ['unit' => 'لتر', 'quantity' => 1.5, 'min_quantity' => 5, 'is_active' => true]
        );

        $admin = User::query()->where('email', 'admin@sweets.ly')->first()
            ?? User::query()->first();
        $supplier = Supplier::query()->where('name', 'مخابز الأمل')->first();
        $stock = StockItem::query()->whereIn('name', ['دقيق فاخر', 'سكر', 'لوز'])->get();

        if ($admin && $supplier && $stock->count() >= 2) {
            $purchaseService = app(PurchaseService::class);

            // تجنب تكرار المشتريات عند إعادة الـ seed
            if (! \App\Models\Purchase::query()->where('notes', 'فاتورة بذرة تجريبية')->exists()) {
                $purchaseService->create([
                    'supplier_id' => $supplier->id,
                    'purchased_at' => now()->subDays(5)->toDateString(),
                    'notes' => 'فاتورة بذرة تجريبية',
                    'items' => [
                        [
                            'stock_item_id' => $stock[0]->id,
                            'quantity' => 5,
                            'unit_cost' => 85,
                        ],
                        [
                            'stock_item_id' => $stock[1]->id,
                            'quantity' => 3,
                            'unit_cost' => 70,
                        ],
                    ],
                ], $admin);
            }
        }

        $rent = ExpenseCategory::query()->where('name', 'إيجار')->first();
        $power = ExpenseCategory::query()->where('name', 'كهرباء')->first();
        $expenseService = app(ExpenseService::class);

        if ($admin && $rent && ! Expense::query()->where('title', 'إيجار المحل - الشهر الحالي')->exists()) {
            $expenseService->create([
                'expense_category_id' => $rent->id,
                'title' => 'إيجار المحل - الشهر الحالي',
                'amount' => 2500,
                'spent_at' => now()->startOfMonth()->toDateString(),
                'notes' => 'بذرة تجريبية',
            ], $admin);
        }

        if ($admin && $power && ! Expense::query()->where('title', 'فاتورة كهرباء')->exists()) {
            $expenseService->create([
                'expense_category_id' => $power->id,
                'title' => 'فاتورة كهرباء',
                'amount' => 380,
                'spent_at' => now()->subDays(3)->toDateString(),
                'notes' => 'بذرة تجريبية',
            ], $admin);
        }
    }
}
