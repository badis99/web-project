<?php
require_once __DIR__ . '/../../Repositories/MembersRepository.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$membersRepo = new MembersRepository();
$membres = $membersRepo->findAll();

echo json_encode($membres);