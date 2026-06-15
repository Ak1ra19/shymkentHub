@if (filament()->auth()->check())
    @php
        $user = filament()->auth()->user();
        $profileUrl = \App\Filament\App\Pages\Auth\EditProfile::getUrl(panel: 'app');
        $isActive = request()->url() === $profileUrl;
        $isSidebarCollapsibleOnDesktop = filament()->isSidebarCollapsibleOnDesktop();
    @endphp

    <div
        @if ($isSidebarCollapsibleOnDesktop)
            x-show="$store.sidebar.isOpen"
        @endif
        class="px-4 pb-4"
    >
        <div class="rounded-lg border border-gray-200 bg-white p-2 shadow-sm dark:border-white/10 dark:bg-white/[0.03]">
            <a
                href="{{ $profileUrl }}"
                @class([
                    'flex items-center gap-3 rounded-md px-2.5 py-2 transition',
                    'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-200' => $isActive,
                    'text-gray-700 hover:bg-gray-50 hover:text-gray-950 dark:text-gray-200 dark:hover:bg-white/[0.05] dark:hover:text-white' => ! $isActive,
                ])
            >
                <x-filament-panels::avatar.user :user="$user" loading="lazy" />

                <span class="min-w-0">
                    <span class="block text-sm font-medium">Мой профиль</span>
                    <span class="block truncate text-xs text-gray-500 dark:text-gray-400">
                        {{ filament()->getUserName($user) }}
                    </span>
                </span>
            </a>

            <form action="{{ filament()->getLogoutUrl() }}" method="post" class="mt-1">
                @csrf

                <button
                    type="submit"
                    class="flex w-full items-center rounded-md px-2.5 py-2 text-sm text-gray-500 transition hover:bg-gray-50 hover:text-gray-950 dark:text-gray-400 dark:hover:bg-white/[0.05] dark:hover:text-white"
                >
                    Выйти
                </button>
            </form>
        </div>
    </div>
@endif
