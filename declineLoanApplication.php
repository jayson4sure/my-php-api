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
    die(json_encode(["status" => "error", "message" => "Database connection failed"]));
}

// Auth
$auth = $_SERVER['HTTP_AUTHENTICATION'] ?? '';
list($id, $auth_token) = explode(':', $auth) + [null, null];
$userId = intval($id);

// Inputs
$loan_application_id = intval($_REQUEST['loan_application_id'] ?? 0);
$loaner_user_id      = intval($_REQUEST['loaner_user_id'] ?? 0);
$amount              = floatval($_REQUEST['amount'] ?? 0);
$details             = trim($_REQUEST['details'] ?? '');

// Query
$query = "
UPDATE loan_applications
SET status = 'deferred', approved_by = $1
WHERE id = $2 AND user_id = $3
RETURNING id
";

$result = pg_query_params($conn, $query, [
    $userId,
    $loan_application_id,
    $loaner_user_id
]);

if ($result && pg_num_rows($result) > 0) {
    echo json_encode([
        "status" => "success",
        "message" => "Loan application rejected (deferred).",
        "error" => ""
    ]);

    // OPTIONAL: notification (you must adapt your function to pg)
    // addNotificationPg(...);

} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to reject loan application.",
        "error" => pg_last_error($conn)
    ]);
}

pg_close($conn);
?>
