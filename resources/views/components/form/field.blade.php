@props([
    'name',
    'label' => null,
    'labelVariant' => 'caps',
    'errorKey' => null,
])

@php $errorKey = $errorKey ?? $name; @endphp

<div {{ $attributes->merge(['class' => 'space-y-2']) }}>
    @if ($label)
        <div class="flex items-baseline justify-between gap-4">
            <x-input-label :for="$name" :value="$label" :variant="$labelVariant" />
            @isset($action)
                <div class="text-xs font-semibold">{{ $action }}</div>
            @endisset
        </div>
    @endif

    {{ $slot }}

    <x-input-error :messages="$errors->get($errorKey)" />
</div>
