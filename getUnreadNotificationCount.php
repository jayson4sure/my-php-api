<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: authentication, Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$conn = pg_connect("
    host=YOUR_HOST
    port=5432
    dbname=YOUR_DB
    user=YOUR_USER
    password=YOUR_PASSWORD
");

if (!$conn) {
    die(json_encode([
        "status" => "fail",
        "message" => "",
        "error" => "Database connection failed"
    ]));
}

// Default response
$response = [
    "status" => 'fail',
    "message" => '',
    "error" => ""
];

// Auth
$auth = $_SERVER['HTTP_AUTHENTICATION'] ?? '';
list($id, $auth_token) = explode(':', $auth) + [null, null];
$user_id = intval($id);

try {
    $query = "
        SELECT COUNT(*) AS unread_count
        FROM notifications
        WHERE user_id = $1 AND is_read = 0
    ";

    $result = pg_query_params($conn, $query, [$user_id]);

    if ($result) {
        $row = pg_fetch_assoc($result);
        $unreadCount = $row ? intval($row['unread_count']) : 0;

        $response['status'] = "success";
        $response['message'] = strval($unreadCount);
        $response['error'] = "";
    } else {
        $response['error'] = pg_last_error($conn);
    }

} catch (Exception $e) {
    $response['status'] = "fail";
    $response['message'] = "";
    $response['error'] = "Error: " . $e->getMessage();
}

echo json_encode($response);

pg_close($conn);
?>
