@props([
    'name' => 'password',
    'variant' => 'filled',
    'toggle' => true,
    'icon' => 'lock',
    'invalid' => false,
])

@php
    $base = match ($variant) {
        'underline' => 'field-underline',
        'outline' => 'field-outline',
        default => 'field-filled',
    };

    // Kelas ditulis utuh (bukan hasil interpolasi) supaya terbaca oleh Tailwind JIT.
    $padding = $variant === 'underline' ? 'pr-8' : 'pr-12';
    $togglePos = $variant === 'underline' ? 'right-0' : 'right-4';

    $inputClass = trim($base . ' ' . $padding . ($invalid ? ' field-error' : ''));
@endphp

@if ($toggle)
    <div x-data="{ show: false }" class="relative">
        <input
            type="password"
            x-bind:type="show ? 'text' : 'password'"
            {{ $attributes->merge(['class' => $inputClass, 'name' => $name, 'id' => $name]) }}
        >

        <button type="button"
                x-on:click="show = ! show"
                class="absolute inset-y-0 {{ $togglePos }} flex items-center text-ink-subtle transition hover:text-ink focus:outline-none focus-visible:text-ink"
                x-bind:aria-label="show ? 'Hide password' : 'Show password'"
                aria-label="Show password">
            <x-icon name="eye" x-show="! show" class="h-5 w-5" />
            <x-icon name="eye-off" x-show="show" x-cloak class="h-5 w-5" />
        </button>
    </div>
@else
    <x-form.control :icon="$icon">
        <input type="password"
               {{ $attributes->merge(['class' => $inputClass, 'name' => $name, 'id' => $name]) }}>
    </x-form.control>
@endif
