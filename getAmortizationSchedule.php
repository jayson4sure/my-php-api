<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

require 'db_connect.php';

if (!$conn) {
    die(json_encode(["error" => "Database connection failed"]));
}

// Validate
if (!isset($_GET['loan_id'])) {
    echo json_encode(["error" => "Missing required parameter: loan_id"]);
    exit;
}

$loan_id = intval($_GET['loan_id']);

$query = "
SELECT 
    las.schedule_id,
    las.due_dates,
    las.payment,
    las.status
FROM loans_amortization_schedule las
INNER JOIN loans_amortization la 
    ON las.schedule_id = la.schedule_id
WHERE la.loan_id = $1
";

$result = pg_query_params($conn, $query, [$loan_id]);

$data = [];

if ($result) {
    while ($row = pg_fetch_assoc($result)) {

        $row['schedule_id'] = intval($row['schedule_id']);
        
        $data[] = $row;
    }
}

if (!empty($data)) {
    echo json_encode(["status" => "success", "data" => $data]);
} else {
    echo json_encode([
        "status" => "error",
        "error" => "No schedule found."
    ]);
}

pg_close($conn);
?>
