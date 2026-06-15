@php
    $workspaceUrl = \App\Filament\App\Resources\WorkspaceBookings\WorkspaceBookingResource::getUrl('index', panel: 'app');
    $conferenceUrl = \App\Filament\App\Resources\ConferenceRoomRequests\ConferenceRoomRequestResource::getUrl('index', panel: 'app');

    $workspaceBadgeColor = fn ($status): string => match ($status->value) {
        'active' => 'success',
        'cancelled' => 'danger',
        default => 'gray',
    };

    $conferenceBadgeColor = fn ($status): string => match ($status->value) {
        'pending' => 'warning',
        'approved' => 'success',
        'rejected', 'cancelled' => 'danger',
        default => 'gray',
    };
@endphp

<div class="space-y-6">
    <x-filament::section>
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex min-w-0 items-center gap-4">
                <x-filament-panels::avatar.user :user="$user" loading="lazy" />

                <div class="min-w-0">
                    <h2 class="text-xl font-semibold text-gray-950 dark:text-white">
                        {{ $user->name }}
                    </h2>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $user->position }} · {{ $user->company }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-filament::button
                    :href="$workspaceUrl"
                    tag="a"
                    :icon="\Filament\Support\Icons\Heroicon::OutlinedComputerDesktop"
                >
                    Забронировать место
                </x-filament::button>

                <x-filament::button
                    :href="$conferenceUrl"
                    tag="a"
                    color="gray"
                    :icon="\Filament\Support\Icons\Heroicon::OutlinedBuildingOffice2"
                >
                    Конференц-зал
                </x-filament::button>
            </div>
        </div>

        <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-primary-200 bg-primary-50 px-4 py-3 dark:border-primary-500/30 dark:bg-primary-500/10">
                <p class="text-xs font-medium text-primary-700 dark:text-primary-300">Активные места</p>
                <p class="mt-1 text-2xl font-semibold text-primary-900 dark:text-primary-100">{{ $activeWorkspaceBookings }}</p>
            </div>

            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-500/30 dark:bg-amber-500/10">
                <p class="text-xs font-medium text-amber-700 dark:text-amber-300">Заявки на зал</p>
                <p class="mt-1 text-2xl font-semibold text-amber-900 dark:text-amber-100">{{ $pendingConferenceRequests }}</p>
            </div>

            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-white/10 dark:bg-white/[0.03]">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Рабочие брони</p>
                <p class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ $workspaceBookings->count() }}</p>
            </div>

            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-white/10 dark:bg-white/[0.03]">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Заявки всего</p>
                <p class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ $conferenceRequests->count() }}</p>
            </div>
        </div>
    </x-filament::section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.3fr)_minmax(320px,0.7fr)]">
        <x-filament::section
            heading="Мои бронирования"
            description="Рабочие места и конференц-зал в одном месте."
        >
            <div class="grid gap-5 lg:grid-cols-2">
                <div>
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Рабочие места</h3>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $workspaceBookings->count() }} последних</span>
                    </div>

                    <div class="space-y-3">
                        @forelse ($workspaceBookings as $booking)
                            <article class="rounded-lg border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/[0.03]">
                                <div class="flex flex-col gap-3">
                                    <div class="min-w-0">
                                        <p class="font-medium text-gray-950 dark:text-white">
                                            {{ $booking->workspace?->displayName() ?? 'Место № '.$booking->workspace_number }}
                                        </p>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            {{ $booking->booking_date->format('d.m.Y') }} · {{ $booking->starts_at->format('H:i') }} - {{ $booking->ends_at->format('H:i') }}
                                        </p>
                                    </div>

                                    <div class="w-fit">
                                        <x-filament::badge :color="$workspaceBadgeColor($booking->status)">
                                            {{ $booking->status->label() }}
                                        </x-filament::badge>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                                У вас пока нет бронирований рабочих мест.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div>
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Конференц-зал</h3>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $conferenceRequests->count() }} последних</span>
                    </div>

                    <div class="space-y-3">
                        @forelse ($conferenceRequests as $request)
                            <article class="rounded-lg border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/[0.03]">
                                <div class="flex flex-col gap-3">
                                    <div class="min-w-0">
                                        <p class="font-medium text-gray-950 dark:text-white">{{ $request->purpose }}</p>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            {{ $request->booking_date->format('d.m.Y') }} · {{ $request->starts_at->format('H:i') }} - {{ $request->ends_at->format('H:i') }}
                                        </p>

                                        @if (filled($request->admin_comment))
                                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                                {{ $request->admin_comment }}
                                            </p>
                                        @endif
                                    </div>

                                    <div class="w-fit">
                                        <x-filament::badge :color="$conferenceBadgeColor($request->status)">
                                            {{ $request->status->label() }}
                                        </x-filament::badge>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                                У вас пока нет заявок на конференц-зал.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section
            heading="Данные резидента"
            description="Основная информация аккаунта."
        >
            <div class="grid gap-3 text-sm">
                <div class="flex items-start justify-between gap-4 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-white/10 dark:bg-white/[0.03]">
                    <span class="text-gray-500 dark:text-gray-400">Телефон</span>
                    <span class="text-right font-medium text-gray-950 dark:text-white">{{ $user->phone }}</span>
                </div>

                <div class="flex items-start justify-between gap-4 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-white/10 dark:bg-white/[0.03]">
                    <span class="text-gray-500 dark:text-gray-400">Email</span>
                    <span class="text-right font-medium text-gray-950 dark:text-white">{{ $user->email ?: 'Не указан' }}</span>
                </div>

                <div class="flex items-start justify-between gap-4 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-white/10 dark:bg-white/[0.03]">
                    <span class="text-gray-500 dark:text-gray-400">Компания</span>
                    <span class="text-right font-medium text-gray-950 dark:text-white">{{ $user->company }}</span>
                </div>

                <div class="flex items-start justify-between gap-4 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-white/10 dark:bg-white/[0.03]">
                    <span class="text-gray-500 dark:text-gray-400">Должность</span>
                    <span class="text-right font-medium text-gray-950 dark:text-white">{{ $user->position }}</span>
                </div>
            </div>
        </x-filament::section>
    </div>
</div>
