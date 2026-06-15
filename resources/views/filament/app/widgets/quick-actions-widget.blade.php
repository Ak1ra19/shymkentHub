<x-filament-widgets::widget>
    <x-filament::section
        heading="Быстрые действия"
        description="Короткий вход в основные сценарии дня."
    >
        <div class="space-y-4">
            <div class="grid gap-3">
                <x-filament::button
                    tag="a"
                    :href="$workspaceUrl"
                    color="warning"
                    icon="heroicon-o-computer-desktop"
                    icon-position="before"
                    size="lg"
                >
                    Забронировать место
                </x-filament::button>

                <x-filament::button
                    tag="a"
                    :href="$conferenceUrl"
                    color="gray"
                    icon="heroicon-o-building-office-2"
                    icon-position="before"
                    size="lg"
                >
                    Заявка на зал
                </x-filament::button>

                <x-filament::button
                    tag="a"
                    :href="$residentInstructionsUrl"
                    color="gray"
                    outlined
                    icon="heroicon-o-document-text"
                    icon-position="before"
                >
                    Инструкция резидента
                </x-filament::button>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                <article class="rounded-xl border border-gray-200/80 bg-gray-50/80 p-4 dark:border-white/10 dark:bg-white/[0.04]">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Свободно сейчас
                            </p>
                            <p class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">
                                {{ $availableWorkspacesNow }}
                                <span class="text-base font-medium text-gray-500 dark:text-gray-400">/ {{ $activeWorkspaces }}</span>
                            </p>
                        </div>

                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                            Места
                        </span>
                    </div>

                    <p class="mt-3 text-sm leading-6 text-gray-600 dark:text-gray-300">
                        Доступно в текущий момент без пересечения по времени.
                    </p>
                </article>

                <article class="rounded-xl border border-gray-200/80 bg-white p-4 dark:border-white/10 dark:bg-white/[0.03]">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Ближайший зал
                            </p>
                            <p class="mt-2 text-lg font-semibold text-gray-950 dark:text-white">
                                {{ $nextConferenceSlot ?? 'Свободного часа сегодня нет' }}
                            </p>
                        </div>

                        <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-medium text-sky-700 dark:bg-sky-500/15 dark:text-sky-300">
                            Зал
                        </span>
                    </div>

                    <p class="mt-3 text-sm leading-6 text-gray-600 dark:text-gray-300">
                        Ближайшее свободное окно конференц-зала на сегодня.
                    </p>
                </article>
            </div>

        </div>
    </x-filament::section>
</x-filament-widgets::widget>
