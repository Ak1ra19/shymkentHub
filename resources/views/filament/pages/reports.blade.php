<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-white/[0.03]">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Сегодня</p>
                <p class="mt-3 text-3xl font-semibold text-gray-950 dark:text-white">{{ $this->todayBookings }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Все бронирования за день</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-white/[0.03]">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Общий зал</p>
                <p class="mt-3 text-3xl font-semibold text-gray-950 dark:text-white">
                    {{ $this->occupiedWorkspaces }} / {{ $this->activeWorkspaces }}
                </p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $this->occupancyPercent }}% активных мест занято</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-white/[0.03]">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Неделя</p>
                <p class="mt-3 text-3xl font-semibold text-gray-950 dark:text-white">{{ $this->weekBookings }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Брони с понедельника по воскресенье</p>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-500/30 dark:bg-amber-500/10">
                <p class="text-sm font-medium text-amber-700 dark:text-amber-300">На согласовании</p>
                <p class="mt-3 text-3xl font-semibold text-gray-950 dark:text-white">{{ $this->pendingConferenceRequests }}</p>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Заявки на конференц-зал</p>
            </div>
        </div>

        <x-filament::section
            heading="Отчет по бронированиям"
            description="Excel-файл включает рабочие места, конференц-зал, пользователя, дату, время, длительность и статус."
        >
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_18rem]">
                <form method="GET" action="{{ route('admin.reports.bookings') }}" class="grid gap-4 md:grid-cols-[1fr_1fr_auto] md:items-end">
                    <label class="space-y-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Дата от</span>
                        <x-filament::input.wrapper>
                            <x-filament::input name="from" type="date" value="{{ now()->startOfMonth()->toDateString() }}" required />
                        </x-filament::input.wrapper>
                    </label>

                    <label class="space-y-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Дата до</span>
                        <x-filament::input.wrapper>
                            <x-filament::input name="to" type="date" value="{{ now()->toDateString() }}" required />
                        </x-filament::input.wrapper>
                    </label>

                    <x-filament::button type="submit" icon="heroicon-m-arrow-down-tray">
                        Скачать .xlsx
                    </x-filament::button>
                </form>

                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600 dark:border-white/10 dark:bg-white/[0.03] dark:text-gray-300">
                    <p class="font-medium text-gray-950 dark:text-white">Что попадет в файл</p>
                    <ul class="mt-3 space-y-2">
                        <li>Рабочие места и конференц-зал.</li>
                        <li>Период, пользователь, компания и статус.</li>
                        <li>Время начала, окончания и количество часов.</li>
                    </ul>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
