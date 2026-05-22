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
    die(json_encode(["status" => "error", "error" => "Database connection failed"]));
}

// Get status
$statusRaw = $_REQUEST['status'] ?? '';
$statusList = [];

if (!empty($statusRaw)) {
    $trimmed = trim($statusRaw, "[]'\"");
    $split = explode(',', $trimmed);
    foreach ($split as $status) {
        $status = trim($status, " '\"");
        if (!empty($status)) {
            $statusList[] = $status;
        }
    }
}

if (empty($statusList)) {
    $statusList = ['pending'];
}

// Convert to PostgreSQL array format
$statusArray = '{' . implode(',', $statusList) . '}';

// Search term
$term = $_REQUEST['term'] ?? null;

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
    l.interest_rate,
    l.loan_id
FROM loan_applications la
LEFT JOIN users u ON la.user_id = u.user_id
LEFT JOIN loans l ON la.id = l.loan_application_id
WHERE la.status = ANY($1)
";

$params = [$statusArray];

if (!empty($term)) {
    $query .= " AND (
        u.firstname ILIKE '%' || $2 || '%' OR
        u.lastname ILIKE '%' || $2 || '%' OR
        u.middlename ILIKE '%' || $2 || '%' OR
        (u.firstname || ' ' || u.lastname) ILIKE '%' || $2 || '%' OR
        (u.lastname || ' ' || u.firstname) ILIKE '%' || $2 || '%'
    )";
    $params[] = $term;
}

$result = pg_query_params($conn, $query, $params);

if ($result && pg_num_rows($result) > 0) {
    $data = [];

    while ($row = pg_fetch_assoc($result)) {
        $cleanRow = array_map(fn($v) => $v === null ? '' : $v, $row);
        $data[] = $cleanRow;
    }

    echo json_encode(["status" => "success", "data" => $data]);
} else {
    echo json_encode(["status" => "error", "error" => "No loan applications found."]);
}

pg_close($conn);
?>
