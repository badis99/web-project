<?php
/**
 * Fonctions de protection CSRF.
 */

declare(strict_types=1);

/**
 * Retourne le token CSRF de la session courante.
 *
 * @return string
 */
function getCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Verifie le token CSRF soumis par le client.
 *
 * @param string $submittedToken Token recu dans la requete
 * @return bool
 */
function validateCsrf(string $submittedToken): bool
{
    if (empty($_SESSION['csrf_token']) || empty($submittedToken)) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $submittedToken);
}
