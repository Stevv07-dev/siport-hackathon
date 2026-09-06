@props(['icon' => 'box', 'title', 'description' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center px-6 py-14 text-center']) }}>
    <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-surface-sunken text-ink-subtle">
        <x-icon :name="$icon" class="h-6 w-6" />
    </span>

    <p class="mt-4 text-sm font-bold text-ink">{{ $title }}</p>

    @if ($description)
        <p class="mt-1 max-w-sm text-sm leading-relaxed text-ink-muted">{{ $description }}</p>
    @endif

    @isset($action)
        <div class="mt-5">{{ $action }}</div>
    @endisset
</div>
