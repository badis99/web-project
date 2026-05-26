<?php
/**
 * Routeur central des endpoints API.
 */

declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(0);

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

// ← Plus aucun require_once de ton côté ici

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

        case 'approval.handle':
    require_once __DIR__ . '/../Repositories/PendingRepository.php';
    require_once __DIR__ . '/../Repositories/UsingRepository.php';
    require_once __DIR__ . '/../Services/MailService.php';
    require_once __DIR__ . '/../Services/ApprovalService.php';
    require_once __DIR__ . '/../Controllers/ApprovalController.php';
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(createApprovalController()->handle($data));
    break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'API route not found.']);
            break;
    }
}

function normalizeApiAction(string $action): string
{
    $aliases = [
        'csrf'            => 'csrf.token',
        'login'           => 'auth.login',
        'signup'          => 'auth.signup',
        'logout'          => 'auth.logout',
        'check-session'   => 'auth.checkSession',
        'forgot-password' => 'password.forgot',
        'reset-password'  => 'password.reset',
        'send-message'    => 'contact.send',
        'approval'        => 'approval.handle',
    ];

    return $aliases[$action] ?? $action;
}

function createAuthController(): AuthController
{
    return new AuthController(new AuthService(
        new UserRepository(),
        new PendingRegistrationRepository(),
        new UploadService()
    ));
}

function createPasswordController(): PasswordController
{
    return new PasswordController(new PasswordService(
        new UserRepository(),
        new PasswordResetRepository(),
        new MailerService()
    ));
}

function createContactController(): ContactController
{
    return new ContactController(new ContactService(new MailerService()));
}

function createApprovalController(): ApprovalController
{
    return new ApprovalController(new ApprovalService());
}

$apiAction = $apiAction ?? ($_GET['action'] ?? '');
dispatchApiAction((string)$apiAction);