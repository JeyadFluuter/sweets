<x-filament-panels::page>
    @php
        /** @var \App\Models\AiConversation $record */
        $record = $this->record;
        $record->loadMissing(['customer', 'messages']);
        $messages = $record->messages->sortBy('id')->values();
        $ctx = is_array($record->context) ? $record->context : [];
    @endphp

    <div class="space-y-5" dir="rtl">
        <div class="overflow-hidden rounded-3xl border border-emerald-100 bg-gradient-to-l from-emerald-600 via-emerald-500 to-amber-400 p-5 text-white shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="text-xs font-semibold text-white/80">محادثة المساعد</div>
                    <h2 class="mt-1 text-xl font-extrabold">#{{ $record->id }} · {{ $record->customer?->name ?: 'عميل' }}</h2>
                    <p class="mt-1 text-sm text-white/90">
                        @if ($record->customer?->phone)
                            {{ $record->customer->phone }} ·
                        @endif
                        {{ $record->title ?: 'بدون عنوان' }}
                    </p>
                    <p class="mt-1 text-xs text-white/75">
                        بدأت {{ $record->created_at?->format('Y-m-d H:i') }}
                        · آخر نشاط {{ $record->updated_at?->format('Y-m-d H:i') }}
                    </p>
                </div>
                <div class="rounded-2xl bg-white/15 px-4 py-3 text-center backdrop-blur-sm">
                    <div class="text-2xl font-black">{{ $messages->count() }}</div>
                    <div class="text-xs font-semibold text-white/85">رسالة</div>
                </div>
            </div>

            @if ($ctx !== [])
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ([
                        'status' => ['الحالة', 'bg-white/15'],
                        'occasion' => ['مناسبة', 'bg-sky-500/25'],
                        'people' => ['أشخاص', 'bg-violet-500/25'],
                        'budget' => ['ميزانية', 'bg-amber-500/30'],
                    ] as $key => [$label, $class])
                        @if (!empty($ctx[$key]))
                            <span class="rounded-full {{ $class }} px-3 py-1 text-xs font-bold text-white">
                                {{ $label }}:
                                {{ $key === 'budget' ? $ctx[$key].' د.ل' : $ctx[$key] }}
                            </span>
                        @endif
                    @endforeach
                    @if (!empty($ctx['focus_keywords']) && is_array($ctx['focus_keywords']))
                        <span class="rounded-full bg-rose-500/25 px-3 py-1 text-xs font-bold text-white">
                            تركيز: {{ implode('، ', $ctx['focus_keywords']) }}
                        </span>
                    @endif
                </div>
            @endif
        </div>

        <div class="overflow-hidden rounded-3xl border border-gray-200 bg-[#eef6f1] shadow-sm dark:border-white/10 dark:bg-gray-950">
            <div class="flex items-center justify-between border-b border-emerald-100/80 bg-white/90 px-5 py-3 backdrop-blur dark:border-white/10 dark:bg-gray-900/90">
                <div>
                    <h3 class="text-sm font-extrabold text-gray-950 dark:text-white">سجل الشات</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">فقاعات واضحة · العميل أخضر · المساعد أبيض</p>
                </div>
            </div>

            <div class="max-h-[72vh] space-y-5 overflow-y-auto p-4 sm:p-6">
                @forelse ($messages as $message)
                    @php
                        $isUser = $message->role === 'user';
                        $isAssistant = $message->role === 'assistant';
                        $suggestions = is_array($message->suggested_items) ? $message->suggested_items : [];
                    @endphp

                    <div class="flex {{ $isUser ? 'justify-end' : 'justify-start' }}">
                        <div class="flex w-full max-w-[min(100%,40rem)] flex-col gap-2 {{ $isUser ? 'items-end' : 'items-start' }}">
                            <div class="flex items-center gap-2 text-[11px] font-bold {{ $isUser ? 'text-emerald-700 dark:text-emerald-300' : 'text-gray-500 dark:text-gray-400' }}">
                                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full {{ $isUser ? 'bg-emerald-500 text-white' : 'bg-white text-emerald-700 ring-1 ring-emerald-100 dark:bg-gray-900 dark:text-emerald-300 dark:ring-white/10' }}">
                                    {{ $isUser ? 'ع' : 'م' }}
                                </span>
                                <span>{{ $isUser ? 'العميل' : ($isAssistant ? 'المساعد' : 'نظام') }}</span>
                                <span class="font-medium text-gray-400">{{ $message->created_at?->format('H:i · Y-m-d') }}</span>
                            </div>

                            <div @class([
                                'rounded-2xl px-4 py-3 text-sm leading-7 shadow-sm whitespace-pre-wrap break-words',
                                'bg-gradient-to-l from-emerald-600 to-emerald-500 text-white rounded-bl-md' => $isUser,
                                'bg-white text-gray-900 border border-gray-200 rounded-br-md dark:bg-gray-900 dark:text-gray-100 dark:border-white/10' => ! $isUser,
                            ])>
                                {{ is_array($message->content) ? json_encode($message->content, JSON_UNESCAPED_UNICODE) : ($message->content ?: '—') }}
                            </div>

                            @if ($isAssistant && ($message->provider || $message->model || $message->latency_ms))
                                <div class="flex flex-wrap gap-2 text-[10px] text-gray-400">
                                    @if ($message->provider)<span>{{ $message->provider }}</span>@endif
                                    @if ($message->model)<span>· {{ $message->model }}</span>@endif
                                    @if ($message->latency_ms)<span>· {{ $message->latency_ms }} ms</span>@endif
                                </div>
                            @endif

                            @if ($suggestions !== [])
                                <div class="w-full overflow-hidden rounded-2xl border border-emerald-200 bg-white shadow-sm dark:border-emerald-500/30 dark:bg-gray-900">
                                    <div class="flex items-center justify-between gap-2 bg-emerald-50 px-3 py-2 dark:bg-emerald-500/10">
                                        <span class="text-xs font-extrabold text-emerald-800 dark:text-emerald-300">عرض مقترح</span>
                                        @php
                                            $total = collect($suggestions)->sum(function ($item) {
                                                if (! is_array($item)) return 0;
                                                return ((float) ($item['unit_price'] ?? 0)) * ((int) ($item['quantity'] ?? 1));
                                            });
                                        @endphp
                                        @if ($total > 0)
                                            <span class="text-xs font-black text-emerald-700 dark:text-emerald-300">
                                                {{ rtrim(rtrim(number_format($total, 2, '.', ''), '0'), '.') }} د.ل
                                            </span>
                                        @endif
                                    </div>
                                    <div class="divide-y divide-emerald-50 dark:divide-white/5">
                                        @foreach ($suggestions as $item)
                                            @continue(! is_array($item))
                                            @php
                                                $type = (string) ($item['type'] ?? 'product');
                                                $name = (string) ($item['name'] ?? ('#'.($item['id'] ?? '?')));
                                                $qty = (int) ($item['quantity'] ?? 1);
                                                $price = (float) ($item['unit_price'] ?? 0);
                                                $reason = (string) ($item['reason'] ?? '');
                                                $line = $price * $qty;
                                            @endphp
                                            <div class="flex items-start justify-between gap-3 px-3 py-2.5">
                                                <div class="min-w-0">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="rounded-md bg-emerald-100 px-1.5 py-0.5 text-[10px] font-bold text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300">
                                                            {{ $type === 'collection' ? 'باقة' : 'صنف' }}
                                                        </span>
                                                        <span class="truncate text-sm font-extrabold text-gray-900 dark:text-white">{{ $name }}</span>
                                                    </div>
                                                    @if ($reason !== '')
                                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $reason }}</p>
                                                    @endif
                                                </div>
                                                <div class="shrink-0 text-left text-xs font-extrabold text-emerald-700 dark:text-emerald-300">
                                                    <div>×{{ $qty }}</div>
                                                    @if ($price > 0)
                                                        <div>{{ rtrim(rtrim(number_format($line, 2, '.', ''), '0'), '.') }} د.ل</div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500 dark:border-white/10 dark:bg-gray-900 dark:text-gray-400">
                        لا توجد رسائل في هذه المحادثة بعد.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-filament-panels::page>
