{{-- Blok "or continue with" — ikut hilang bila tidak ada provider yang aktif. --}}
@if (config('services.google.client_id'))
    <div class="mt-8 space-y-5">
        <p class="divider-label">Or continue with</p>

        <x-auth.google-button />
    </div>
@endif
