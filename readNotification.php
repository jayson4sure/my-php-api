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
        "status" => "error",
        "message" => "",
        "error" => "Database connection failed"
    ]));
}

// Auth
$auth = $_SERVER['HTTP_AUTHENTICATION'] ?? '';
list($id, $auth_token) = explode(':', $auth) + [null, null];
$user_id = intval($id);

$response = [
    'status' => 'fail',
    'message' => '',
    'error' => ''
];

try {
    $notification_id = isset($_REQUEST['notif-id']) ? intval($_REQUEST['notif-id']) : 0;

    if ($notification_id <= 0) {
        echo json_encode([
            "status" => "fail",
            "message" => "",
            "error" => "Invalid notif-id."
        ]);
        exit();
    }

    $query = "
        UPDATE notifications
        SET is_read = 1
        WHERE notification_id = $1 AND user_id = $2
        RETURNING notification_id
    ";

    $result = pg_query_params($conn, $query, [
        $notification_id,
        $user_id
    ]);

    if ($result && pg_num_rows($result) > 0) {
        $response['status'] = "success";
        $response['message'] = "Notification marked as read successfully.";
        $response['error'] = "";
    } else {
        $response['status'] = "fail";
        $response['message'] = "";
        $response['error'] = "No notification updated. Either it doesn't exist or already marked as read.";
    }

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);

pg_close($conn);
?>
