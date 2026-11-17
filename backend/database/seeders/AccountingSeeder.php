<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Models\Account;
use Illuminate\Database\Seeder;

class AccountingSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['1000', 'الصندوق / النقدية', AccountType::Asset, 'المقبوضات النقدية'],
            ['1100', 'البنك', AccountType::Asset, 'الحساب البنكي والتحويلات'],
            ['1200', 'الذمم المدينة', AccountType::Asset, 'مستحقات العملاء على الطلبات'],
            ['1300', 'المخزون', AccountType::Asset, 'مواد خام وأصناف المخزون'],
            ['2000', 'الذمم الدائنة', AccountType::Liability, 'مستحقات للموردين'],
            ['3000', 'رأس المال', AccountType::Equity, 'حقوق الملكية'],
            ['4000', 'إيرادات المبيعات', AccountType::Revenue, 'مبيعات المنتجات والباقات'],
            ['5000', 'تكلفة المشتريات', AccountType::Expense, 'مشتريات المواد'],
            ['5100', 'مصروفات تشغيلية', AccountType::Expense, 'إيجار ورواتب ومصروفات عامة'],
        ];

        foreach ($accounts as [$code, $name, $type, $description]) {
            Account::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'type' => $type,
                    'description' => $description,
                    'is_active' => true,
                ]
            );
        }
    }
}
