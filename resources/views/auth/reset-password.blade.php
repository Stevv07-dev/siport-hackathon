<x-guest-layout>
    <x-slot:title>Reset Password</x-slot:title>

    <x-slot:hero>
        <x-auth.hero heading="Create Your<br>New Password."
                     description="Choose a strong password to keep your product data and export documents secure." />
    </x-slot:hero>

    <h2 class="text-3xl font-extrabold tracking-tight text-ink">Reset Password</h2>
    <p class="mt-2 text-sm text-ink-muted">Enter a new password for your account.</p>

    <form method="POST" action="{{ route('password.store') }}" class="mt-8 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-form.field name="email" label="Email Address">
            <x-form.control icon="mail">
                <x-text-input id="email" name="email" type="email" class="pr-12"
                              :value="old('email', $request->email)" :invalid="$errors->has('email')"
                              required autofocus autocomplete="username" />
            </x-form.control>
        </x-form.field>

        <x-form.field name="password" label="New Password">
            <x-form.password name="password" :invalid="$errors->has('password')"
                             placeholder="••••••••" required autocomplete="new-password" />
        </x-form.field>

        <x-form.field name="password_confirmation" label="Confirm Password">
            <x-form.password name="password_confirmation" :invalid="$errors->has('password_confirmation')"
                             placeholder="••••••••" required autocomplete="new-password" />
        </x-form.field>

        <x-primary-button class="btn-block !mt-7">Save Password</x-primary-button>
    </form>
</x-guest-layout>
