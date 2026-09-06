{{-- Google reCAPTCHA v2 checkbox — invisible entirely unless CAPTCHA_SITE_KEY is set. --}}
@if (config('services.recaptcha.site_key'))
    <div>
        <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
        <x-input-error :messages="$errors->get('g-recaptcha-response')" class="mt-2" />
    </div>

    @once
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endonce
@endif
