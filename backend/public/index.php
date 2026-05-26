<?php

declare(strict_types=1);

require_once __DIR__ . '/../Core/Env.php';
require_once __DIR__ . '/../Core/Request.php';
require_once __DIR__ . '/../Core/Response.php';
require_once __DIR__ . '/../Core/Router.php';

require_once __DIR__ . '/../Models/WorkshopModel.php';
require_once __DIR__ . '/../Models/WorkshopRegistrationModel.php';
require_once __DIR__ . '/../Controllers/HealthController.php';
require_once __DIR__ . '/../Controllers/WorkshopController.php';
require_once __DIR__ . '/../Controllers/WorkshopRegistrationController.php';
require_once __DIR__ . '/../Controllers/VideoUploadController.php';

// Load env vars first (Env::load is safe to call multiple times)
Env::load(__DIR__ . '/../../.env');
Env::load(__DIR__ . '/../.env');

// Load Supabase REST client — defines supabase_query() and SUPABASE_* constants
require_once __DIR__ . '/../config/db.php';

// ── CORS: allow any origin, handle preflight ──────────────────
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Models use supabase_query() — no PDO / Database needed
$workshopModel     = new WorkshopModel();
$registrationModel = new WorkshopRegistrationModel();

$healthController       = new HealthController();
$workshopController     = new WorkshopController($workshopModel);
$registrationController = new WorkshopRegistrationController($registrationModel);
$videoUploadController  = new VideoUploadController();

$router = new Router();
require __DIR__ . '/../routes/api.php';

$request = Request::fromGlobals();
$router->dispatch($request);
