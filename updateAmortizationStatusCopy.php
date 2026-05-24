<?php
// ================================
// DEBUG (REMOVE IN PRODUCTION)
// ================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ================================
// CORS
// ================================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ================================
// DB CONNECT
// ================================
require 'db_connect.php';

if (!$conn) {
    die(json_encode([
        'status'  => 'fail',
        'message' => '',
        'error'   => 'Database connection failed'
    ]));
}

// ================================
// AUTH
// ================================
include 'authenticate_admin.php';

// ================================
// READ INPUT (JSON + fallback)
// ================================
$input = json_decode(file_get_contents('php://input'), true);

// Accept both JSON and legacy GET/POST
$status  = $input['status']  ?? $_GET['status']  ?? null;
$loan_id = $input['loan_id'] ?? $_GET['loan_id'] ?? null;
$idsRaw  = $input['ids']     ?? $_POST['ids']    ?? null;

// ================================
// VALIDATION
// ================================
if (!$status || !$loan_id || !$idsRaw) {
    echo json_encode([
        'status'  => 'fail',
        'message' => '',
        'error'   => 'Missing required parameters: status, loan_id, or ids.'
    ]);
    exit;
}

$loan_id = (int)$loan_id;

// ================================
// PARSE IDS
// ================================
if (is_array($idsRaw)) {
    $ids = array_map('intval', $idsRaw);
} else {
    $decodedIds = json_decode($idsRaw, true);

    if (is_array($decodedIds)) {
        $ids = array_map('intval', $decodedIds);
    } else {
        $ids = array_map('intval', explode(',', $idsRaw));
    }
}

$ids = array_filter($ids);

if (empty($ids)) {
    echo json_encode([
        'status'  => 'fail',
        'message' => '',
        'error'   => 'No valid schedule IDs provided.'
    ]);
    exit;
}

// ================================
// SQL (PostgreSQL)
// ================================
$query = "
    UPDATE loans_amortization_schedule AS las
    SET status = $1
    FROM loans_amortization AS la
    WHERE la.schedule_id = las.schedule_id
      AND la.loan_id = $2
      AND las.schedule_id = ANY($3::int[])
    RETURNING las.schedule_id
";

// Convert PHP array → PostgreSQL array format
$pgArray = '{' . implode(',', $ids) . '}';

// ================================
// EXECUTE
// ================================
$result = pg_query_params($conn, $query, [
    $status,
    $loan_id,
    $pgArray
]);

if ($result) {
    $affected_rows = pg_num_rows($result);

    echo json_encode([
        'status'  => 'success',
        'message' => 'Loan schedule statuses updated successfully.',
        'error'   => '',
        'affected_rows' => $affected_rows
    ]);
} else {
    echo json_encode([
        'status'  => 'fail',
        'message' => '',
        'error'   => pg_last_error($conn)
    ]);
}

// ================================
// CLEANUP
// ================================
pg_close($conn);
?>
