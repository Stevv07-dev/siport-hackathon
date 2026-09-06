<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecaptchaIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.recaptcha.site_key' => 'test-site-key', 'services.recaptcha.secret_key' => 'test-secret']);
    }

    public function test_the_widget_is_hidden_when_not_configured(): void
    {
        config(['services.recaptcha.site_key' => null]);

        $this->get('/login')->assertOk()->assertDontSee('g-recaptcha');
        $this->get('/register')->assertOk()->assertDontSee('g-recaptcha');
    }

    public function test_the_widget_is_shown_when_configured(): void
    {
        $this->get('/login')->assertOk()->assertSee('g-recaptcha');
        $this->get('/register')->assertOk()->assertSee('g-recaptcha');
    }

    public function test_registration_is_rejected_when_the_captcha_fails(): void
    {
        Http::fake(['www.google.com/*' => Http::response(['success' => false])]);

        $this->post('/register', [
            'name' => 'Rina Kusuma',
            'email' => 'rina@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'g-recaptcha-response' => 'fake-token',
        ])->assertSessionHasErrors('g-recaptcha-response');

        $this->assertSame(0, User::count());
    }

    public function test_registration_succeeds_when_the_captcha_passes(): void
    {
        Http::fake(['www.google.com/*' => Http::response(['success' => true])]);

        $this->post('/register', [
            'name' => 'Rina Kusuma',
            'email' => 'rina@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'g-recaptcha-response' => 'real-token',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertSame(1, User::count());
    }

    public function test_login_is_rejected_when_the_captcha_fails(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);

        Http::fake(['www.google.com/*' => Http::response(['success' => false])]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'g-recaptcha-response' => 'fake-token',
        ])->assertSessionHasErrors('g-recaptcha-response');

        $this->assertGuest();
    }

    public function test_login_succeeds_when_the_captcha_passes(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);

        Http::fake(['www.google.com/*' => Http::response(['success' => true])]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'g-recaptcha-response' => 'real-token',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }
}
