@props(['active' => false])

@php
    $classes = $active
        ? 'block w-full rounded-lg bg-brand-600 px-4 py-2.5 text-start text-sm font-semibold text-white'
        : 'block w-full rounded-lg px-4 py-2.5 text-start text-sm font-medium text-ink-muted transition hover:bg-surface-sunken hover:text-ink';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
