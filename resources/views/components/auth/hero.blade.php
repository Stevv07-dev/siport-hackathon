@props(['heading', 'description' => null])

{{-- Ringkas hero untuk halaman auth sekunder (lupa/atur ulang/verifikasi kata sandi). --}}
<x-application-logo size="lg" class="mb-10" />

<h1 class="text-4xl font-extrabold leading-[1.12] tracking-tight text-ink xl:text-5xl">
    {!! $heading !!}
</h1>

@if ($description)
    <p class="mt-6 max-w-md text-base leading-relaxed text-ink-muted">{{ $description }}</p>
@endif

<p class="mt-12 text-xs font-bold uppercase tracking-label text-ink">Screen · Verify · Guide · Export</p>
