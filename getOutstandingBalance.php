<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header('Content-Type: application/json');

require 'db_connect.php';

if (!$conn) {
    die(json_encode([
        "status" => "fail",
        "message" => "0",
        "error" => "Database connection failed"
    ]));
}

// Auth
$auth = $_SERVER['HTTP_AUTHENTICATION'] ?? '';
list($id, $auth_token) = explode(':', $auth) + [null, null];
$user_id = intval($id);

try {
    $query = "
        SELECT 
            SUM(las.payment) AS outstanding_balance
        FROM loans l
        JOIN loans_amortization la 
            ON l.loan_id = la.loan_id
        JOIN loans_amortization_schedule las 
            ON la.schedule_id = las.schedule_id
        WHERE l.user_id = $1
          AND las.status = 'pending'
    ";

    $result = pg_query_params($conn, $query, [$user_id]);

    if ($result) {
        $row = pg_fetch_assoc($result);
        $balance = $row ? $row['outstanding_balance'] : null;

        if ($balance === null) {
            echo json_encode([
                "status" => "fail",
                "message" => "0",
                "error" => "No pending balance."
            ]);
        } else {
            echo json_encode([
                "status" => "success",
                "message" => (string)$balance,
                "error" => ""
            ]);
        }
    } else {
        echo json_encode([
            "status" => "fail",
            "message" => "0",
            "error" => pg_last_error($conn)
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        "status" => "fail",
        "message" => "0",
        "error" => $e->getMessage()
    ]);
}

pg_close($conn);
?>
