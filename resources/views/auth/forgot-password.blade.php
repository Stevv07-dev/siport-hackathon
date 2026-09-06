<x-guest-layout>
    <x-slot:title>Forgot Password</x-slot:title>

    <x-slot:hero>
        <x-auth.hero heading="Back to Your<br>Export Flow."
                     description="Enter your registered email and we will send you a link to reset your password." />
    </x-slot:hero>

    <h2 class="text-3xl font-extrabold tracking-tight text-ink">Forgot Password</h2>
    <p class="mt-2 text-sm leading-relaxed text-ink-muted">
        No problem. Tell us your email address and we will send a link to create a new password.
    </p>

    <x-auth.session-status class="mt-6" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
        @csrf

        <x-form.field name="email" label="Email Address">
            <x-form.control icon="mail">
                <x-text-input id="email" name="email" type="email" class="pr-12"
                              :value="old('email')" :invalid="$errors->has('email')"
                              placeholder="name@gmail.com"
                              required autofocus autocomplete="username" />
            </x-form.control>
        </x-form.field>

        <x-primary-button class="btn-block !mt-7">Send Reset Link</x-primary-button>
    </form>

    <p class="mt-8 text-center text-sm text-ink-muted">
        Remembered your password?
        <a href="{{ route('login') }}" class="font-semibold text-ink transition hover:text-brand-600">Back to Sign In</a>
    </p>
</x-guest-layout>
