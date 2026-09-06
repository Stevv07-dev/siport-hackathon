@props(['value', 'label', 'variant' => 'plain'])

@if ($variant === 'card')
    <div class="flex-1 rounded-xl border border-white/40 bg-white/25 px-5 py-4 backdrop-blur-sm">
        <p class="text-2xl font-extrabold tracking-tight text-ink">{{ $value }}</p>
        <p class="mt-1 text-sm text-ink-muted">{{ $label }}</p>
    </div>
@else
    <div>
        <p class="text-2xl font-extrabold tracking-tight text-ink">{{ $value }}</p>
        <p class="mt-1 text-xs font-bold uppercase tracking-label text-ink">{{ $label }}</p>
    </div>
@endif
