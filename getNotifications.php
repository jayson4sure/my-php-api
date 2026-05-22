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
        "status" => "fail",
        "data" => [],
        "error" => "Database connection failed"
    ]));
}

// Default response
$response = [
    "status" => 'fail',
    "data" => [],
    "error" => "No notifications found."
];

// Auth
$auth = $_SERVER['HTTP_AUTHENTICATION'] ?? '';
list($id, $auth_token) = explode(':', $auth) + [null, null];
$user_id = intval($id);

try {
    // Params
    $notifType = $_REQUEST['notif-type'] ?? null;
    $notifId   = isset($_REQUEST['notif-id']) ? intval($_REQUEST['notif-id']) : null;

    // Base query
    $query = "SELECT * FROM notifications WHERE 1=1";
    $params = [];
    $index = 1;

    // Dynamic conditions
    if (!empty($notifType)) {
        $query .= " AND notif_type = $" . $index;
        $params[] = $notifType;
        $index++;
    }

    if (!empty($notifId)) {
        $query .= " AND notification_id = $" . $index;
        $params[] = $notifId;
        $index++;
    }

    if (!empty($user_id)) {
        $query .= " AND user_id = $" . $index;
        $params[] = $user_id;
        $index++;
    }

    $query .= " ORDER BY notification_id DESC";

    // Execute
    $result = pg_query_params($conn, $query, $params);

    if ($result) {
        $notifications = [];

        while ($row = pg_fetch_assoc($result)) {
            $notifications[] = $row;
        }

        if (!empty($notifications)) {
            $response['status'] = "success";
            $response['data'] = $notifications;
            $response['error'] = "";
        } else {
            $response['status'] = "fail";
            $response['data'] = [];
            $response['error'] = "No notification found.";
        }
    } else {
        $response['error'] = pg_last_error($conn);
    }

} catch (Exception $e) {
    $response['status'] = "fail";
    $response['data'] = [];
    $response['error'] = "Error: " . $e->getMessage();
}

echo json_encode($response);

pg_close($conn);
?>
