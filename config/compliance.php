<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Compliance Engine Driver
    |--------------------------------------------------------------------------
    |
    | Which App\Contracts\ComplianceEngine implementation generates export
    | screening questionnaires and verdicts (see AppServiceProvider).
    |
    | - "backend_ai" (default): reads questions and classification rules
    |   straight from backend-ai/usb-c-cable-dataset/ — the AI Engineer's
    |   actual rule engine. Deterministic, offline, no API key needed. Only
    |   covers USB-C cable today (see BackendAiComplianceEngine) — every
    |   other category/material is honestly "Coming Soon", not a fallback.
    | - "gemini": calls the Google Gemini API for AI-generated questions
    |   and verdicts. Requires GEMINI_API_KEY.
    | - "anthropic": calls the Anthropic API. Requires ANTHROPIC_API_KEY.
    |
    */

    'driver' => env('COMPLIANCE_ENGINE', 'backend_ai'),

];
