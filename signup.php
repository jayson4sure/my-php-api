<?php
require 'db_connect.php';

header('Content-Type: application/json');

    // $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $email = isset($_REQUEST['email']) ? trim($_REQUEST['email']) : '';

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
    } else {
        echo json_encode([
            'status' => 'success',
            'message' => 'Email exists.'
        ]);
    }

    pg_close($conn);
?>
