<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-3">
            <x-filament::section heading="Бронирования сегодня">
                <p class="text-3xl font-semibold text-gray-950 dark:text-white">{{ $this->todayBookings }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Рабочие места и конференц-зал</p>
            </x-filament::section>

            <x-filament::section heading="Занятые места">
                <p class="text-3xl font-semibold text-gray-950 dark:text-white">
                    {{ $this->occupiedWorkspaces }} / {{ $this->activeWorkspaces }}
                </p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Активные рабочие места сегодня</p>
            </x-filament::section>

            <x-filament::section heading="На согласовании">
                <p class="text-3xl font-semibold text-gray-950 dark:text-white">{{ $this->pendingConferenceRequests }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Заявки на конференц-зал</p>
            </x-filament::section>
        </div>

        <x-filament::section
            heading="Отчет по бронированиям"
            description="Экспорт Excel за выбранный период."
        >
            <form method="GET" action="{{ route('admin.reports.bookings') }}" class="grid gap-4 md:grid-cols-[1fr_1fr_auto] md:items-end">
                <label class="space-y-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Дата от</span>
                    <x-filament::input.wrapper>
                        <x-filament::input name="from" type="date" value="{{ now()->toDateString() }}" required />
                    </x-filament::input.wrapper>
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Дата до</span>
                    <x-filament::input.wrapper>
                        <x-filament::input name="to" type="date" value="{{ now()->toDateString() }}" required />
                    </x-filament::input.wrapper>
                </label>

                <x-filament::button type="submit">
                    Скачать .xlsx
                </x-filament::button>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
