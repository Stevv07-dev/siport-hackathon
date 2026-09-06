<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialiteLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_button_is_hidden_when_credentials_are_missing(): void
    {
        config(['services.google.client_id' => null]);

        $this->get('/login')->assertOk()->assertDontSee('Or continue with');
        $this->get('/register')->assertOk()->assertDontSee('Or continue with');
    }

    public function test_google_button_is_shown_when_credentials_are_configured(): void
    {
        config(['services.google.client_id' => 'test-client-id']);

        $this->get('/login')->assertOk()->assertSee('Or continue with');
    }

    public function test_oauth_routes_are_not_available_when_provider_is_disabled(): void
    {
        config(['services.google.client_id' => null]);

        $this->get('/auth/google/redirect')->assertNotFound();
        $this->get('/auth/google/callback')->assertNotFound();
    }

    public function test_unknown_providers_are_rejected(): void
    {
        $this->get('/auth/facebook/redirect')->assertNotFound();
    }
}
