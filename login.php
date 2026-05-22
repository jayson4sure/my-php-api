<?php
// Always set CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight (OPTIONS) requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Include PostgreSQL connection
require 'db_connect.php';

// Get username/email input
$user_input = $_POST['username'] ?? $_REQUEST['username'] ?? '';

if (empty($user_input)) {
    echo json_encode([
        "status" => "fail",
        "error" => "field is empty"
    ]);
    exit();
}

// PostgreSQL query
$sql = "SELECT * FROM users 
        WHERE (username = $1 OR email = $2)
        AND password != ''";

// Execute prepared query
$result = pg_query_params($conn, $sql, array($user_input, $user_input));

if (!$result) {
    echo json_encode([
        "status" => "fail",
        "error" => pg_last_error($conn)
    ]);
    exit();
}

// Check if user exists
if (pg_num_rows($result) > 0) {

    $row = pg_fetch_assoc($result);

    echo json_encode([
        "status" => "success",
        "msg" => "user found",
        "user" => $row
    ]);

} else {

    echo json_encode([
        "status" => "fail",
        "error" => "user not found"
    ]);
}

// Close connection
pg_close($conn);
?>
