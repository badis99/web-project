<?php
require_once __DIR__ . '/../../Repositories/MembersRepository.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$id = $_POST['id'] ?? null;
if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing ID']);
    exit;
}

$membersRepo = new MembersRepository();

$updatableFields = [
    'firstname',
    'lastname',
    'birthdate',
    'department',
    'phone',
    'email',
    'fieldofstudy',
    'yearofstudy'
];

$updateData = [];
foreach ($updatableFields as $field) {
    if (array_key_exists($field, $_POST)) {
        $updateData[$field] = trim((string)$_POST[$field]);
    }
}

if (!empty($_FILES['picture']['tmp_name']) && is_uploaded_file($_FILES['picture']['tmp_name'])) {
    $pictureFile = $_FILES['picture'];
    $extension = pathinfo($pictureFile['name'], PATHINFO_EXTENSION) ?: 'jpg';
    $originalName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($pictureFile['name']));
    $filename = $originalName !== '' ? $originalName : sprintf('profile_%s.%s', $id, $extension);
    $content = file_get_contents($pictureFile['tmp_name']);
    $contentType = $pictureFile['type'] ?: 'application/octet-stream';

    $uploaded = $membersRepo->uploadProfilePicture($filename, $content, $contentType);
    if (!$uploaded) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Image upload failed']);
        exit;
    }

    $updateData['picture'] = $filename;
}

$success = $membersRepo->updateById($id, $updateData);

if (!$success) {
    http_response_code(500);
}

echo json_encode(['success' => $success, 'updated' => $updateData]);
