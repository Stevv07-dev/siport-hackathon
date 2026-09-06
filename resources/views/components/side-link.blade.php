@props(['active' => false, 'disabled' => false, 'icon' => null])

@if ($disabled)
    <span class="side-link cursor-default text-ink-subtle hover:bg-transparent hover:text-ink-subtle"
          aria-disabled="true" title="Coming soon">
        @if ($icon) <x-icon :name="$icon" class="h-5 w-5 shrink-0" /> @endif
        <span class="flex-1">{{ $slot }}</span>
        <span class="rounded-full bg-surface-sunken px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-label">Soon</span>
    </span>
@else
    <a {{ $attributes->merge(['class' => $active ? 'side-link-active' : 'side-link']) }}
       @if($active) aria-current="page" @endif>
        @if ($icon) <x-icon :name="$icon" class="h-5 w-5 shrink-0" /> @endif
        <span class="flex-1">{{ $slot }}</span>
    </a>
@endif
