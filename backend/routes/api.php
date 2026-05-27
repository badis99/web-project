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

// ------------------------------------------------------------
// REST booking API (admin-only)
// Supports rewritten routes like:
//   GET  /api/shows
//   GET  /api/shows/name/{name}
//   POST /api/shows/ensure
//   GET  /api/shows/{showId}/seats
//   POST /api/tickets
// If the web server rewrites /api/* to this file, REQUEST_URI will start with /api.
// ------------------------------------------------------------

function booking_requireAdmin(): void
{
    startAuthSession(false);
    $role = strtolower(trim((string)($_SESSION['role'] ?? '')));
    if (empty($_SESSION['user_id']) || $role !== 'admin') {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Admin access required.']);
        exit;
    }
}

function booking_normalizePath(string $path): string
{
    $path = parse_url($path, PHP_URL_PATH) ?? '/';
    $path = rtrim($path, '/');
    return $path === '' ? '/' : $path;
}

function booking_pathAfterApi(string $path): array
{
    $path = booking_normalizePath($path);
    if ($path === '/api') {
        return [];
    }
    if (!str_starts_with($path, '/api/')) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Not found.']);
        exit;
    }
    $rest = substr($path, strlen('/api/'));
    return array_values(array_filter(explode('/', $rest), fn($s) => $s !== ''));
}

function dispatchBookingRestApiIfMatched(): void
{
    // Supports 2 modes:
    // 1) Rewritten REST paths: /api/shows, /api/tickets, ...
    // 2) Non-rewritten fallback: /backend/routes/api.php?rest=shows
    $requestUri = (string)($_SERVER['REQUEST_URI'] ?? '/');
    $rest = isset($_GET['rest']) ? trim((string)$_GET['rest']) : '';
    if ($rest !== '') {
        $rest = ltrim($rest, '/');
        $requestUri = '/api/' . $rest;
    }
    $path = booking_normalizePath($requestUri);
    if (!($path === '/api' || str_starts_with($path, '/api/'))) {
        return;
    }

    $method = (string)($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $segments = booking_pathAfterApi($requestUri);

    // Public (no admin) endpoints:
    // - GET /api/shows
    // - GET /api/shows/name/{name}
    $isPublic =
        ($method === 'GET' && $segments === ['shows']) ||
        ($method === 'GET' && count($segments) === 3 && $segments[0] === 'shows' && $segments[1] === 'name');

    if (!$isPublic) {
        booking_requireAdmin();
    }

    header('Content-Type: application/json; charset=utf-8');

    $bookingController = new BookingController(
        new BookingService(new ShowsRepository(), new SeatsRepository(), new TicketsRepository()),
        new ShowsRepository()
    );

    // GET /api/shows
    if ($method === 'GET' && $segments === ['shows']) {
        $bookingController->getShows();
        exit;
    }

    // GET /api/shows/name/{name}
    if ($method === 'GET' && count($segments) === 3 && $segments[0] === 'shows' && $segments[1] === 'name') {
        $bookingController->getShowByName(urldecode($segments[2]));
        exit;
    }

    // POST /api/shows/ensure
    if ($method === 'POST' && $segments === ['shows', 'ensure']) {
        $bookingController->ensureShow();
        exit;
    }

    // GET /api/shows/{showId}/seats
    if ($method === 'GET' && count($segments) === 3 && $segments[0] === 'shows' && $segments[2] === 'seats') {
        $bookingController->getShowSeats($segments[1]);
        exit;
    }

    // POST /api/tickets
    if ($method === 'POST' && $segments === ['tickets']) {
        $bookingController->createTicket();
        exit;
    }

    // POST /api/tickets/release
    if ($method === 'POST' && $segments === ['tickets', 'release']) {
        $bookingController->releaseTicket();
        exit;
    }

    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Not found.']);
    exit;
}

dispatchBookingRestApiIfMatched();

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