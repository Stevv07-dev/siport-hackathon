@props(['title' => null, 'padding' => 'p-6'])

<section {{ $attributes->merge(['class' => 'card']) }}>
    @if ($title || isset($actions))
        <div class="flex items-center justify-between gap-4 border-b border-line px-6 py-4">
            <h2 class="text-base font-bold text-ink">{{ $title }}</h2>
            @isset($actions)
                <div class="shrink-0 text-sm">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div class="{{ $padding }}">
        {{ $slot }}
    </div>
</section>
