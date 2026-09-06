@props(['showText' => true, 'tagline' => null, 'size' => 'md'])

@php
    // Tile logo dirender dari PNG di public/logo (master SVG: maxport-mark.svg).
    $mark = match ($size) {
        'sm' => ['class' => 'h-8 w-8', 'px' => 32],
        'lg' => ['class' => 'h-11 w-11', 'px' => 44],
        default => ['class' => 'h-10 w-10', 'px' => 40],
    };

    $word = $size === 'sm' ? 'text-lg' : ($size === 'lg' ? 'text-2xl' : 'text-xl');
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <img src="{{ asset('logo/maxport-mark-192.png') }}"
         alt="{{ $showText ? '' : config('app.name', 'MAXPORT') }}"
         width="{{ $mark['px'] }}" height="{{ $mark['px'] }}"
         {{-- Sudut membulat sudah menyatu di dalam PNG, jangan di-clip lagi. --}}
         class="{{ $mark['class'] }} shrink-0"
         @if ($showText) aria-hidden="true" @endif>

    @if ($showText)
        <span class="leading-tight">
            <span class="{{ $word }} block font-extrabold tracking-tight text-ink">MAXPORT</span>
            @if ($tagline)
                <span class="block text-xs font-medium text-ink-muted">{{ $tagline }}</span>
            @endif
        </span>
    @endif
</span>
