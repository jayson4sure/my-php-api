<?php
// ✅ CORS (what you requested)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, authentication");

// 🔴 CRITICAL: prevent warnings breaking JSON
error_reporting(0);
ini_set('display_errors', 0);

require 'db_connect.php';

header('Content-Type: application/json');

// Always return JSON (even on fatal errors)
function send($data) {
    echo json_encode($data);
    exit;
}

// Get email safely (supports GET + POST)
$email = isset($_REQUEST['email']) ? trim($_REQUEST['email']) : '';

if ($email === '') {
    send([
        'status' => 'error',
        'error' => 'Email is required.'
    ]);
}

// Validate DB connection
if (!$conn) {
    send([
        'status' => 'error',
        'error' => 'Database connection failed.'
    ]);
}

// Query safely
$result = @pg_query_params(
    $conn,
    "SELECT user_id FROM users WHERE email = $1",
    array($email)
);

// Check query failure
if ($result === false) {
    send([
        'status' => 'error',
        'error' => 'Database query failed.'
    ]);
}

// Check result
if (pg_num_rows($result) === 0) {
    send([
        'status' => 'error',
        'error' => 'Email does not exist.'
    ]);
}

// Success
send([
    'status' => 'success',
    'message' => 'Email exists.'
]);

// Close connection
pg_close($conn);
?>
