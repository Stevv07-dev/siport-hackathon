<x-guest-layout>
    <x-slot:title>Verify Email</x-slot:title>

    <x-slot:hero>
        <x-auth.hero heading="Check Your<br>Inbox."
                     description="Verifying your email makes sure screening results and export documents reach the right address." />
    </x-slot:hero>

    <h2 class="text-3xl font-extrabold tracking-tight text-ink">Verify Email</h2>
    <p class="mt-2 text-sm leading-relaxed text-ink-muted">
        Thanks for signing up. Before getting started, please verify your email address using the link we just sent.
        If you did not receive the email, we can send another one.
    </p>

    @if (session('status') === 'verification-link-sent')
        <x-auth.session-status class="mt-6" status="A new verification link has been sent to your email address." />
    @endif

    <div class="mt-8 space-y-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button class="btn-block">Resend Verification Email</x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-ghost btn-block">Log Out</button>
        </form>
    </div>
</x-guest-layout>
