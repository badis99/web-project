<?php
/**
 * csrf.php — CSRF token middleware
 *
 * HOW IT WORKS:
 *   1. Frontend fetches GET /backend/middleware/csrf.php → receives a random token.
 *   2. Token is stored server-side in the PHP session.
 *   3. Frontend includes the token in every POST request body: { csrf_token: "..." }
 *   4. PHP endpoint calls validate_csrf($token) before processing.
 *
 * WHY?
 *   Even though SameSite=Strict on the session cookie already blocks
 *   most CSRF attacks, the token adds a second layer and satisfies
 *   academic requirements for proper CSRF protection.
 *
 * WHY hash_equals()?
 *   Constant-time comparison prevents timing-based attacks
 *   that could leak the token one byte at a time.
 */

declare(strict_types=1);

// Session must already be started by the calling script.
// This file is only included, not executed standalone (except for GET).

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Standalone: called by frontend JS to obtain a token
    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/cors.php';

    session_name('theatro_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
        'secure'   => !LOCAL_DEV,
    ]);
    session_start();

    // Generate once per session
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    echo json_encode(['token' => $_SESSION['csrf_token']]);
    exit;
}

/**
 * validate_csrf()
 * Called by POST endpoints after session_start().
 * Returns false and sends 403 if token is invalid.
 */
function validate_csrf(string $submitted_token): bool
{
    if (empty($_SESSION['csrf_token']) || empty($submitted_token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $submitted_token);
}
