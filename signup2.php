<?php
// ✅ CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, authentication");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

// 🔴 Prevent warnings breaking JSON
error_reporting(0);
ini_set('display_errors', 0);

require 'db_connect.php';

header('Content-Type: application/json');

// Always return JSON
function send($data) {
    echo json_encode($data);
    exit;
}

// Validate DB connection
if (!$conn) {
    send([
        'status' => 'error',
        'error' => 'Database connection failed'
    ]);
}

// Get inputs safely
$email = isset($_REQUEST['email']) ? trim($_REQUEST['email']) : '';
$password = isset($_REQUEST['password']) ? trim($_REQUEST['password']) : '';

if ($email === '') {
    send([
        'status' => 'error',
        'error' => 'Email is required.'
    ]);
}

// OPTIONAL: require password if needed
if ($password === '') {
    send([
        'status' => 'error',
        'error' => 'Password is required.'
    ]);
}

// Hash password
$passwordhash = password_hash($password, PASSWORD_BCRYPT);

// Check if user exists
$checkResult = pg_query_params(
    $conn,
    "SELECT user_id FROM users WHERE email = $1",
    array($email)
);

if (!$checkResult) {
    send([
        'status' => 'error',
        'error' => pg_last_error($conn)
    ]);
}

if (pg_num_rows($checkResult) === 0) {
    send([
        'status' => 'error',
        'error' => 'email does not exist.'
    ]);
}

// Update password
$updateResult = pg_query_params(
    $conn,
    "UPDATE users SET password = $1 WHERE email = $2 RETURNING user_id",
    array($passwordhash, $email)
);

if (!$updateResult) {
    send([
        'status' => 'error',
        'error' => pg_last_error($conn)
    ]);
}

if (pg_num_rows($updateResult) > 0) {
    send([
        'status' => 'success',
        'message' => 'Password updated successfully.'
    ]);
}

// Fallback (should not happen)
send([
    'status' => 'error',
    'error' => 'Update failed.'
]);

pg_close($conn);
?>
