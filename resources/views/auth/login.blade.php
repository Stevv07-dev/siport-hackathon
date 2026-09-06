<x-guest-layout>
    <x-slot:title>Sign In</x-slot:title>

    <x-slot:hero>
        <x-application-logo size="lg" class="mb-10" />

        <h1 class="text-5xl font-extrabold leading-[1.08] tracking-tight text-ink xl:text-6xl">
            Elevate Your<br>Professional<br>Trajectory.
        </h1>

        <p class="mt-6 max-w-md text-base leading-relaxed text-ink-muted">
            Manage product specifications, documents, and export processes in one integrated platform.
        </p>

        <div class="mt-12 flex items-center gap-8">
            <x-auth.hero-stat value="98%" label="Accuracy" />
            <div class="h-12 w-px bg-ink/15"></div>
            <x-auth.hero-stat value="12k+" label="Shipments Managed" />
        </div>
    </x-slot:hero>

    <h2 class="text-3xl font-extrabold tracking-tight text-ink">Welcome Back</h2>
    <p class="mt-2 text-sm text-ink-muted">Please enter your details to access your dashboard.</p>

    <x-auth.session-status class="mt-6" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
        @csrf

        <x-form.field name="email" label="Email Address">
            <x-form.control icon="mail">
                <x-text-input id="email" name="email" type="email" class="pr-12"
                              :value="old('email')" :invalid="$errors->has('email')"
                              placeholder="name@gmail.com"
                              required autofocus autocomplete="username" />
            </x-form.control>
        </x-form.field>

        <x-form.field name="password" label="Password">
            <x-slot:action>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                       class="text-ink-muted transition hover:text-ink">Forgot password?</a>
                @endif
            </x-slot:action>

            <x-form.password name="password" :toggle="false" icon="lock"
                             :invalid="$errors->has('password')"
                             placeholder="••••••••••"
                             required autocomplete="current-password" />
        </x-form.field>

        <label for="remember_me" class="flex cursor-pointer items-center gap-3 pt-1">
            <input id="remember_me" name="remember" type="checkbox"
                   class="h-5 w-5 rounded border-line-strong text-brand-600 shadow-none focus:ring-brand-400/40">
            <span class="text-sm text-ink-muted">Remember this device for 30 days</span>
        </label>

        <x-recaptcha />

        <x-primary-button class="btn-block !mt-7">Sign In</x-primary-button>
    </form>

    <x-auth.social />

    <p class="mt-8 text-center text-sm text-ink-muted">
        Don't have an account yet?
        <a href="{{ route('register') }}" class="font-semibold text-ink transition hover:text-brand-600">Create Account</a>
    </p>
</x-guest-layout>
