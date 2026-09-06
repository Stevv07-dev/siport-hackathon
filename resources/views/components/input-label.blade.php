@props(['value' => null, 'variant' => 'caps'])

<label {{ $attributes->merge(['class' => $variant === 'caps' ? 'field-label' : 'field-label-plain']) }}>
    {{ $value ?? $slot }}
</label>
