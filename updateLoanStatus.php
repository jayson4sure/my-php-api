<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require 'db_connect.php';

if (!$conn) {
    die(json_encode([
        'status' => 'fail',
        'message' => 'Database connection failed'
    ]));
}

include 'authenticate_admin.php';

// Inputs
$status  = $_GET['status'] ?? '';
$idsJson = $_POST['ids'] ?? '';

if ($status === '' || empty($idsJson)) {
    echo json_encode([
        'status' => 'fail',
        'message' => 'Missing status or ids.',
    ]);
    exit;
}

// Decode IDs
$ids = json_decode($idsJson, true);

if (!is_array($ids) || empty($ids)) {
    echo json_encode([
        'status' => 'fail',
        'message' => 'Invalid or empty ids array.',
    ]);
    exit;
}

$ids = array_map('intval', $ids);

// Convert to PostgreSQL array
$pgArray = '{' . implode(',', $ids) . '}';

try {
    /**
     * 1) UPDATE loan_applications
     */
    $query1 = "
        UPDATE loan_applications
        SET status = $1
        WHERE id = ANY($2)
        RETURNING id
    ";

    $result1 = pg_query_params($conn, $query1, [
        $status,
        $pgArray
    ]);

    if (!$result1) {
        echo json_encode([
            'status' => 'fail',
            'message' => pg_last_error($conn),
        ]);
        exit;
    }

    $total = pg_num_rows($result1);

    /**
     * 2) UPDATE loans (PostgreSQL JOIN equivalent)
     */
    $query2 = "
        UPDATE loans
        SET status = $1
        FROM loan_applications la
        WHERE loans.loan_application_id = la.id
          AND la.id = ANY($2)
        RETURNING loans.loan_id
    ";

    $result2 = pg_query_params($conn, $query2, [
        $status,
        $pgArray
    ]);

    if (!$result2) {
        echo json_encode([
            'status' => 'fail',
            'message' => pg_last_error($conn),
        ]);
        exit;
    }

    /**
     * 3) Response
     */
    $response = [
        'status' => 'success',
        'total'  => $total
    ];

    if ($total === 1) {
        $response['message'] = $ids[0];
    }

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'fail',
        'message' => $e->getMessage()
    ]);
}

pg_close($conn);
?>
