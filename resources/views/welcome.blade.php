<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', [
            'title' => 'Maksimalkan ekspor anda, minimalkan risiko nya',
            'description' => 'MAXPORT helps first-time Indonesian exporters check their product specifications and prepare the documents needed to export electronics to Singapore.',
        ])
    </head>
    <body class="bg-surface font-sans text-ink antialiased">
        <header class="sticky top-0 z-40 border-b border-line bg-surface/95 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-6 px-6">
                <a href="{{ route('home') }}" aria-label="{{ config('app.name') }}">
                    <x-application-logo size="sm" />
                </a>

                <nav class="hidden items-center gap-8 text-sm font-medium md:flex" aria-label="Main navigation">
                    <a href="#home" class="text-ink transition hover:text-brand-600">Home</a>
                    <a href="#how-it-works" class="text-ink-muted transition hover:text-brand-600">How It Works</a>
                    <a href="#services" class="text-ink-muted transition hover:text-brand-600">Services</a>
                    <a href="#about" class="text-ink-muted transition hover:text-brand-600">About</a>
                </nav>

                <div class="flex items-center gap-2">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-primary !py-2">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn-ghost !py-2">Log In</a>
                        <a href="{{ route('register') }}" class="btn-primary !py-2">Sign Up</a>
                    @endauth
                </div>
            </div>
        </header>

        <main>
            {{-- Hero --}}
            <section id="home" class="mx-auto max-w-6xl px-6 py-20 lg:py-28">
                <div class="grid items-center gap-14 lg:grid-cols-2">
                    <div>
                        <h1 class="text-4xl font-extrabold leading-[1.1] tracking-tight text-ink-navy sm:text-5xl">
                            Maksimalkan ekspor anda,<br>minimalkan risiko nya
                        </h1>

                        <p class="mt-6 max-w-lg text-lg leading-relaxed text-ink-muted">
                            Check your product specifications against export requirements, then get the list of
                            documents you need to prepare — all in one flow.
                        </p>

                        <div class="mt-9 flex flex-wrap gap-3">
                            <a href="{{ route('products.create') }}" class="btn-primary !px-7 !py-3">
                                Try It Now — No Account Needed
                                <x-icon name="arrow-right" class="h-4 w-4" />
                            </a>
                            <a href="#how-it-works" class="btn-outline !px-7 !py-3">See How It Works</a>
                        </div>

                        <div class="mt-10 flex items-center gap-4">
                            <div class="flex -space-x-2">
                                @foreach (['bg-brand-600', 'bg-brand-400', 'bg-ink-subtle'] as $tone)
                                    <span class="h-9 w-9 rounded-full border-2 border-surface {{ $tone }}"></span>
                                @endforeach
                                <span class="flex h-9 items-center rounded-full border-2 border-surface bg-surface-field px-2.5 text-xs font-bold text-ink-muted">500+</span>
                            </div>
                            <p class="text-sm text-ink-muted">Try the screening free — sign up only when you're ready to see your result</p>
                        </div>
                    </div>

                    {{-- Ringkasan hasil verifikasi — memperkenalkan bahasa warna aplikasi. --}}
                    <div class="card overflow-hidden">
                        <div class="border-b border-line bg-surface px-6 py-4">
                            <p class="text-xs font-bold uppercase tracking-label text-ink-muted">Export Readiness Check</p>
                            <p class="mt-1 text-sm text-ink">Insulated Wires / Cables → Singapore</p>
                        </div>

                        <div class="space-y-3 px-6 py-6">
                            <div class="alert-success !rounded-xl !px-4 !py-3">
                                <x-icon name="shield-check" class="mt-0.5 h-5 w-5 shrink-0" />
                                <div>
                                    <p class="text-sm font-bold">Specifications match</p>
                                    <p class="mt-0.5 text-sm">Material, rating, and voltage meet the requirements.</p>
                                </div>
                            </div>

                            <div class="alert-danger !rounded-xl !px-4 !py-3">
                                <x-icon name="alert-triangle" class="mt-0.5 h-5 w-5 shrink-0" />
                                <div>
                                    <p class="text-sm font-bold">HS code needs review</p>
                                    <p class="mt-0.5 text-sm">The product description does not match the code you entered.</p>
                                </div>
                            </div>

                            <div class="alert-info !rounded-xl !px-4 !py-3">
                                <x-icon name="file-check" class="mt-0.5 h-5 w-5 shrink-0" />
                                <div>
                                    <p class="text-sm font-bold">4 documents to prepare</p>
                                    <p class="mt-0.5 text-sm">Invoice, packing list, certificate of origin, export declaration.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Cara kerja --}}
            <section id="how-it-works" class="border-y border-line bg-canvas">
                <div class="mx-auto max-w-6xl px-6 py-20">
                    <h2 class="text-center text-3xl font-extrabold tracking-tight text-ink-navy">How It Works</h2>
                    <p class="mx-auto mt-3 max-w-xl text-center text-ink-muted">
                        A streamlined verification process so your exports run smoothly.
                    </p>

                    <ol class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ([
                            ['Product Input', 'Enter the basic details of the product you want to export.'],
                            ['Detailed Specifications', 'Complete the technical parameters for your product group.'],
                            ['Automatic Verification', 'The system compares your data against the requirements.'],
                            ['Results & Documents', 'Receive the findings and the list of documents to prepare.'],
                        ] as $index => [$stepTitle, $stepText])
                            <li class="card p-6 text-center">
                                <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white">
                                    {{ $index + 1 }}
                                </span>
                                <h3 class="mt-4 text-base font-bold text-ink">{{ $stepTitle }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-ink-muted">{{ $stepText }}</p>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>

            {{-- Kenapa MAXPORT --}}
            <section class="mx-auto max-w-6xl px-6 py-20">
                <h2 class="text-center text-3xl font-extrabold tracking-tight text-ink-navy">Why MAXPORT?</h2>
                <p class="mx-auto mt-3 max-w-xl text-center text-ink-muted">
                    Bringing together information that used to be scattered across many sources.
                </p>

                <div class="mt-12 grid gap-5 md:grid-cols-3">
                    @foreach ([
                        ['history', 'Centralized Requirements', 'No more tracking down regulations from one source after another.'],
                        ['shield-check', 'Early Detection', 'Specification mismatches surface before the export process begins.'],
                        ['file-check', 'Document Guidance', 'A list of documents and what each one is for, matched to your product.'],
                    ] as [$featureIcon, $featureTitle, $featureText])
                        <div class="card p-7">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                                <x-icon :name="$featureIcon" />
                            </span>
                            <h3 class="mt-5 text-lg font-bold text-ink">{{ $featureTitle }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-ink-muted">{{ $featureText }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Kelompok produk --}}
            <section id="services" class="border-y border-line bg-canvas">
                <div class="mx-auto max-w-6xl px-6 py-20">
                    <h2 class="text-3xl font-extrabold tracking-tight text-ink-navy">Initial Product Groups</h2>
                    <p class="mt-3 max-w-2xl text-ink-muted">
                        The MAXPORT MVP focuses on the electrical/electronics sector within Indonesia–Singapore trade.
                    </p>

                    <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ([
                            ['Electrical Machines', 'Electrical machines and apparatus.'],
                            ['Telecommunications', 'Telecommunications equipment.'],
                            ['Semiconductor Devices', 'Semiconductor components.'],
                            ['Electrical Capacitors', 'Electrical capacitors.'],
                            ['Insulated Wires / Cables', 'Insulated wires and cables.'],
                        ] as [$groupName, $groupDesc])
                            <div class="card flex items-start gap-4 p-5">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                                    <x-icon name="box" class="h-5 w-5" />
                                </span>
                                <div>
                                    <p class="text-sm font-bold text-ink">{{ $groupName }}</p>
                                    <p class="mt-0.5 text-sm text-ink-muted">{{ $groupDesc }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- Tentang --}}
            <section id="about" class="mx-auto max-w-3xl px-6 py-20 text-center">
                <h2 class="text-3xl font-extrabold tracking-tight text-ink-navy">About MAXPORT</h2>
                <p class="mt-5 text-base leading-relaxed text-ink-muted">
                    MAXPORT is a <strong class="font-semibold text-ink">first-line export readiness assistant</strong>
                    for Indonesian entrepreneurs and first-time exporters. It does not replace export consultants
                    or regulators — its job is to help you understand the state of your product before you step
                    into the real export process.
                </p>

                <a href="{{ route('products.create') }}" class="btn-primary mt-9 !px-7 !py-3">Try the Screening Free</a>
            </section>
        </main>

        <footer class="border-t border-line bg-canvas">
            <div class="mx-auto max-w-6xl px-6 py-14">
                <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="lg:col-span-2">
                        <x-application-logo size="sm" />
                        <p class="mt-4 max-w-sm text-sm leading-relaxed text-ink-muted">
                            An export readiness assistant that helps make sure your product specifications and
                            documents are ready before you ship.
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-bold text-ink">Product</p>
                        <ul class="mt-4 space-y-2 text-sm text-ink-muted">
                            <li><a href="#how-it-works" class="transition hover:text-brand-600">How It Works</a></li>
                            <li><a href="#services" class="transition hover:text-brand-600">Product Groups</a></li>
                            <li><a href="#about" class="transition hover:text-brand-600">About</a></li>
                        </ul>
                    </div>

                    <div>
                        <p class="text-sm font-bold text-ink">Account</p>
                        <ul class="mt-4 space-y-2 text-sm text-ink-muted">
                            <li><a href="{{ route('login') }}" class="transition hover:text-brand-600">Log In</a></li>
                            <li><a href="{{ route('register') }}" class="transition hover:text-brand-600">Sign Up</a></li>
                        </ul>
                    </div>
                </div>

                <div class="mt-12 flex flex-col gap-3 border-t border-line pt-8 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-ink-subtle">&copy; {{ date('Y') }} MAXPORT. Hackathon prototype.</p>
                    <p class="text-xs text-ink-subtle">Screening results are not an official regulator decision.</p>
                </div>
            </div>
        </footer>
    </body>
</html>
