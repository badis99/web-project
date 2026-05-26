<?php
/**
 * Routeur central des endpoints API.
 */

declare(strict_types=1);

require_once __DIR__ . '/../Core/Cors.php';
require_once __DIR__ . '/../config/app.php';

try {
    loadAppConfig();
} catch (RuntimeException $exception) {
    http_response_code(500);
    echo json_encode(['error' => $exception->getMessage()]);
    exit;
}

require_once __DIR__ . '/../Core/Autoloader.php';
require_once __DIR__ . '/../Core/Session.php';
require_once __DIR__ . '/../Core/Csrf.php';
require_once __DIR__ . '/../Core/RateLimit.php';

/**
 * Route l'action API vers le bon controleur.
 *
 * @param string $action Action demandee
 * @return void
 */
function dispatchApiAction(string $action): void
{
    $action = normalizeApiAction($action);

    switch ($action) {
        case 'csrf.token':
            startAuthSession(false);
            echo json_encode(['token' => getCsrfToken()]);
            break;

        case 'auth.login':
            createAuthController()->login();
            break;

        case 'auth.signup':
            createAuthController()->signup();
            break;

        case 'auth.logout':
            createAuthController()->logout();
            break;

        case 'auth.checkSession':
            createAuthController()->checkSession();
            break;

        case 'password.forgot':
            createPasswordController()->forgotPassword();
            break;

        case 'password.reset':
            createPasswordController()->resetPassword();
            break;

        case 'contact.send':
            createContactController()->sendMessage();
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'API route not found.']);
            break;
    }
}

/**
 * Normalise les alias d'action.
 *
 * @param string $action Action brute
 * @return string
 */
function normalizeApiAction(string $action): string
{
    $aliases = [
        'csrf'           => 'csrf.token',
        'login'          => 'auth.login',
        'signup'         => 'auth.signup',
        'logout'         => 'auth.logout',
        'check-session'  => 'auth.checkSession',
        'forgot-password'=> 'password.forgot',
        'reset-password' => 'password.reset',
        'send-message'   => 'contact.send',
    ];

    return $aliases[$action] ?? $action;
}

/**
 * Fabrique le controleur d'authentification.
 *
 * @return AuthController
 */
function createAuthController(): AuthController
{
    return new AuthController(new AuthService(
        new UserRepository(),
        new PendingRegistrationRepository(),
        new UploadService()
    ));
}

/**
 * Fabrique le controleur de mot de passe.
 *
 * @return PasswordController
 */
function createPasswordController(): PasswordController
{
    return new PasswordController(new PasswordService(
        new UserRepository(),
        new PasswordResetRepository(),
        new MailerService()
    ));
}

/**
 * Fabrique le controleur de contact.
 *
 * @return ContactController
 */
function createContactController(): ContactController
{
    return new ContactController(new ContactService(new MailerService()));
}

$apiAction = $apiAction ?? ($_GET['action'] ?? '');
dispatchApiAction((string)$apiAction);
