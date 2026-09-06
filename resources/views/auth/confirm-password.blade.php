<x-guest-layout>
    <x-slot:title>Confirm Password</x-slot:title>

    <x-slot:hero>
        <x-auth.hero heading="One Step of<br>Verification."
                     description="This area holds sensitive data, so we need to make sure it is really you." />
    </x-slot:hero>

    <h2 class="text-3xl font-extrabold tracking-tight text-ink">Confirm Password</h2>
    <p class="mt-2 text-sm leading-relaxed text-ink-muted">
        This is a secure area of the application. Please confirm your password before continuing.
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-8 space-y-5">
        @csrf

        <x-form.field name="password" label="Password">
            <x-form.password name="password" :invalid="$errors->has('password')"
                             placeholder="••••••••" required autocomplete="current-password" />
        </x-form.field>

        <x-primary-button class="btn-block !mt-7">Confirm</x-primary-button>
    </form>
</x-guest-layout>
