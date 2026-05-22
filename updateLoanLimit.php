<?php
header('Content-Type: application/json');

require 'db_connect.php';

if (!$conn) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed."]);
    exit;
}

// Validate input
if (!isset($_GET['limit'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing 'limit' parameter."]);
    exit;
}

$limit = $_GET['limit'];

if (!is_numeric($limit)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid limit value. Must be numeric."]);
    exit;
}

try {
    $query = "
        UPDATE users
        SET loan_limit = $1
        RETURNING user_id
    ";

    $result = pg_query_params($conn, $query, [$limit]);

    if ($result) {
        $count = pg_num_rows($result);

        echo json_encode([
            "success" => true,
            "message" => "Loan limits updated successfully.",
            "affected_rows" => $count
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "error" => pg_last_error($conn)
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => $e->getMessage()
    ]);
}

pg_close($conn);
?>
