<?php
/**
 * cors.php — Response headers middleware
 *
 * Called at the top of every backend endpoint.
 * Sets JSON content type and handles OPTIONS preflight.
 *
 * Since frontend and backend share the same origin
 * (http://localhost:8000), we do not need permissive
 * CORS. We still set security headers explicitly.
 */

declare(strict_types=1);

// Always respond with JSON
header('Content-Type: application/json; charset=utf-8');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Credentials must be explicitly allowed for cookies to be sent
// (same-origin, so this is mostly a safety measure)
header('Access-Control-Allow-Credentials: true');

// Handle CORS preflight (OPTIONS) immediately
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    http_response_code(204);
    exit;
}
