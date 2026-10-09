<?php

return [
    /*
    | Content-Security-Policy. Set CSP_REPORT_ONLY=true to watch for violations in the browser console
    | without blocking anything, which is the safe way to roll out a change to the policy.
    */
    'csp' => [
        'enabled' => env('CSP_ENABLED', true),
        'report_only' => env('CSP_REPORT_ONLY', false),
    ],

    /* Strict-Transport-Security, sent only on HTTPS responses outside local development. */
    'hsts' => [
        'enabled' => env('HSTS_ENABLED', true),
        'max_age' => 31536000,
    ],
];
