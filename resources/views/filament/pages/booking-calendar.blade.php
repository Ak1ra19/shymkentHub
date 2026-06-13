<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section
            :heading="$this->periodLabel"
            :description="$this->entries->count().' записей по выбранным фильтрам.'"
        >
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
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
                    <x-filament::button type="button" wire:click="setToday">
                        Сегодня
                    </x-filament::button>
                    <x-filament::button type="button" color="gray" wire:click="clearSelectedDate">
                        Период
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        <div class="grid gap-4 xl:grid-cols-2">
            @forelse ($this->groupedEntries as $date => $entries)
                <x-filament::section
                    :heading="\Carbon\CarbonImmutable::parse($date)->translatedFormat('d F')"
                    :description="\Carbon\CarbonImmutable::parse($date)->translatedFormat('l')"
                >
                    <div class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($entries as $entry)
                            <div class="py-3">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $entry['resource'] }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $entry['time'] }} · {{ $entry['user'] }} · {{ $entry['company'] }}
                                        </p>
                                    </div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $entry['status'] }}</span>
                                </div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $entry['type'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            @empty
                <x-filament::section>
                    <div class="py-8 text-sm text-gray-500 dark:text-gray-400">
                        По выбранным фильтрам бронирований нет.
                    </div>
                </x-filament::section>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
