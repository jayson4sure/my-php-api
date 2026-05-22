<?php
// Get authentication header from request
$auth = isset($_SERVER['HTTP_AUTHENTICATION']) ? trim($_SERVER['HTTP_AUTHENTICATION']) : '';

// Check if authentication parameter is provided
if (!$auth) {
    $response = array(
        'status' => 'error',
        'error' => 'Authentication failed: Missing credentials.'
    );
    echo json_encode($response);
    exit;
}

// Split authentication into user_id and token
list($auth_user_id, $auth_token) = explode(':', $auth) + [null, null];

// Check if user_id and token are valid
if (!$auth_user_id || !$auth_token) {
    $response = array(
        'status' => 'error',
        'error' => 'Authentication failed: Invalid format for authentication.'
    );
    echo json_encode($response);
    exit;
}

// PostgreSQL query
$auth_query = "SELECT user_id FROM users WHERE user_id = $1 AND token = $2 AND privilege >= 1";
$auth_result = pg_query_params($conn, $auth_query, array($auth_user_id, $auth_token));

if (!$auth_result || pg_num_rows($auth_result) === 0) {
    $response = array(
        'status' => 'error',
        'error' => 'Authentication failed: Administration login required.'
    );
    echo json_encode($response);
    exit;
}
?>
