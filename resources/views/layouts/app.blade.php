<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-canvas font-sans text-ink antialiased">
        <div class="flex min-h-screen">
            {{-- Guests can reach this layout via the open Product Screening
                 routes (see routes/web.php) — the sidebar assumes a logged-in
                 user, so it's authenticated-only. --}}
            @auth
                @include('layouts.sidebar')
            @endauth

            <div class="flex min-w-0 flex-1 flex-col">
                @auth
                    @include('layouts.navigation')
                @else
                    @include('layouts.navigation-guest')
                @endauth

                @isset($header)
                    <div class="border-b border-line bg-surface px-4 py-5 sm:px-8">
                        {{ $header }}
                    </div>
                @endisset

                <main class="flex-1 px-4 py-6 sm:px-8 sm:py-8">
                    <div class="mx-auto max-w-5xl space-y-6">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
