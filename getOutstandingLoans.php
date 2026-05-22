<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header('Content-Type: application/json');

require 'db_connect.php';

if (!$conn) {
    die(json_encode([
        "status" => "error",
        "error" => "Database connection failed"
    ]));
}

// ================================
// AUTH
// ================================
$auth = $_SERVER['HTTP_AUTHENTICATION'] ?? '';
list($id, $auth_token) = explode(':', $auth) + [null, null];

$user_id = intval($id);
$loan_id = isset($_REQUEST['loan_id']) ? intval($_REQUEST['loan_id']) : null;

// ================================
// QUERY
// ================================
$query = "
SELECT 
    l.loan_id,
    l.user_id,
    l.months_payable,

    COUNT(*) FILTER (WHERE las.status = 'paid') AS progress,

    CASE 
        WHEN COUNT(*) FILTER (
            WHERE las.due_dates < CURRENT_DATE
            AND las.status <> 'paid'
        ) > 0 THEN 1
        ELSE 0
    END AS due_status,

    l.interest_rate,
    l.status,
    l.loan_application_id,
    l.date_approved,
    la.date   AS loan_application_date,
    la.amount AS loan_application_amount

FROM loans l
JOIN loan_applications la
    ON l.loan_application_id = la.id

LEFT JOIN loans_amortization lam
    ON lam.loan_id = l.loan_id

LEFT JOIN loans_amortization_schedule las
    ON las.schedule_id = lam.schedule_id

WHERE l.user_id = $1
";

$params = [$user_id];
$paramIndex = 2;

// Optional filter
if (!empty($loan_id)) {
    $query .= " AND l.loan_id = $" . $paramIndex;
    $params[] = $loan_id;
}

// GROUP BY (required)
$query .= "
GROUP BY 
    l.loan_id,
    l.user_id,
    l.months_payable,
    l.interest_rate,
    l.status,
    l.loan_application_id,
    l.date_approved,
    la.date,
    la.amount
";

// ================================
// EXECUTE
// ================================
$result = pg_query_params($conn, $query, $params);

$loans = [];

if ($result) {
    while ($row = pg_fetch_assoc($result)) {

        // 🔥 FORCE CORRECT TYPES
        $row['loan_id']               = intval($row['loan_id']);
        $row['user_id']              = intval($row['user_id']);
        $row['months_payable']       = intval($row['months_payable']);
        $row['progress']             = intval($row['progress']);
        $row['due_status']           = intval($row['due_status']);
        $row['loan_application_id']  = intval($row['loan_application_id']);

        // Optional: keep decimals as string OR float (your choice)
        // If Flutter expects string, keep as is
        // If you want float:
        // $row['interest_rate'] = floatval($row['interest_rate']);
        // $row['loan_application_amount'] = floatval($row['loan_application_amount']);

        $loans[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'data'   => $loans,
        'error'  => empty($loans) ? 'No loan records found.' : ''
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'error'  => pg_last_error($conn)
    ]);
}

pg_close($conn);
?>
