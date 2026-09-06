@props(['hero' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="bg-surface font-sans text-ink antialiased">
        <div class="flex min-h-screen flex-col lg:flex-row">
            {{-- Panel kiri: cerita produk. Disembunyikan di layar kecil agar form tetap fokus. --}}
            <aside class="relative hidden overflow-hidden bg-gradient-to-br from-hero via-hero to-hero-deep lg:flex lg:w-1/2 lg:flex-col lg:justify-center lg:px-16 xl:px-24">
                <div class="pointer-events-none absolute -right-24 -top-24 h-80 w-80 rounded-full bg-white/25 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-32 -left-16 h-96 w-96 rounded-full bg-brand-600/10 blur-3xl"></div>

                <div class="relative max-w-lg">
                    {{ $hero }}
                </div>
            </aside>

            {{-- Panel kanan: form autentikasi. --}}
            <main class="flex flex-1 items-center justify-center px-6 py-12 sm:px-10 lg:w-1/2">
                <div class="w-full max-w-[420px]">
                    <div class="mb-10 lg:hidden">
                        <a href="{{ route('home') }}" aria-label="{{ config('app.name') }}">
                            <x-application-logo />
                        </a>
                    </div>

                    {{ $slot }}
                </div>
            </main>
        </div>
    </body>
</html>
