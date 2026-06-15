<x-filament::section>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
            <h2 class="text-xl font-semibold text-gray-950 dark:text-white">
                Бронирование рабочих мест
            </h2>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Сегодня: {{ $dateLabel }}
            </p>
        </div>

        <div class="grid grid-cols-3 gap-2 text-sm sm:flex sm:flex-wrap sm:justify-end">
            <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 dark:border-white/10 dark:bg-white/[0.03]">
                <p class="text-xs text-gray-500 dark:text-gray-400">Доступно</p>
                <p class="text-base font-semibold text-gray-950 dark:text-white">{{ $availableCount }} / {{ $totalCount }}</p>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 dark:border-white/10 dark:bg-white/[0.03]">
                <p class="text-xs text-gray-500 dark:text-gray-400">Ваши места</p>
                <p class="text-base font-semibold text-gray-950 dark:text-white">{{ $bookedByUserCount }}</p>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 dark:border-white/10 dark:bg-white/[0.03]">
                <p class="text-xs text-gray-500 dark:text-gray-400">День</p>
                <p class="text-base font-semibold text-gray-950 dark:text-white">{{ $scheduleLabel }}</p>
            </div>
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-white/10 dark:bg-gray-950/40 sm:p-5">
        @if ($workspaces->isNotEmpty())
            <div class="grid grid-cols-3 gap-3 sm:grid-cols-5 lg:grid-cols-8 xl:grid-cols-10">
                @foreach ($workspaces as $workspace)
                    @php
                        $statusClasses = match ($workspace['status']) {
                            'mine_full' => 'cursor-not-allowed border-emerald-600 bg-emerald-600 text-white shadow-sm shadow-emerald-600/20 dark:border-emerald-400 dark:bg-emerald-500',
                            'mine' => 'border-primary-500 bg-primary-500 text-white shadow-sm shadow-primary-500/20 hover:bg-primary-600 dark:border-primary-400 dark:bg-primary-500',
                            'partial' => 'border-amber-300 bg-amber-50 text-amber-800 hover:border-amber-400 hover:bg-amber-100 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200',
                            'occupied_full' => 'cursor-not-allowed border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300',
                            'occupied' => 'cursor-not-allowed border-gray-200 bg-gray-100 text-gray-400 dark:border-white/10 dark:bg-white/[0.04] dark:text-gray-500',
                            default => 'border-gray-200 bg-white text-gray-900 hover:border-primary-400 hover:bg-primary-50 dark:border-white/10 dark:bg-white/[0.03] dark:text-white dark:hover:border-primary-400 dark:hover:bg-primary-500/10',
                        };
                    @endphp

                    <button
                        type="button"
                        @if ($workspace['can_book'])
                            wire:click="mountAction('create', @js(['workspace' => $workspace['id']]))"
                        @else
                            disabled
                        @endif
                        class="group relative flex aspect-[5/3] min-h-16 flex-col items-center justify-center rounded-lg border text-sm font-semibold transition {{ $statusClasses }}"
                        title="{{ $workspace['label'] }}. {{ $workspace['description'] }}"
                    >
                        <span>{{ $workspace['number'] }}</span>

                        @if ($workspace['assigned_to_current_user'])
                            <span class="mt-1 rounded-full bg-white/20 px-1.5 py-0.5 text-[10px] font-medium">
                                ваше
                            </span>
                        @endif
                    </button>
                @endforeach
            </div>

            <div class="mt-6 border-t border-gray-200 pt-4 dark:border-white/10">
                <div class="flex flex-wrap gap-x-5 gap-y-3 text-sm text-gray-600 dark:text-gray-300">
                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-full border border-gray-300 bg-white dark:border-white/20 dark:bg-white/[0.03]"></span>
                        <span>Свободно</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-full bg-amber-400"></span>
                        <span>Есть занятые часы</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-full bg-primary-500"></span>
                        <span>Ваше бронирование</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-full bg-emerald-600"></span>
                        <span>Ваше на весь день</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-full bg-rose-400"></span>
                        <span>Занято весь день</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                        <span>Нет свободного часа</span>
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-lg border border-dashed border-gray-300 p-6 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                Сейчас нет активных рабочих мест, доступных для бронирования.
            </div>
        @endif
    </div>
</x-filament::section>

<x-filament-actions::modals />
