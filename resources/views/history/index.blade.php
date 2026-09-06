<x-app-layout>
    <x-slot:title>History</x-slot:title>

    <x-slot:header>
        <div class="mx-auto flex max-w-5xl items-center gap-3">
            <a href="{{ route('dashboard') }}" class="btn-ghost !px-2" aria-label="Back">
                <x-icon name="arrow-left" />
            </a>
            <h1 class="text-lg font-bold tracking-tight text-ink">Export History</h1>
        </div>
    </x-slot:header>

    <x-card title="All Screenings" padding="p-0">
        <x-slot:actions>
            <a href="{{ route('products.create') }}" class="font-semibold text-brand-600 hover:text-brand-700">New Export</a>
        </x-slot:actions>

        @if ($sessions->isEmpty())
            <x-empty-state
                icon="history"
                title="No screenings yet"
                description="Start a new export to see it show up here — you can pick it back up any time, even after signing out.">
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
                            <th scope="col" class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($sessions as $session)
                            <tr class="transition hover:bg-surface">
                                <td class="px-6 py-4 font-medium text-ink">{{ $session->material }}</td>
                                <td class="px-6 py-4 text-ink-muted">{{ $session->category_name }}</td>
                                <td class="px-6 py-4 text-ink-muted">{{ $session->updated_at->diffForHumans() }}</td>
                                <td class="px-6 py-4">
                                    @if (! $session->isCompleted())
                                        <x-badge tone="warning">In Progress</x-badge>
                                    @elseif ($session->passed())
                                        <x-badge tone="success">Candidate Found</x-badge>
                                    @else
                                        <x-badge tone="danger">Needs Review</x-badge>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('products.show', $session) }}" class="font-semibold text-brand-600 hover:text-brand-700">
                                        {{ $session->isCompleted() ? 'View Result' : 'Resume' }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</x-app-layout>
