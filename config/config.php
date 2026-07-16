<?php
declare(strict_types=1);

// DB configuration
return [
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'assamjobs360',
    'DB_USER' => 'root',
    'DB_PASS' => '',

    // Security
    'CSRF_SESSION_KEY' => 'aj360_csrf',

    // Rate limiting (simple)
    'RATE_LIMIT_WINDOW_SECONDS' => 60,
    'RATE_LIMIT_MAX_REQUESTS' => 120,

    // Admin session expires after this much inactivity.
    'ADMIN_SESSION_TIMEOUT_SECONDS' => 3600,
    'ADMIN_SIGNUP_RATE_LIMIT_MAX' => 5,
    'ADMIN_SIGNUP_RATE_LIMIT_WINDOW_SECONDS' => 3600,

    // Admin login brute-force protection
    'ADMIN_LOGIN_RATE_LIMIT_MAX_REQUESTS' => 10, // attempts per IP+username
    'ADMIN_LOGIN_RATE_LIMIT_WINDOW_SECONDS' => 900, // lock window (15 minutes)
];


