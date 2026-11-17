<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <div class="flex flex-wrap items-center gap-3">
                <x-filament::button type="submit" icon="heroicon-o-check">
                    حفظ كل الإعدادات
                </x-filament::button>
                <x-filament::button
                    type="button"
                    color="success"
                    icon="heroicon-o-signal"
                    wire:click="testConnection"
                    wire:loading.attr="disabled"
                >
                    اختبار الاتصال
                </x-filament::button>
                <x-filament::button
                    color="gray"
                    icon="heroicon-o-chat-bubble-left-right"
                    tag="a"
                    href="{{ \App\Filament\Resources\AiConversationResource::getUrl() }}"
                >
                    محادثات العملاء
                </x-filament::button>
            </div>
        </form>

        @php($versions = $this->recentPromptVersions())
        @if (count($versions))
            <x-filament::section>
                <x-slot name="heading">سجل تعديلات البرومبت</x-slot>
                <x-slot name="description">كل نسخة محفوظة بالكامل — يمكنك استعراضها أو استرجاعها للمحرر ثم الحفظ</x-slot>

                <ul class="divide-y divide-gray-200 dark:divide-white/10">
                    @foreach ($versions as $version)
                        <li class="py-4 first:pt-0 last:pb-0" x-data="{ open: false }">
                            <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold text-gray-950 dark:text-white">
                                        {{ $version['editor'] ?: 'نظام' }}
                                    </span>
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-white/10 dark:text-gray-300">
                                        {{ $version['chars'] }} حرف
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-500 dark:text-gray-400">{{ $version['at'] }}</span>
                                    <x-filament::button
                                        size="xs"
                                        color="gray"
                                        type="button"
                                        x-on:click="open = !open"
                                    >
                                        <span x-text="open ? 'إخفاء الكامل' : 'عرض الكامل'"></span>
                                    </x-filament::button>
                                    <x-filament::button
                                        size="xs"
                                        color="primary"
                                        type="button"
                                        wire:click="restorePromptVersion({{ $version['id'] }})"
                                        wire:confirm="تحميل هذه النسخة في المحرر؟ لن تُحفظ إلا بعد ضغط حفظ الإعدادات."
                                    >
                                        استرجاع للمحرر
                                    </x-filament::button>
                                </div>
                            </div>

                            <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300 whitespace-pre-wrap" x-show="!open">
                                {{ $version['preview'] }}
                            </p>

                            <pre
                                x-show="open"
                                x-cloak
                                class="mt-3 max-h-[28rem] overflow-auto rounded-xl bg-gray-50 p-4 text-xs leading-6 text-gray-800 dark:bg-white/5 dark:text-gray-200 whitespace-pre-wrap"
                                style="direction: rtl; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;"
                            >{{ $version['prompt'] }}</pre>
                        </li>
                    @endforeach
                </ul>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
