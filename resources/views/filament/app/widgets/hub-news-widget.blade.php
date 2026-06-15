<x-filament-widgets::widget>
    <x-filament::section
        heading="Сегодня в ShymkentHub"
        description="Ближайшие мероприятия и анонсы для резидентов."
    >
        @php
            $formatEventDate = function ($date): string {
                if ($date->isToday()) {
                    return 'Сегодня';
                }

                if ($date->isTomorrow()) {
                    return 'Завтра';
                }

                return $date->format('d.m.Y');
            };
        @endphp

        @if ($headlineEvent)
            <div class="space-y-4">
                <article class="grid overflow-hidden rounded-xl border border-gray-200/80 bg-gray-50/70 dark:border-white/10 dark:bg-white/[0.04] md:grid-cols-[minmax(15rem,0.72fr)_minmax(0,1fr)]">
                    @if ($headlineEvent->banner_url)
                        <img
                            src="{{ $headlineEvent->banner_url }}"
                            alt="{{ $headlineEvent->title }}"
                            class="h-full min-h-64 w-full object-cover"
                        />
                    @endif

                    <div class="flex flex-col p-6">
                        <div class="flex flex-wrap items-center gap-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                                Главное событие
                            </span>
                            <span class="rounded-full bg-gray-200 px-2.5 py-1 text-gray-700 dark:bg-white/8 dark:text-gray-300">
                                {{ $formatEventDate($headlineEvent->event_date) }}
                            </span>
                            <span>{{ $headlineEvent->event_time->format('H:i') }}</span>
                        </div>

                        <h3 class="mt-5 text-2xl font-semibold text-gray-950 dark:text-white">
                            {{ $headlineEvent->title }}
                        </h3>

                        <p class="mt-3 max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-300">
                            {{ \Illuminate\Support\Str::limit((string) $headlineEvent->description, 180) }}
                        </p>

                        <div class="mt-auto grid gap-3 pt-6 sm:grid-cols-2">
                            <div class="rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-white/[0.03]">
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Когда
                                </p>
                                <p class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">
                                    {{ $headlineEvent->event_date->format('d.m.Y') }} в {{ $headlineEvent->event_time->format('H:i') }}
                                </p>
                            </div>

                            <div class="rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-white/[0.03]">
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Дальше по плану
                                </p>
                                <p class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">
                                    {{ $events->count() > 0 ? 'Еще '.$events->count().' события в расписании' : 'Пока без следующих анонсов' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </article>

                <div>
                    <div class="flex items-center justify-between gap-3">
                        <h4 class="text-sm font-semibold text-gray-950 dark:text-white">
                            Ближайшие мероприятия
                        </h4>

                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $events->count() }} впереди
                        </span>
                    </div>

                    <div class="mt-3 grid gap-3 md:grid-cols-2">
                        @forelse ($events as $event)
                            <article class="overflow-hidden rounded-lg border border-gray-200/80 bg-white dark:border-white/10 dark:bg-white/[0.03]">
                                <div class="flex gap-3 p-3">
                                    @if ($event->banner_url)
                                        <img
                                            src="{{ $event->banner_url }}"
                                            alt="{{ $event->title }}"
                                            class="h-20 w-24 shrink-0 rounded-md object-cover"
                                        />
                                    @else
                                        <div class="h-20 w-24 shrink-0 rounded-md bg-gray-100 dark:bg-white/8"></div>
                                    @endif

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                                            <span>{{ $formatEventDate($event->event_date) }}</span>
                                            <span>{{ $event->event_time->format('H:i') }}</span>
                                        </div>

                                        <h4 class="mt-1 text-sm font-semibold leading-5 text-gray-950 dark:text-white">
                                            {{ \Illuminate\Support\Str::limit($event->title, 58) }}
                                        </h4>

                                        <p class="mt-1 text-xs leading-5 text-gray-600 dark:text-gray-300">
                                            {{ \Illuminate\Support\Str::limit((string) $event->description, 78) }}
                                        </p>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <article class="rounded-lg border border-dashed border-gray-300 bg-gray-50/80 p-4 text-sm text-gray-500 dark:border-white/10 dark:bg-white/[0.03] dark:text-gray-400">
                                После ближайшего события в расписании пока пусто.
                            </article>
                        @endforelse
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50/80 p-6 text-sm text-gray-500 dark:border-white/10 dark:bg-white/[0.03] dark:text-gray-400">
                Администратор пока не добавил ближайшие мероприятия.
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
