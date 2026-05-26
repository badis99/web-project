<?php
ob_start();
header("Content-Type: application/json");
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../repositories/PendingRepository.php';
require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../services/MailService.php';
require_once __DIR__ . '/../services/ApprovalService.php';
require_once __DIR__ . '/../controllers/ApprovalController.php';

$service    = new ApprovalService();
$controller = new ApprovalController($service);

$data   = json_decode(file_get_contents("php://input"), true) ?? [];
$result = $controller->handle($data);

ob_end_clean();
echo json_encode($result);