<?php
    require_once __DIR__ . '/../../Repositories/MembersRepository.php';

    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: DELETE, GET");
    header("Access-Control-Allow-Headers: Content-Type");

    $id = $_GET['id'] ?? null;

    if (!$id) 
    {
        http_response_code(400);
        echo json_encode(["error" => "Missing ID"]);
        exit;
    }

    $membersRepo = new MembersRepository();
    $success = $membersRepo->deleteById($id);

    echo json_encode(["success" => $success]);