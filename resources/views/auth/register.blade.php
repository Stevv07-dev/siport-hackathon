<x-guest-layout>
    <x-slot:title>Sign Up</x-slot:title>

    <x-slot:hero>
        <span class="inline-flex items-center gap-2 rounded-full bg-white/40 px-4 py-2 text-xs font-bold uppercase tracking-label text-ink">
            <x-icon name="trending-up" class="h-4 w-4" />
            Smart Export Platform
        </span>

        <h1 class="mt-8 text-5xl font-extrabold leading-[1.08] tracking-tight text-ink xl:text-6xl">
            Simplify Your<br>Export Journey
        </h1>

        <p class="mt-6 max-w-md text-base leading-relaxed text-ink-muted">
            Check your product readiness and prepare export documents efficiently through one integrated platform.
        </p>

        <div class="mt-12 flex gap-4">
            <x-auth.hero-stat variant="card" value="94%" label="Shipments Processed" />
            <x-auth.hero-stat variant="card" value="12k+" label="Process Accuracy" />
        </div>
    </x-slot:hero>

    <h2 class="text-3xl font-extrabold tracking-tight text-ink">MAXPORT</h2>
    <p class="mt-2 text-sm text-ink-muted">Create your account to start your analysis.</p>

    <x-auth.session-status class="mt-6" :status="session('status')" />

    <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-6">
        @csrf

        <x-form.field name="name" label="Full Name" label-variant="plain">
            <x-text-input id="name" name="name" type="text" variant="underline"
                          :value="old('name')" :invalid="$errors->has('name')"
                          placeholder="Enter your name"
                          required autofocus autocomplete="name" />
        </x-form.field>

        <x-form.field name="email" label="Email Address" label-variant="plain">
            <x-text-input id="email" name="email" type="email" variant="underline"
                          :value="old('email')" :invalid="$errors->has('email')"
                          placeholder="name@gmail.com"
                          required autocomplete="username" />
        </x-form.field>

        <x-form.field name="password" label="Password" label-variant="plain">
            <x-form.password name="password" variant="underline"
                             :invalid="$errors->has('password')"
                             placeholder="••••••••"
                             required autocomplete="new-password" />
        </x-form.field>

        <x-form.field name="password_confirmation" label="Confirm Password" label-variant="plain">
            <x-form.password name="password_confirmation" variant="underline"
                             :invalid="$errors->has('password_confirmation')"
                             placeholder="••••••••"
                             required autocomplete="new-password" />
        </x-form.field>

        <x-recaptcha />

        <x-primary-button class="btn-block !mt-8">Sign Up</x-primary-button>
    </form>

    <x-auth.social />

    <p class="mt-8 text-center text-sm text-ink-muted">
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-brand-600 transition hover:text-brand-500">Log In</a>
    </p>

    <p class="mt-6 text-center text-xs leading-relaxed text-ink-subtle">
        By clicking "Sign Up", you agree to our
        <a href="#" class="underline decoration-line-strong underline-offset-2 hover:text-ink-muted">Terms of Service</a>
        and
        <a href="#" class="underline decoration-line-strong underline-offset-2 hover:text-ink-muted">Privacy Policy</a>.
        MAXPORT uses encryption to keep your data secure.
    </p>
</x-guest-layout>
