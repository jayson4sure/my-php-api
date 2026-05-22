<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: authentication");

// Include PostgreSQL database connection
require 'db_connect.php';

$user_input = $_REQUEST['username'] ?? '';
$pass_input = $_REQUEST['password'] ?? '';

// Check if username and password are provided
if (empty($user_input) || empty($pass_input)) {
    echo json_encode([
        "status" => "fail",
        "error" => "username or password is missing"
    ]);
    exit();
}

// PostgreSQL query
$sql = "SELECT * FROM users 
        WHERE username = $1 OR email = $2";

// Execute prepared query
$result = pg_query_params(
    $conn,
    $sql,
    array($user_input, $user_input)
);

// Check query success
if (!$result) {
    echo json_encode([
        "status" => "fail",
        "error" => pg_last_error($conn)
    ]);
    exit();
}

// Check if user exists
if (pg_num_rows($result) > 0) {

    $row = pg_fetch_assoc($result);

    // Verify password
    if (password_verify($pass_input, $row['password'])) {

        // Remove password from response
        unset($row['password']);

        // Generate token
        $token = bin2hex(random_bytes(32));

        // Update token in database
        $update_sql = "UPDATE users 
                       SET token = $1 
                       WHERE user_id = $2";

        $update_result = pg_query_params(
            $conn,
            $update_sql,
            array($token, $row['user_id'])
        );

        if (!$update_result) {
            echo json_encode([
                "status" => "fail",
                "error" => pg_last_error($conn)
            ]);
            exit();
        }

        // Add token to returned user data
        $row['token'] = $token;

        echo json_encode([
            "status" => "success",
            "user" => $row,
            "error" => ""
        ]);

    } else {

        echo json_encode([
            "status" => "fail",
            "error" => "invalid username or password"
        ]);

    }

} else {

    echo json_encode([
        "status" => "fail",
        "error" => "user not found"
    ]);

}

// Close PostgreSQL connection
pg_close($conn);
?>
