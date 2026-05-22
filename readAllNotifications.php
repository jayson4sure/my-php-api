<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: authentication, Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require 'db_connect.php';

if (!$conn) {
    die(json_encode([
        "status" => "error",
        "message" => "",
        "error" => "Database connection failed"
    ]));
}

// Auth
$auth = $_SERVER['HTTP_AUTHENTICATION'] ?? '';
list($id, $auth_token) = explode(':', $auth) + [null, null];
$userId = intval($id);

// Update all notifications
$query = "
    UPDATE notifications 
    SET is_read = 1 
    WHERE user_id = $1
    RETURNING notification_id
";

$result = pg_query_params($conn, $query, [$userId]);

if ($result) {
    $count = pg_num_rows($result);

    echo json_encode([
        "status" => "success",
        "message" => "Marked {$count} notifications as read",
        "error" => ""
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "",
        "error" => pg_last_error($conn)
    ]);
}

pg_close($conn);
?>
