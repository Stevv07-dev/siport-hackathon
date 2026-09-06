<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class SocialiteController extends Controller
{
    /**
     * Provider yang boleh dipakai. Ditambah di sini kalau nanti ada provider lain.
     */
    private const PROVIDERS = ['google'];

    public function redirect(string $provider): RedirectResponse
    {
        $this->ensureProviderIsEnabled($provider);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        $this->ensureProviderIsEnabled($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (Throwable) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Could not sign in with Google. Please try again.']);
        }

        if (! $socialUser->getEmail()) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Your Google account did not share an email address.']);
        }

        $user = User::firstOrNew(['email' => $socialUser->getEmail()]);

        // Menautkan akun Google ke user yang sudah ada, atau membuat user baru.
        $user->fill([
            'name' => $user->name ?: ($socialUser->getName() ?: Str::before($socialUser->getEmail(), '@')),
            'google_id' => $socialUser->getId(),
            'avatar_url' => $socialUser->getAvatar(),
        ]);

        // Email dari provider sudah terverifikasi di sisi Google.
        $user->email_verified_at ??= now();
        $user->save();

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function ensureProviderIsEnabled(string $provider): void
    {
        if (! in_array($provider, self::PROVIDERS, true) || ! config("services.{$provider}.client_id")) {
            throw new NotFoundHttpException("The {$provider} login is not enabled.");
        }
    }
}
