<x-app-layout>
    <x-slot:title>Dashboard</x-slot:title>

    {{-- Sapaan --}}
    <x-card padding="px-8 py-10">
        <p class="text-xs font-bold uppercase tracking-label text-brand-600">
            Maksimalkan ekspor anda, minimalkan risiko nya
        </p>

        <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">
            Welcome back, {{ Str::before(Auth::user()->name, ' ') }}
        </h1>

        <p class="mt-3 max-w-xl text-base leading-relaxed text-ink-muted">
            MAXPORT helps you check the export readiness of your electronic products for Singapore —
            from specification screening to the list of documents you need to prepare.
        </p>

        <div class="mt-7 flex flex-wrap gap-3">
            <a href="{{ route('products.create') }}" class="btn-primary">Start Screening</a>
            <span class="btn-secondary cursor-not-allowed opacity-60" aria-disabled="true">Export Report</span>
        </div>
    </x-card>

    {{-- Ringkasan angka --}}
    <div class="card grid grid-cols-1 divide-y divide-line sm:grid-cols-3 sm:divide-x sm:divide-y-0">
        <x-stat icon="truck" label="Total Exports" :value="$stats['total']" />
        <x-stat icon="clock-list" label="Pending" :value="$stats['pending']" />
        <x-stat icon="file-check" label="Candidate Found" :value="$stats['approved']" />
    </div>

    {{-- Aktivitas terakhir --}}
    <x-card title="Recent Activity" padding="p-0">
        <x-slot:actions>
            <a href="{{ route('history.index') }}" class="text-brand-600 hover:text-brand-700">View All</a>
        </x-slot:actions>

        @if ($activities->isEmpty())
            <x-empty-state
                icon="box"
                title="No export activity yet"
                description="Start a screening to check a product's export readiness — it'll show up here, and you can pick it back up any time.">
                <x-slot:action>
                    <a href="{{ route('products.create') }}" class="btn-primary">Start Screening</a>
                </x-slot:action>
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-line text-left text-xs font-bold uppercase tracking-label text-ink-muted">
                            <th scope="col" class="px-6 py-3">Product</th>
                            <th scope="col" class="px-6 py-3">Category</th>
                            <th scope="col" class="px-6 py-3">Updated</th>
                            <th scope="col" class="px-6 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($activities as $activity)
                            <tr class="cursor-pointer transition hover:bg-surface"
                                onclick="window.location='{{ route('products.show', $activity['session']) }}'">
                                <td class="px-6 py-4 font-medium text-ink">{{ $activity['batch'] }}</td>
                                <td class="px-6 py-4 text-ink-muted">{{ $activity['destination'] }}</td>
                                <td class="px-6 py-4 text-ink-muted">{{ $activity['date'] }}</td>
                                <td class="px-6 py-4">
                                    <x-badge :tone="$activity['tone']">{{ $activity['status'] }}</x-badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</x-app-layout>
