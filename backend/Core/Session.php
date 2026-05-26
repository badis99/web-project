<?php
/**
 * Configuration centralisee de la session PHP.
 */

declare(strict_types=1);

/**
 * Demarre la session avec les parametres du projet.
 *
 * @param bool $persistent true pour un cookie de 7 jours
 * @return void
 */
function startAuthSession(bool $persistent = true): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $sessionPath = __DIR__ . '/../../tmp/sessions';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0755, true);
    }
    if (is_writable($sessionPath)) {
        session_save_path($sessionPath);
    }

    session_name('theatro_session');
    session_set_cookie_params([
        'lifetime' => $persistent ? 60 * 60 * 24 * 7 : 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
        'secure'   => !LOCAL_DEV,
    ]);
    session_start();
}
