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

// Auth
$auth = $_SERVER['HTTP_AUTHENTICATION'] ?? '';
list($id, $auth_token) = explode(':', $auth) + [null, null];

$user_id = intval($id);
$loan_id = $_REQUEST['loan_id'] ?? null;

$query = "
SELECT 
    l.loan_id,
    l.user_id,
    l.months_payable,

    COUNT(*) FILTER (WHERE las.status = 'PAID') AS progress,

    CASE 
        WHEN COUNT(*) FILTER (
            WHERE las.due_dates < CURRENT_DATE
            AND las.status <> 'PAID'
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
$index = 2;

// Optional filter
if (!empty($loan_id)) {
    $query .= " AND l.loan_id = $" . $index;
    $params[] = $loan_id;
}

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

$result = pg_query_params($conn, $query, $params);

$loans = [];

if ($result) {
    while ($row = pg_fetch_assoc($result)) {
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
