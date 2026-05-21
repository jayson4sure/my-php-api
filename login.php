<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include 'db.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode([
        "success" => false,
        "message" => "Username and password required"
    ]);
    exit;
}

$query = "SELECT * FROM users WHERE username = $1 AND password = $2";

$result = pg_query_params(
    $conn,
    $query,
    [$username, $password]
);

if (pg_num_rows($result) > 0) {

    $user = pg_fetch_assoc($result);

    echo json_encode([
        "success" => true,
        "message" => "Login successful",
        "user" => [
            "id" => $user['id'],
            "username" => $user['username']
        ]
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Invalid username or password"
    ]);
}
