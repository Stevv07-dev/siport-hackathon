@props(['user' => null, 'size' => 'md'])

@php
    $user = $user ?? auth()->user();

    $initials = collect(preg_split('/\s+/', trim((string) ($user?->name ?? '?'))))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');

    $dimensions = match ($size) {
        'sm' => 'h-8 w-8 text-xs',
        'lg' => 'h-16 w-16 text-lg',
        default => 'h-10 w-10 text-sm',
    };
@endphp

<span {{ $attributes->merge(['class' => $dimensions . ' inline-flex shrink-0 items-center justify-center rounded-full bg-brand-600 font-bold text-white']) }}>
    {{ $initials !== '' ? $initials : '?' }}
</span>
