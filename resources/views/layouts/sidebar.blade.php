@php $items = collect(config('maxport.nav')); @endphp

<aside class="sticky top-0 hidden h-screen w-64 shrink-0 flex-col border-e border-line bg-surface px-5 py-6 lg:flex"
       aria-label="Sidebar navigation">

    <a href="{{ route('dashboard') }}" class="px-1" aria-label="{{ config('app.name') }}">
        <x-application-logo tagline="Export Compliance" />
    </a>

    <a href="{{ route('products.create') }}" class="btn-primary mt-8 w-full">
        <x-icon name="plus" class="h-4 w-4" />
        New Export
    </a>

    <nav class="mt-6 space-y-1">
        @foreach ($items as $item)
            <x-side-link
                :href="$item['route'] ? route($item['route']) : '#'"
                :active="request()->routeIs($item['pattern'])"
                :disabled="! $item['route']"
                :icon="$item['icon']">
                {{ $item['label'] }}
            </x-side-link>
        @endforeach
    </nav>

    <div class="mt-auto border-t border-line pt-4">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="side-link w-full">
                <x-icon name="logout" class="h-5 w-5 shrink-0" />
                <span class="flex-1 text-start">Logout</span>
            </button>
        </form>
    </div>
</aside>
