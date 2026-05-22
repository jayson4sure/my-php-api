<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: authentication");

require 'db_connect.php';

if (!$conn) {
    die(json_encode([
        "status" => "error",
        "error" => "Database connection failed"
    ]));
}

// Auth
$auth = $_SERVER['HTTP_AUTHENTICATION'] ?? '';
$authParts = explode(':', $auth);
$user_id = isset($authParts[0]) ? intval($authParts[0]) : null;

// Status array (JSON input)
$statusListJson = $_REQUEST['status'] ?? '[]';
$statusList = json_decode($statusListJson, true);

// Base query
$query = "
SELECT 
    la.id,
    la.date AS application_date,
    la.amount AS loan_amount,
    la.status AS application_status,
    u.user_id,
    u.firstname,
    u.lastname,
    u.middlename,
    u.suffix,
    u.email,
    u.dob,
    u.address,
    u.sub_contactno,
    u.contactno,
    u.photo,
    u.loan_limit,
    l.months_payable,
    l.interest_rate
FROM loan_applications la
LEFT JOIN users u ON la.user_id = u.user_id
LEFT JOIN loans l ON la.id = l.loan_application_id
WHERE la.user_id = $1
";

$params = [$user_id];
$index = 2;

// Status filter (PostgreSQL array style)
if (!empty($statusList) && is_array($statusList)) {
    $query .= " AND la.status = ANY($" . $index . ")";
    $params[] = '{' . implode(',', $statusList) . '}';
}

$result = pg_query_params($conn, $query, $params);

if ($result && pg_num_rows($result) > 0) {
    $loanApplications = [];

    while ($row = pg_fetch_assoc($result)) {
        $cleanRow = array_map(fn($v) => $v === null ? '' : $v, $row);
        $loanApplications[] = $cleanRow;
    }

    echo json_encode([
        'status' => 'success',
        'data' => $loanApplications
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'error' => 'No loan applications found.'
    ]);
}

pg_close($conn);
?>
