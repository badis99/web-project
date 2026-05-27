<?php
    require_once __DIR__ . '/../../Repositories/MembersRepository.php';

    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");

    $id = $_GET['id'] ?? null;

    if (!$id) 
    {
        http_response_code(400);
        echo json_encode(["error" => "Missing ID"]);
        exit;
    }

    $membersRepo = new MembersRepository();
    $infos = $membersRepo->findById($id);

    echo json_encode($infos);