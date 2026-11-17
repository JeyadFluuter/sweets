<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            AdminUserSeeder::class,
            StaffSeeder::class,
            SettingSeeder::class,
            CatalogSeeder::class,
            MarketingSeeder::class,
            FulfillmentSeeder::class,
            NotificationTemplateSeeder::class,
            AccountingSeeder::class,
            FinanceSeeder::class,
            CustomerSeeder::class,
            OrderDemoSeeder::class,
        ]);
    }
}
