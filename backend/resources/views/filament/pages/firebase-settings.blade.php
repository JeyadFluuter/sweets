<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <div class="flex flex-wrap items-center gap-3">
                <x-filament::button type="submit" icon="heroicon-o-check">
                    حفظ الاعتماد
                </x-filament::button>
                <x-filament::button type="button" color="gray" icon="heroicon-o-signal" wire:click="testConnection">
                    اختبار الاتصال
                </x-filament::button>
            </div>
        </form>

        @if (! empty($lastTest['steps']))
            <x-filament::section>
                <x-slot name="heading">نتيجة آخر اختبار</x-slot>
                <x-slot name="description">الحالة: {{ $lastTest['status'] ?? '—' }}</x-slot>

                <ul class="space-y-3">
                    @foreach ($lastTest['steps'] as $step)
                        <li @class([
                            'flex items-start gap-3 rounded-xl px-3 py-2.5 text-sm',
                            'bg-emerald-50 text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200' => $step['ok'],
                            'bg-rose-50 text-rose-900 dark:bg-rose-500/10 dark:text-rose-200' => ! $step['ok'],
                        ])>
                            <span class="mt-0.5">{{ $step['ok'] ? '✓' : '✕' }}</span>
                            <span>
                                <span class="font-semibold">{{ $step['name'] }}</span>
                                <span class="opacity-80"> — {{ $step['message'] }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
