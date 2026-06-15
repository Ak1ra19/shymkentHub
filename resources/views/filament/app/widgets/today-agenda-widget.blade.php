<x-filament-widgets::widget>
    <x-filament::section
        heading="Моя повестка на сегодня"
        description="Все ваши бронирования и заявки текущего дня в одном блоке."
    >
        @if ($entries->isNotEmpty())
            <div class="grid gap-3 xl:grid-cols-2">
                @foreach ($entries as $entry)
                    <article class="rounded-xl border border-gray-200/80 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/[0.03]">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <span @class([
                                'rounded-full px-2.5 py-1 text-xs font-medium',
                                'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300' => $entry['tone'] === 'amber',
                                'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300' => $entry['tone'] === 'sky',
                            ])>
                                {{ $entry['meta'] }}
                            </span>

                            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs text-gray-600 dark:bg-white/8 dark:text-gray-300">
                                {{ $entry['status'] }}
                            </span>
                        </div>

                        <h3 class="mt-4 text-base font-semibold text-gray-950 dark:text-white">
                            {{ $entry['title'] }}
                        </h3>

                        <div class="mt-4 flex items-center justify-between gap-3 border-t border-gray-200 pt-3 text-sm text-gray-600 dark:border-white/10 dark:text-gray-300">
                            <span>{{ $entry['time'] }}</span>
                            <span class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">На сегодня</span>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50/80 p-6 text-sm text-gray-500 dark:border-white/10 dark:bg-white/[0.03] dark:text-gray-400">
                На сегодня у вас пока нет запланированных броней или заявок.
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
