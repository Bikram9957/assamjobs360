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

    // Mailer (for password reset links)
    // Set MAIL_USE_PHPMailer=true if you installed PHPMailer via composer.
    'MAIL_USE_PHPMailer' => false,
    'MAIL_FROM_EMAIL' => 'no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
    'MAIL_FROM_NAME' => 'AssamJobs360',

    // SMTP (only used when MAIL_USE_PHPMailer=true)
    'MAIL_SMTP_HOST' => 'localhost',
    'MAIL_SMTP_AUTH' => false,
    'MAIL_SMTP_USER' => '',
    'MAIL_SMTP_PASS' => '',
    'MAIL_SMTP_PORT' => 587,
    'MAIL_SMTP_ENCRYPTION' => 'tls',
];





