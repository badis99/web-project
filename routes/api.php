<?php
ob_start();
header("Content-Type: application/json");
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../backend/Repositories/PendingRepository.php';
require_once __DIR__ . '/../backend/Repositories/UserRepository.php';
require_once __DIR__ . '/../backend/Services/MailService.php';
require_once __DIR__ . '/../backend/Services/ApprovalService.php';
require_once __DIR__ . '/../backend/Controllers/ApprovalController.php';

$service    = new ApprovalService();
$controller = new ApprovalController($service);

$data   = json_decode(file_get_contents("php://input"), true) ?? [];
$result = $controller->handle($data);

ob_end_clean();
echo json_encode($result);