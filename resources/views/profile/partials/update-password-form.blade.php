<section>
    <header>
        <h2 class="text-lg font-bold tracking-tight text-ink">Update Password</h2>
        <p class="mt-1 text-sm text-ink-muted">
            Use a long, random password to keep your account secure.
        </p>
    </header>

    @if (is_null($user->password))
        {{-- Akun hasil login Google belum punya kata sandi lokal. --}}
        <div class="mt-6 max-w-xl rounded-lg border border-line bg-surface px-4 py-3 text-sm text-ink-muted">
            This account signs in with Google and has no password yet. Use
            <a href="{{ route('password.request') }}" class="font-semibold text-ink underline underline-offset-2">Forgot password</a>
            to create one.
        </div>
    @else
        <form method="post" action="{{ route('password.update') }}" class="mt-6 max-w-xl space-y-5">
            @csrf
            @method('put')

            <div class="space-y-2">
                <x-input-label for="update_password_current_password" value="Current Password" variant="plain" />
                <x-form.password name="current_password" variant="outline"
                                 id="update_password_current_password"
                                 :invalid="$errors->updatePassword->has('current_password')"
                                 autocomplete="current-password" />
                <x-input-error :messages="$errors->updatePassword->get('current_password')" />
            </div>

            <div class="space-y-2">
                <x-input-label for="update_password_password" value="New Password" variant="plain" />
                <x-form.password name="password" variant="outline"
                                 id="update_password_password"
                                 :invalid="$errors->updatePassword->has('password')"
                                 autocomplete="new-password" />
                <x-input-error :messages="$errors->updatePassword->get('password')" />
            </div>

            <div class="space-y-2">
                <x-input-label for="update_password_password_confirmation" value="Confirm Password" variant="plain" />
                <x-form.password name="password_confirmation" variant="outline"
                                 id="update_password_password_confirmation"
                                 :invalid="$errors->updatePassword->has('password_confirmation')"
                                 autocomplete="new-password" />
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" />
            </div>

            <div class="flex items-center gap-4 pt-1">
                <x-primary-button>Save</x-primary-button>

                @if (session('status') === 'password-updated')
                    <p x-data="{ show: true }" x-show="show" x-transition
                       x-init="setTimeout(() => show = false, 2500)"
                       class="text-sm font-medium text-success-700">Saved.</p>
                @endif
            </div>
        </form>
    @endif
</section>
