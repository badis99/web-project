<?php
    require_once __DIR__ . '/../../Repositories/MembersRepository.php';

    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");

    $column = $_GET["filter-column"] ?? null;
    $value  = $_GET["filter-value"] ?? null;

    if (!$column || !$value) 
    {
        echo json_encode([]);
        exit;
    }

    $membersRepo = new MembersRepository();
    echo json_encode($membersRepo->findByFilter($column, $value));