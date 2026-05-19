<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$username = $_POST['username'] ?? '';

echo json_encode([
    "success" => true,
    "username" => $username
]);
