@php $items = collect(config('maxport.nav')); @endphp

<header x-data="{ mobileMenu: false }" class="sticky top-0 z-40 border-b border-line bg-surface">
    <div class="flex h-16 items-center gap-3 px-4 sm:px-6">
        {{-- Brand only on small screens; on large screens it lives in the sidebar. --}}
        <a href="{{ route('dashboard') }}" class="shrink-0 lg:hidden" aria-label="{{ config('app.name') }}">
            <x-application-logo :show-text="false" size="sm" />
        </a>

        {{-- Search --}}
        <label class="relative hidden max-w-md flex-1 sm:block">
            <span class="sr-only">Search</span>
            <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-ink-subtle">
                <x-icon name="search" class="h-4 w-4" />
            </span>
            <input type="search" placeholder="Search products, HS codes..."
                   class="w-full rounded-full border border-line bg-surface-sunken py-2.5 pl-11 pr-4 text-sm text-ink placeholder:text-ink-subtle focus:border-brand-600 focus:bg-white focus:ring-2 focus:ring-brand-200">
        </label>

        <div class="ms-auto flex items-center gap-1 sm:gap-2">
            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button type="button" class="rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">
                        <x-user-avatar />
                        <span class="sr-only">{{ Auth::user()->name }}</span>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <div class="border-b border-line bg-surface px-4 py-3">
                        <p class="truncate text-sm font-semibold text-ink">{{ Auth::user()->name }}</p>
                        <p class="truncate text-xs text-ink-muted">{{ Auth::user()->email }}</p>
                    </div>

                    <x-dropdown-link :href="route('profile.edit')">Profile</x-dropdown-link>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-dropdown-link :href="route('logout')"
                                         onclick="event.preventDefault(); this.closest('form').submit();">
                            Log Out
                        </x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>

            <button type="button" x-on:click="mobileMenu = ! mobileMenu"
                    class="btn-ghost !px-2.5 lg:hidden" aria-label="Open menu">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round">
                    <path x-show="! mobileMenu" d="M4 7h16M4 12h16M4 17h16" />
                    <path x-show="mobileMenu" x-cloak d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Mobile navigation --}}
    <div x-show="mobileMenu" x-cloak class="border-t border-line bg-surface px-4 py-4 lg:hidden">
        <nav class="space-y-1" aria-label="Mobile navigation">
            @foreach ($items as $item)
                <x-side-link
                    :href="$item['route'] ? route($item['route']) : '#'"
                    :active="request()->routeIs($item['pattern'])"
                    :disabled="! $item['route']"
                    :icon="$item['icon']">
                    {{ $item['label'] }}
                </x-side-link>
            @endforeach

            <form method="POST" action="{{ route('logout') }}" class="pt-2">
                @csrf
                <button type="submit" class="side-link w-full">
                    <x-icon name="logout" class="h-5 w-5 shrink-0" />
                    <span class="flex-1 text-start">Logout</span>
                </button>
            </form>
        </nav>
    </div>
</header>
