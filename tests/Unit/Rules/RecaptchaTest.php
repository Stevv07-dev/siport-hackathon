<?php

namespace Tests\Unit\Rules;

use App\Rules\Recaptcha;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecaptchaTest extends TestCase
{
    private function fails(mixed $value): bool
    {
        $failed = false;

        (new Recaptcha)->validate('g-recaptcha-response', $value, function () use (&$failed) {
            $failed = true;
        });

        return $failed;
    }

    public function test_it_passes_when_not_configured(): void
    {
        config(['services.recaptcha.secret_key' => null]);

        $this->assertFalse($this->fails(null));
        $this->assertFalse($this->fails('anything'));
    }

    public function test_it_fails_when_the_token_is_empty(): void
    {
        config(['services.recaptcha.secret_key' => 'test-secret']);

        $this->assertTrue($this->fails(null));
        $this->assertTrue($this->fails(''));
    }

    public function test_it_passes_when_google_confirms_success(): void
    {
        config(['services.recaptcha.secret_key' => 'test-secret']);

        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true]),
        ]);

        $this->assertFalse($this->fails('a-valid-token'));

        Http::assertSent(fn ($request) => $request['secret'] === 'test-secret' && $request['response'] === 'a-valid-token');
    }

    public function test_it_fails_when_google_reports_failure(): void
    {
        config(['services.recaptcha.secret_key' => 'test-secret']);

        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => false, 'error-codes' => ['invalid-input-response']]),
        ]);

        $this->assertTrue($this->fails('a-bad-token'));
    }

    public function test_it_fails_when_google_is_unreachable(): void
    {
        config(['services.recaptcha.secret_key' => 'test-secret']);

        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response('', 500),
        ]);

        $this->assertTrue($this->fails('a-token'));
    }
}
