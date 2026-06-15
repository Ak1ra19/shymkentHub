<x-filament-widgets::widget>
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(20rem,0.9fr)]">
        <x-filament::section
            heading="Сегодняшняя загрузка"
            :description="'Рабочий день общего зала: '.$scheduleLabel"
        >
            @if ($entries->isNotEmpty())
                <div class="divide-y divide-gray-200 dark:divide-white/10">
                    @foreach ($entries as $entry)
                        <div class="flex items-start gap-4 py-3 first:pt-0 last:pb-0">
                            <div class="w-24 shrink-0 rounded-lg bg-gray-100 px-3 py-2 text-center text-sm font-semibold text-gray-800 dark:bg-white/8 dark:text-gray-200">
                                {{ $entry['time'] }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-semibold text-gray-950 dark:text-white">
                                        {{ $entry['title'] }}
                                    </p>

                                    <x-filament::badge :color="$entry['tone']">
                                        {{ $entry['status'] }}
                                    </x-filament::badge>
                                </div>

                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $entry['meta'] }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="rounded-lg border border-dashed border-gray-300 p-6 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                    На сегодня активных броней нет.
                </div>
            @endif
        </x-filament::section>

        <x-filament::section
            heading="Ожидают решения"
            description="Ближайшие заявки на конференц-зал."
        >
            @if ($pendingRequests->isNotEmpty())
                <div class="space-y-3">
                    @foreach ($pendingRequests as $request)
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-500/30 dark:bg-amber-500/10">
                            <div class="flex items-start justify-between gap-3">
                                <p class="text-sm font-semibold text-gray-950 dark:text-white">
                                    {{ $request['purpose'] }}
                                </p>

                                <span class="shrink-0 text-xs font-medium text-amber-700 dark:text-amber-300">
                                    {{ $request['time'] }}
                                </span>
                            </div>

                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                {{ $request['user'] }}
                            </p>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="rounded-lg border border-dashed border-gray-300 p-6 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                    Нет заявок, которые ждут согласования.
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-widgets::widget>
