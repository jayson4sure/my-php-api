<?php
require 'db_connect.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (empty($email)) {
        echo json_encode([
            'status' => 'error',
            'error' => 'Email is required.'
        ]);
        exit;
    }

    // Check if email exists
    $result = pg_query_params($conn,
        "SELECT user_id FROM USERS WHERE email = $1",
        array($email)
    );

    if (pg_num_rows($result) == 0) {
        echo json_encode([
            'status' => 'error',
            'error' => 'Email does not exist.'
        ]);
        exit;
    }

    // Handle password
    if (!empty($password)) {
        $passwordhash = password_hash($password, PASSWORD_BCRYPT);
    } else {
        $passwordhash = "";
    }

    // Update password
    $update = pg_query_params($conn,
        "UPDATE USERS SET password = $1 WHERE email = $2",
        array($passwordhash, $email)
    );

    if ($update) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Password updated successfully.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'error' => 'Failed to update password.'
        ]);
    }

    pg_close($conn);

} else {
    echo json_encode([
        'status' => 'error',
        'error' => 'Invalid request method.'
    ]);
}
?>
