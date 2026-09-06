{{-- Guest variant of layouts/navigation — no sidebar, no user menu, since
     there's no account yet. Shown on the open Product Screening routes. --}}
<header class="sticky top-0 z-40 border-b border-line bg-surface">
    <div class="flex h-16 items-center gap-3 px-4 sm:px-6">
        <a href="{{ route('home') }}" class="shrink-0" aria-label="{{ config('app.name') }}">
            <x-application-logo size="sm" />
        </a>

        <div class="ms-auto flex items-center gap-2">
            <a href="{{ route('login') }}" class="btn-ghost !py-2">Log In</a>
            <a href="{{ route('register') }}" class="btn-primary !py-2">Sign Up</a>
        </div>
    </div>
</header>
