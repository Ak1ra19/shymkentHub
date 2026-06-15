<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section
            :heading="$this->periodLabel"
            :description="$this->summary['total'].' записей по выбранным фильтрам.'"
        >
            <div class="space-y-5">
                <div class="grid gap-3 md:grid-cols-4">
                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-white/10 dark:bg-white/[0.03]">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Всего</p>
                        <p class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">{{ $this->summary['total'] }}</p>
                    </div>

                    <div class="rounded-lg border border-teal-200 bg-teal-50 px-4 py-3 dark:border-teal-500/30 dark:bg-teal-500/10">
                        <p class="text-xs font-medium text-teal-700 dark:text-teal-300">Рабочие места</p>
                        <p class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">{{ $this->summary['workspace'] }}</p>
                    </div>

                    <div class="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 dark:border-indigo-500/30 dark:bg-indigo-500/10">
                        <p class="text-xs font-medium text-indigo-700 dark:text-indigo-300">Конференц-зал</p>
                        <p class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">{{ $this->summary['conference'] }}</p>
                    </div>

                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-500/30 dark:bg-amber-500/10">
                        <p class="text-xs font-medium text-amber-700 dark:text-amber-300">На рассмотрении</p>
                        <p class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">{{ $this->summary['pending'] }}</p>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-[1fr_1fr_1fr_1fr_auto]">
                    <label class="space-y-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Конкретная дата</span>
                        <x-filament::input.wrapper>
                            <x-filament::input type="date" wire:model.live="selectedDate" />
                        </x-filament::input.wrapper>
                    </label>

                    <label class="space-y-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Дата от</span>
                        <x-filament::input.wrapper>
                            <x-filament::input type="date" wire:model.live="fromDate" />
                        </x-filament::input.wrapper>
                    </label>

                    <label class="space-y-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Дата до</span>
                        <x-filament::input.wrapper>
                            <x-filament::input type="date" wire:model.live="toDate" />
                        </x-filament::input.wrapper>
                    </label>

                    <label class="space-y-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Тип бронирования</span>
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="bookingType">
                                <option value="all">Все типы</option>
                                <option value="workspace">Рабочие места</option>
                                <option value="conference">Конференц-зал</option>
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </label>

                    <div class="flex items-end gap-2">
                        <x-filament::button type="button" wire:click="setToday" icon="heroicon-m-calendar">
                            Сегодня
                        </x-filament::button>
                        <x-filament::button type="button" color="gray" wire:click="clearSelectedDate">
                            Период
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </x-filament::section>

        <div class="grid gap-4 xl:grid-cols-2">
            @forelse ($this->groupedEntries as $date => $entries)
                <x-filament::section
                    :heading="\Carbon\CarbonImmutable::parse($date)->translatedFormat('d F')"
                    :description="\Carbon\CarbonImmutable::parse($date)->translatedFormat('l')"
                >
                    <div class="space-y-3">
                        @foreach ($entries as $entry)
                            @php
                                $entryClasses = $entry['tone'] === 'teal'
                                    ? 'border-teal-200 bg-teal-50/70 dark:border-teal-500/30 dark:bg-teal-500/10'
                                    : 'border-indigo-200 bg-indigo-50/70 dark:border-indigo-500/30 dark:bg-indigo-500/10';
                            @endphp

                            <div class="rounded-lg border p-4 {{ $entryClasses }}">
                                <div class="flex items-start gap-4">
                                    <div class="w-24 shrink-0 rounded-md bg-white px-3 py-2 text-center text-xs font-semibold text-gray-800 shadow-sm dark:bg-white/10 dark:text-gray-200">
                                        {{ $entry['time'] }}
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $entry['resource'] }}</p>
                                            <x-filament::badge color="gray">{{ $entry['status'] }}</x-filament::badge>
                                        </div>

                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                            {{ $entry['user'] }}{{ filled($entry['company']) ? ' · '.$entry['company'] : '' }}
                                        </p>

                                        <p class="mt-1 text-xs font-medium text-gray-500 dark:text-gray-400">
                                            {{ $entry['type'] }} · {{ $entry['meta'] }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            @empty
                <x-filament::section class="xl:col-span-2">
                    <div class="py-8 text-sm text-gray-500 dark:text-gray-400">
                        По выбранным фильтрам бронирований нет.
                    </div>
                </x-filament::section>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
