<section>
    <header>
        <h2 class="text-lg font-bold tracking-tight text-ink">Profile Information</h2>
        <p class="mt-1 text-sm text-ink-muted">Update your account's name and email address.</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 max-w-xl space-y-5">
        @csrf
        @method('patch')

        <x-form.field name="name" label="Full Name" label-variant="plain">
            <x-text-input id="name" name="name" type="text" variant="outline"
                          :value="old('name', $user->name)" :invalid="$errors->has('name')"
                          required autofocus autocomplete="name" />
        </x-form.field>

        <x-form.field name="email" label="Email Address" label-variant="plain">
            <x-text-input id="email" name="email" type="email" variant="outline"
                          :value="old('email', $user->email)" :invalid="$errors->has('email')"
                          required autocomplete="username" />
        </x-form.field>

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="rounded-lg border border-warning-500/25 bg-warning-50 px-4 py-3 text-sm text-warning-700">
                Your email address is unverified.
                <button form="send-verification" class="font-semibold underline underline-offset-2">
                    Resend the verification email
                </button>

                @if (session('status') === 'verification-link-sent')
                    <p class="mt-2 font-semibold text-success-700">A new verification link has been sent.</p>
                @endif
            </div>
        @endif

        <div class="flex items-center gap-4 pt-1">
            <x-primary-button>Save</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition
                   x-init="setTimeout(() => show = false, 2500)"
                   class="text-sm font-medium text-success-700">Saved.</p>
            @endif
        </div>
    </form>
</section>
