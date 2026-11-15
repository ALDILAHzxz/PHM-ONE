<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Demo Mode
    |---------------------------------------------------------------------------
    | When true the application runs entirely on synthetic seed data, the demo
    | login panel is available, and account-security features that require real
    | enterprise services (password reset email, passkeys) are presented as
    | unavailable/simulated instead of pretending to work.
    |
    | This flag must be false before any deployment against company services.
    */

    'demo_mode' => (bool) env('PHM_DEMO_MODE', true),

    /*
    |---------------------------------------------------------------------------
    | Dashboard Refresh
    |---------------------------------------------------------------------------
    | Interval (seconds) used by authorized Livewire polling on the supervisor
    | queue and management dashboard. Polling is used instead of WebSockets
    | because no broadcast infrastructure is authorized for this prototype.
    */

    'poll_seconds' => (int) env('PHM_POLL_SECONDS', 5),

    /*
    |---------------------------------------------------------------------------
    | Beesha AI
    |---------------------------------------------------------------------------
    | provider: "demo"      -> deterministic assistant driven only by authorized
    |                          database records. No data leaves the server.
    |           "anthropic" -> server-side model call. Requires an explicitly
    |                          approved API key. See docs/BEESHA_AI_ARCHITECTURE.md
    */

    'beesha' => [
        'provider' => env('BEESHA_PROVIDER', 'demo'),
        'api_key' => env('BEESHA_API_KEY'),
        'model' => env('BEESHA_MODEL', 'claude-haiku-5-5'),
        'max_history' => 40,
    ],

    /*
    |---------------------------------------------------------------------------
    | Overproduction Policy (DEMO RULE)
    |---------------------------------------------------------------------------
    | PHM has NOT confirmed an official overproduction policy. For the MVP we
    | use a documented demo rule: a report whose quantity exceeds the remaining
    | target is rejected with a correction prompt, unless the submitting user
    | holds the 'production.report.exception' permission, in which case the
    | report is accepted and flagged for supervisor attention.
    |
    | The submitted amount is never silently reduced.
    */

    'overproduction' => [
        'policy' => 'reject_with_exception', // documented demo rule
        'tolerance_units' => 0,
    ],

    /*
    |---------------------------------------------------------------------------
    | Enterprise SSO Boundary
    |---------------------------------------------------------------------------
    | Integration point for a future approved OIDC provider (Authorization Code
    | Flow + PKCE). Nothing is wired to a live provider in this prototype.
    */

    'oidc' => [
        'enabled' => (bool) env('OIDC_ENABLED', false),
        'issuer_url' => env('OIDC_ISSUER_URL'),
        'client_id' => env('OIDC_CLIENT_ID'),
        'client_secret' => env('OIDC_CLIENT_SECRET'),
        'redirect_uri' => env('OIDC_REDIRECT_URI'),
    ],

];
