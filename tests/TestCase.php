<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Laravel's test HTTP client doesn't carry cookies between separate
     * $this->get()/$this->post() calls by default — each call gets a fresh
     * session ID, unlike a real browser that resends its session cookie
     * automatically. Call this after a request that should be "remembered"
     * (e.g. a guest starting a quiz) so later calls in the same test see the
     * same session()->getId(), matching how ExportSessionPolicy authorizes
     * guest-owned sessions.
     *
     * Passing the plain session ID (not the encrypted Set-Cookie value from
     * the response) matters: withCookie() encrypts it itself on the way out,
     * so re-encrypting an already-encrypted value would just produce garbage
     * that fails to decrypt back to the original ID on the next request.
     */
    protected function persistGuestSession(): void
    {
        $this->withCookie(config('session.cookie'), session()->getId());
    }
}
