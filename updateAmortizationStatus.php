<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$conn = pg_connect("
    host=YOUR_HOST
    port=5432
    dbname=YOUR_DB
    user=YOUR_USER
    password=YOUR_PASSWORD
");

if (!$conn) {
    die(json_encode([
        'status' => 'fail',
        'message' => '',
        'error' => 'Database connection failed'
    ]));
}

// Validate params
if (!isset($_GET['status']) || !isset($_GET['loan_id']) || !isset($_POST['ids'])) {
    echo json_encode([
        'status' => 'fail',
        'message' => '',
        'error' => 'Missing required parameters: status, loan_id, or ids.'
    ]);
    exit;
}

$status  = $_GET['status'];
$loan_id = intval($_GET['loan_id']);
$idsRaw  = $_POST['ids'];

// Parse IDs
$decodedIds = json_decode($idsRaw, true);

if (is_array($decodedIds)) {
    $ids = array_map('intval', $decodedIds);
} else {
    $ids = array_map('intval', explode(',', $idsRaw));
}

if (empty($ids)) {
    echo json_encode([
        'status' => 'fail',
        'message' => '',
        'error' => 'No valid schedule IDs provided.'
    ]);
    exit;
}

try {
    /*
      PostgreSQL equivalent of UPDATE JOIN:
      use FROM clause
    */
    $query = "
        UPDATE loans_amortization_schedule las
        SET status = $1
        FROM loans_amortization la
        WHERE la.schedule_id = las.schedule_id
          AND la.loan_id = $2
          AND las.schedule_id = ANY($3)
        RETURNING las.schedule_id
    ";

    // Convert PHP array → PostgreSQL array
    $pgArray = '{' . implode(',', $ids) . '}';

    $result = pg_query_params($conn, $query, [
        $status,
        $loan_id,
        $pgArray
    ]);

    if ($result) {
        $updatedCount = pg_num_rows($result);

        echo json_encode([
            'status' => 'success',
            'message' => "Updated {$updatedCount} schedule(s).",
            'error' => ''
        ]);
    } else {
        echo json_encode([
            'status' => 'fail',
            'message' => '',
            'error' => pg_last_error($conn)
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'fail',
        'message' => '',
        'error' => $e->getMessage()
    ]);
}

pg_close($conn);
?>
