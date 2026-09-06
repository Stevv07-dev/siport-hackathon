@props(['disabled' => false, 'variant' => 'filled', 'invalid' => false])

@php
    $base = match ($variant) {
        'underline' => 'field-underline',
        'outline' => 'field-outline',
        default => 'field-filled',
    };
@endphp

<input
    @disabled($disabled)
    {{ $attributes->merge(['class' => $base . ($invalid ? ' field-error' : '')]) }}
>
