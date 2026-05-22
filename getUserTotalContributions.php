<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: authentication");

$conn = pg_connect("
    host=YOUR_HOST
    port=5432
    dbname=YOUR_DB
    user=YOUR_USER
    password=YOUR_PASSWORD
");

if (!$conn) {
    die(json_encode([
        "status" => "fail",
        "error" => "Database connection failed"
    ]));
}

// Validate user_id
if (!isset($_REQUEST['user_id']) || !is_numeric($_REQUEST['user_id'])) {
    echo json_encode([
        "status" => "fail",
        "error" => "Invalid or missing user_id"
    ]);
    exit;
}

$user_id = intval($_REQUEST['user_id']);

try {
    $query = "
        SELECT SUM(contribution) AS total
        FROM contributions
        WHERE user_id = $1
    ";

    $result = pg_query_params($conn, $query, [$user_id]);

    if ($result) {
        $row = pg_fetch_assoc($result);
        $total = $row ? $row['total'] : null;

        // NULL means no contributions (or user has no rows)
        if ($total === null) {
            echo json_encode([
                "status" => "fail",
                "error" => "User not found."
            ]);
        } else {
            echo json_encode([
                "status" => "success",
                "total_contribution" => floatval($total)
            ]);
        }
    } else {
        echo json_encode([
            "status" => "fail",
            "error" => pg_last_error($conn)
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        "status" => "fail",
        "error" => $e->getMessage()
    ]);
}

pg_close($conn);
?>
