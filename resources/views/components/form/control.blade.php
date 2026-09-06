@props(['icon' => null])

{{-- Membungkus input agar ikon di sisi kanan sejajar di semua varian field. --}}
<div {{ $attributes->merge(['class' => 'relative']) }}>
    {{ $slot }}

    @if ($icon)
        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-ink-subtle">
            <x-icon :name="$icon" class="h-5 w-5" />
        </span>
    @endif
</div>
