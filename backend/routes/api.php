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
// REST API (/api/*)
// Booking:
//   GET  /api/shows
//   GET  /api/shows/name/{name}
//   POST /api/shows/ensure
//   GET  /api/shows/{showId}/seats
//   POST /api/tickets
// Workshops:
//   GET    /api/workshops              (logged in)
//   GET    /api/workshops/{id}         (logged in)
//   POST   /api/workshops              (admin)
//   PUT    /api/workshops/{id}         (admin)
//   DELETE /api/workshops/{id}         (admin)
//   GET    /api/workshop-registrations (admin)
//   POST   /api/workshop-registrations (logged in, user id from session)
//   POST   /api/upload/video           (admin, multipart field "video")
// Fallback: /backend/routes/api.php?rest=workshops
// ------------------------------------------------------------

function requireAdmin(): void
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

function api_requireLoggedIn(): string
{
    startAuthSession(false);
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Login required.']);
        exit;
    }

    return (string)$_SESSION['user_id'];
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

function dispatchRestApiIfMatched(): void
{
    // Supports 2 modes:
    // 1) Rewritten REST paths: /api/shows, /api/workshops, ...
    // 2) Non-rewritten fallback: /backend/routes/api.php?rest=workshops
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

    header('Content-Type: application/json; charset=utf-8');

    $isWorkshopApi =
        ($segments[0] ?? '') === 'workshops' ||
        ($segments[0] ?? '') === 'workshop-registrations' ||
        (($segments[0] ?? '') === 'upload' && ($segments[1] ?? '') === 'video');

    if ($isWorkshopApi) {
        $workshopController = new WorkshopController(new WorkshopRepository());
        $registrationController = new WorkshopRegistrationController(new WorkshopRegistrationRepository());
        $videoUploadController = new VideoUploadController();

        // GET /api/workshops
        if ($method === 'GET' && $segments === ['workshops']) {
            api_requireLoggedIn();
            $workshopController->index();
            exit;
        }

        // GET /api/workshops/{id}
        if ($method === 'GET' && count($segments) === 2 && $segments[0] === 'workshops') {
            api_requireLoggedIn();
            $workshopController->workshop($segments[1]);
            exit;
        }

        // POST /api/workshops
        if ($method === 'POST' && $segments === ['workshops']) {
            requireAdmin();
            $workshopController->store();
            exit;
        }

        // PUT /api/workshops/{id}
        if ($method === 'PUT' && count($segments) === 2 && $segments[0] === 'workshops') {
            requireAdmin();
            $workshopController->update($segments[1]);
            exit;
        }

        // DELETE /api/workshops/{id}
        if ($method === 'DELETE' && count($segments) === 2 && $segments[0] === 'workshops') {
            requireAdmin();
            $workshopController->destroy($segments[1]);
            exit;
        }

        // GET /api/workshop-registrations
        if ($method === 'GET' && $segments === ['workshop-registrations']) {
            requireAdmin();
            $registrationController->index();
            exit;
        }

        // POST /api/workshop-registrations
        if ($method === 'POST' && $segments === ['workshop-registrations']) {
            $idUser=api_requireLoggedIn();
            $registrationController->store($idUser);
            exit;
        }

        // POST /api/upload/video
        if ($method === 'POST' && $segments === ['upload', 'video']) {
            requireAdmin();
            $videoUploadController->upload();
            exit;
        }

        http_response_code(404);
        echo json_encode(['error' => 'Not found.']);
        exit;
    }

    $isBookingApi = ($segments[0] ?? '') === 'shows' || ($segments[0] ?? '') === 'tickets';
    if (!$isBookingApi) {
        return;
    }

    // Public (no admin) endpoints:
    // - GET /api/shows
    // - GET /api/shows/name/{name}
    $isPublic =
        ($method === 'GET' && $segments === ['shows']) ||
        ($method === 'GET' && count($segments) === 3 && $segments[0] === 'shows' && $segments[1] === 'name');

    if (!$isPublic) {
        requireAdmin();
    }

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
    echo json_encode(['error' => 'Not found.']);
    exit;
}

dispatchRestApiIfMatched();

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