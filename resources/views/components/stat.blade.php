@props(['label', 'value', 'icon' => null])

<div {{ $attributes->merge(['class' => 'flex items-center gap-4 px-6 py-5']) }}>
    @if ($icon)
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-surface-sunken text-ink-muted">
            <x-icon :name="$icon" />
        </span>
    @endif

    <div class="min-w-0">
        <p class="text-xs font-bold uppercase tracking-label text-ink-muted">{{ $label }}</p>
        <p class="mt-0.5 text-2xl font-extrabold tracking-tight text-ink">{{ $value }}</p>
    </div>
</div>
