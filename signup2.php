<?php
header('Content-Type: application/json');

$conn = pg_connect("
    host=YOUR_HOST
    port=5432
    dbname=YOUR_DB
    user=YOUR_USER
    password=YOUR_PASSWORD
");

if (!$conn) {
    die(json_encode([
        'status' => 'error',
        'error' => 'Database connection failed'
    ]));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_REQUEST['email'] ?? '');
    $password = trim($_REQUEST['password'] ?? '');

    if (empty($email)) {
        echo json_encode([
            'status' => 'error',
            'error' => 'Email is required.'
        ]);
        exit;
    }

    $passwordhash = !empty($password)
        ? password_hash($password, PASSWORD_BCRYPT)
        : "";

    // Check if user exists
    $checkQuery = "SELECT user_id FROM users WHERE email = $1";
    $checkResult = pg_query_params($conn, $checkQuery, [$email]);

    if (!$checkResult || pg_num_rows($checkResult) == 0) {
        echo json_encode([
            'status' => 'error',
            'error' => 'email does not exist.'
        ]);
        exit;
    }

    // Update password
    $updateQuery = "
        UPDATE users 
        SET password = $1 
        WHERE email = $2
        RETURNING user_id
    ";

    $updateResult = pg_query_params($conn, $updateQuery, [
        $passwordhash,
        $email
    ]);

    if ($updateResult && pg_num_rows($updateResult) > 0) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Password updated successfully.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'error' => pg_last_error($conn)
        ]);
    }

} else {
    echo json_encode([
        'status' => 'error',
        'error' => 'Invalid request method.'
    ]);
}

pg_close($conn);
?>
