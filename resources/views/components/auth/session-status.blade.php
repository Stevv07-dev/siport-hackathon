@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'mb-6 flex items-start gap-2.5 rounded-lg border border-success-500/25 bg-success-50 px-4 py-3 text-sm font-medium text-success-700']) }}>
        <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.58 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd" />
        </svg>
        <span>{{ $status }}</span>
    </div>
@endif
