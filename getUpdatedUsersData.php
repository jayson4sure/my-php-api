<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: authentication");

// DB connection
require 'db_connect.php';

if (!$conn) {
    die(json_encode([
        "status" => "error",
        "error" => "Database connection failed"
    ]));
}

// Auth
$auth = $_SERVER['HTTP_AUTHENTICATION'] ?? '';
list($user_id, $auth_token) = explode(':', $auth) + [null, null];

$user_id = intval($user_id);

// Query
$query = "SELECT * FROM users WHERE user_id = $1";

$result = pg_query_params($conn, $query, [$user_id]);

$response = [];

if ($result) {
    if (pg_num_rows($result) > 0) {
        $user = pg_fetch_assoc($result);

        $response['status'] = "success";
        $response['user'] = $user;
        $response['error'] = "";
    } else {
        $response['status'] = "fail";
        $response['error'] = "No user found with the specified ID.";
    }
} else {
    $response['status'] = "error";
    $response['error'] = pg_last_error($conn);
}

pg_close($conn);

echo json_encode($response);
?>
