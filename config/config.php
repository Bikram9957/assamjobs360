<?php
declare(strict_types=1);

// Central configuration.
// NOTE: This file was missing/empty in the current workspace.
// These defaults are safe for local development; update with your SMTP credentials.

return [
    // Database
    'DB_HOST' => 'localhost',
    'DB_USER' => 'root',
    'DB_PASS' => '',
    'DB_NAME' => 'assamjobs360',

    // CSRF
    'CSRF_SESSION_KEY' => 'aj360_csrf',

    // Admin signup rate limiting
    'ADMIN_SIGNUP_RATE_LIMIT_MAX' => 10,
    'ADMIN_SIGNUP_RATE_LIMIT_WINDOW_SECONDS' => 3600,

    // Admin login rate limiting (used in admin/login.php)
    'ADMIN_LOGIN_RATE_LIMIT_MAX_REQUESTS' => 10,
    'ADMIN_LOGIN_RATE_LIMIT_WINDOW_SECONDS' => 900,

    // Forgot password (admin)
    'ADMIN_PASSWORD_RESET_TTL_SECONDS' => 1800,
    'ADMIN_FORGOT_PASSWORD_RATE_LIMIT_MAX_REQUESTS' => 5,
    'ADMIN_FORGOT_PASSWORD_RATE_LIMIT_WINDOW_SECONDS' => 3600,

    // Reset password (admin)
    'ADMIN_RESET_PASSWORD_RATE_LIMIT_MAX_REQUESTS' => 5,
    'ADMIN_RESET_PASSWORD_RATE_LIMIT_WINDOW_SECONDS' => 3600,

    // Sessions
    'ADMIN_SESSION_TIMEOUT_SECONDS' => 3600,
    'USER_SESSION_TIMEOUT_SECONDS' => 3600,

    // Public user signup rate limit (used in user/register.php)
    'USER_SIGNUP_RATE_LIMIT_MAX_REQUESTS' => 10,
    'USER_SIGNUP_RATE_LIMIT_WINDOW_SECONDS' => 3600,

    // Public user login rate limit (used in user/login.php)
    'USER_LOGIN_RATE_LIMIT_MAX_REQUESTS' => 10,
    'USER_LOGIN_RATE_LIMIT_WINDOW_SECONDS' => 900,

    // User email OTP (verification)
    'USER_EMAIL_OTP_TTL_SECONDS' => 900, // 15 mins
    'USER_EMAIL_OTP_RATE_LIMIT_MAX_REQUESTS' => 5,
    'USER_EMAIL_OTP_RATE_LIMIT_WINDOW_SECONDS' => 3600,

    // Mail

    // If you have PHPMailer installed + configured, set MAIL_USE_PHPMailer = true.
    'MAIL_USE_PHPMailer' => false,
    'MAIL_FROM_EMAIL' => 'no-reply@localhost',
    'MAIL_FROM_NAME' => 'AssamJobs360',

    // PHPMailer SMTP settings (optional)
    'MAIL_SMTP_HOST' => 'localhost',
    'MAIL_SMTP_AUTH' => false,
    'MAIL_SMTP_USER' => '',
    'MAIL_SMTP_PASS' => '',
    'MAIL_SMTP_PORT' => 587,
    'MAIL_SMTP_ENCRYPTION' => 'tls',

    // New: Admin email OTP (verification)
    'ADMIN_EMAIL_OTP_TTL_SECONDS' => 900, // 15 mins
    'ADMIN_EMAIL_OTP_RATE_LIMIT_MAX_REQUESTS' => 5,
    'ADMIN_EMAIL_OTP_RATE_LIMIT_WINDOW_SECONDS' => 3600,
];

