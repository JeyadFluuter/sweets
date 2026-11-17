<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">تفاصيل الأكثر طلباً والمخزون</x-slot>
        <x-slot name="description">آخر 30 يوماً للطلبات المؤكدة والجارية والمكتملة</x-slot>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="overflow-x-auto">
                <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-200">الأصناف الأعلى مبيعاً</h3>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-start dark:border-white/10">
                            <th class="px-2 py-2 text-start">#</th>
                            <th class="px-2 py-2 text-start">الصنف</th>
                            <th class="px-2 py-2 text-start">النوع</th>
                            <th class="px-2 py-2 text-start">الكمية</th>
                            <th class="px-2 py-2 text-start">الإجمالي</th>
                            <th class="px-2 py-2 text-start">متوسط السعر</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $i => $item)
                            <tr class="border-b border-gray-100 dark:border-white/5">
                                <td class="px-2 py-2">{{ $i + 1 }}</td>
                                <td class="px-2 py-2 font-medium">{{ $item['name'] }}</td>
                                <td class="px-2 py-2">{{ $item['item_type'] === 'collection' ? 'باقة' : 'منتج' }}</td>
                                <td class="px-2 py-2">{{ $item['qty'] }}</td>
                                <td class="px-2 py-2">{{ number_format($item['total'], 2) }} د.ل</td>
                                <td class="px-2 py-2">
                                    {{ $item['qty'] > 0 ? number_format($item['total'] / $item['qty'], 2) : '0.00' }} د.ل
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-2 py-6 text-center text-gray-500">لا توجد مبيعات كافية بعد</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="space-y-6">
                <div>
                    <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-200">المخزون المنخفض</h3>
                    @if (count($lowStock))
                        <ul class="space-y-2 text-sm">
                            @foreach ($lowStock as $stock)
                                <li class="flex items-center justify-between rounded-lg bg-amber-50 px-3 py-2 dark:bg-amber-500/10">
                                    <span>{{ $stock['name'] }}</span>
                                    <span class="font-semibold text-amber-700 dark:text-amber-300">
                                        {{ $stock['quantity'] }} / حد {{ $stock['min_quantity'] }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-gray-500">كل الأصناف ضمن الحد الآمن.</p>
                    @endif
                </div>

                @if ($books)
                    <div>
                        <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-200">ملخص الدفتر (هذا الشهر)</h3>
                        <dl class="grid grid-cols-2 gap-2 text-sm">
                            <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-white/5">
                                <dt class="text-gray-500">إيرادات دفترية</dt>
                                <dd class="font-semibold">{{ number_format($books['revenue'], 2) }} د.ل</dd>
                            </div>
                            <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-white/5">
                                <dt class="text-gray-500">مصروفات دفترية</dt>
                                <dd class="font-semibold">{{ number_format($books['expenses'], 2) }} د.ل</dd>
                            </div>
                            <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-white/5">
                                <dt class="text-gray-500">صافي الدخل</dt>
                                <dd class="font-semibold">{{ number_format($books['net_income'], 2) }} د.ل</dd>
                            </div>
                            <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-white/5">
                                <dt class="text-gray-500">الذمم المدينة</dt>
                                <dd class="font-semibold">{{ number_format($books['receivables'], 2) }} د.ل</dd>
                            </div>
                            <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-white/5">
                                <dt class="text-gray-500">المخزون</dt>
                                <dd class="font-semibold">{{ number_format($books['inventory'], 2) }} د.ل</dd>
                            </div>
                            <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-white/5">
                                <dt class="text-gray-500">نقدية + بنك</dt>
                                <dd class="font-semibold">{{ number_format($books['cash'] + $books['bank'], 2) }} د.ل</dd>
                            </div>
                        </dl>
                    </div>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
