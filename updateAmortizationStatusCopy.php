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
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ================================
// DB CONNECT
// ================================
$conn = pg_connect("
    host=YOUR_HOST
    port=5432
    dbname=YOUR_DB
    user=YOUR_USER
    password=YOUR_PASSWORD
");

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
// VALIDATION
// ================================
if (!isset($_GET['status'], $_GET['loan_id'], $_POST['ids'])) {
    echo json_encode([
        'status'  => 'fail',
        'message' => '',
        'error'   => 'Missing required parameters: status, loan_id, or ids.'
    ]);
    exit;
}

$status  = $_GET['status'];
$loan_id = (int) $_GET['loan_id'];
$idsRaw  = $_POST['ids'];

// ================================
// PARSE IDS
// ================================
$decodedIds = json_decode($idsRaw, true);

if (is_array($decodedIds)) {
    $ids = array_map('intval', $decodedIds);
} else {
    $ids = array_map('intval', explode(',', $idsRaw));
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
// SQL (PostgreSQL version)
// ================================
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
