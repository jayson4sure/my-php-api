<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: authentication");

include 'db_connect.php';
require_once 'authenticate_admin.php';

// Get admin password
$admin_password = $_GET['admin_password'] ?? '';

$auth = isset($_SERVER['HTTP_AUTHENTICATION'])
    ? trim($_SERVER['HTTP_AUTHENTICATION'])
    : '';

list($user_id, $auth_token) =
    explode(':', $auth) + [null, null];

// PostgreSQL query
$sql = "
    SELECT *
    FROM users
    WHERE privilege_passcode = $1
    AND user_id = $2
";

$result = pg_query_params(
    $conn,
    $sql,
    array($admin_password, $user_id)
);

if (!$result) {

    echo json_encode([
        "status" => "error",
        "error" => pg_last_error($conn)
    ]);

    exit;
}

$users = [];
$privilege_type = 0;

while ($row = pg_fetch_assoc($result)) {

    $users[] = $row;

    if (isset($users[0]['privilege'])) {
        $privilege_type = $users[0]['privilege'];
    }
}

pg_close($conn);

// Return response
if (empty($users)) {

    echo json_encode([
        "status" => "error",
        "error" => "Incorrect code"
    ]);

} else {

    echo json_encode([
        "status" => "success",
        "message" => (string)$privilege_type
    ]);
}
?>
