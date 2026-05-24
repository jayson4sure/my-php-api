<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, authentication");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

require 'db_connect.php';

header('Content-Type: application/json');

function send($data) {
    echo json_encode($data);
    exit;
}

$email = isset($_REQUEST['email']) ? trim($_REQUEST['email']) : '';

if ($email === '') {
    send(['status' => 'error', 'error' => 'Email is required.']);
}

$result = pg_query_params(
    $conn,
    "SELECT password FROM users WHERE email = $1",
    array($email)
);

if (!$result) {
    send(['status' => 'error', 'error' => pg_last_error($conn)]);
}

if (pg_num_rows($result) === 0) {
    send(['status' => 'error', 'error' => 'email does not exist.']);
}

$row = pg_fetch_assoc($result);

if (empty($row['password'])) {
    send(['status' => 'success', 'message' => 'proceed to sign-up']);
} else {
    send(['status' => 'success', 'message' => 'user already signed-up']);
}

pg_close($conn);
?>
