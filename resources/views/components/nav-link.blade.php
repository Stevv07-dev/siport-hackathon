@props(['active' => false, 'disabled' => false])

@php
    $classes = $active
        ? 'relative inline-flex items-center gap-2 py-1 text-sm font-semibold text-ink after:absolute after:-bottom-1.5 after:left-0 after:h-0.5 after:w-full after:rounded-full after:bg-ink'
        : 'relative inline-flex items-center gap-2 py-1 text-sm font-medium text-ink-muted transition hover:text-ink';
@endphp

@if ($disabled)
    <span class="relative inline-flex cursor-default items-center gap-2 py-1 text-sm font-medium text-ink-subtle"
          aria-disabled="true" title="Coming soon">
        {{ $slot }}
        <span class="rounded-full bg-surface-sunken px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-label text-ink-subtle">Soon</span>
    </span>
@else
    <a {{ $attributes->merge(['class' => $classes]) }} @if($active) aria-current="page" @endif>
        {{ $slot }}
    </a>
@endif
