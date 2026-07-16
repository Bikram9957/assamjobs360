<?php
declare(strict_types=1);

// Minimal file-based router (Apache-friendly):
// Example usage in public pages: index.php reads ?p=jobs

function aj360_current_path(): string {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $base = rtrim(parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH) ?? '', '/');
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }
    $uri = strtok($uri, '?');
    if ($uri === '') $uri = '/';
    return $uri;
}

