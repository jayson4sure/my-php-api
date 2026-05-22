<?php
header("Content-Type: application/json");

require_once 'db_connect.php';
require_once 'authenticate_admin.php';

// Authenticate user
$auth = isset($_SERVER['HTTP_AUTHENTICATION'])
    ? trim($_SERVER['HTTP_AUTHENTICATION'])
    : '';

$authParts = explode(':', $auth);

$user_id = isset($authParts[0]) ? intval($authParts[0]) : null;
$auth_token = isset($authParts[1]) ? $authParts[1] : null;

if (!$user_id || !$auth_token) {
    echo json_encode(["error" => "Invalid authentication header"]);
    exit;
}

// Check request method
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["error" => "Invalid request method"]);
    exit;
}

// Validate required parameters
if (
    !isset($_GET['schedule']) ||
    !isset($_GET['loan_application_id']) ||
    !isset($_GET['interest_rate']) ||
    !isset($_GET['months_payable'])
) {
    echo json_encode(["error" => "Missing required parameters"]);
    exit;
}

$scheduleData = json_decode($_GET['schedule'], true);

$loan_application_id = intval($_GET['loan_application_id']);
$interest_rate = floatval($_GET['interest_rate']);
$months_payable = intval($_GET['months_payable']);

if (!is_array($scheduleData) || empty($scheduleData)) {
    echo json_encode(["error" => "Invalid schedule data"]);
    exit;
}

// Check if loan application exists and pending
$checkSQL = "SELECT id 
             FROM loan_applications 
             WHERE id = $1 
             AND status = 'pending'";

$checkResult = pg_query_params(
    $conn,
    $checkSQL,
    array($loan_application_id)
);

if (!$checkResult || pg_num_rows($checkResult) === 0) {
    echo json_encode([
        "error" => "Loan application not found or already approved."
    ]);
    exit;
}

// Get borrower user_id
$appQuery = "SELECT user_id 
             FROM loan_applications 
             WHERE id = $1";

$appResult = pg_query_params(
    $conn,
    $appQuery,
    array($loan_application_id)
);

$appRow = pg_fetch_assoc($appResult);

$borrower_id = $appRow['user_id'];

// Insert loan
$insertLoanSQL = "
    INSERT INTO loans (
        user_id,
        loan_application_id,
        interest_rate,
        status,
        months_payable
    )
    VALUES ($1, $2, $3, 'credited', $4)
    RETURNING loan_id
";

$loanResult = pg_query_params(
    $conn,
    $insertLoanSQL,
    array(
        $borrower_id,
        $loan_application_id,
        $interest_rate,
        $months_payable
    )
);

if (!$loanResult) {
    echo json_encode([
        "error" => pg_last_error($conn)
    ]);
    exit;
}

$loanRow = pg_fetch_assoc($loanResult);
$loanId = $loanRow['loan_id'];

// Insert schedules
foreach ($scheduleData as $schedule) {

    $date = $schedule['date'];
    $payment = floatval($schedule['payments']);
    $status = $schedule['status'];

    // Insert schedule
    $scheduleSQL = "
        INSERT INTO loans_amortization_schedule (
            due_dates,
            payment,
            status
        )
        VALUES ($1, $2, $3)
        RETURNING schedule_id
    ";

    $scheduleResult = pg_query_params(
        $conn,
        $scheduleSQL,
        array($date, $payment, $status)
    );

    if (!$scheduleResult) {
        echo json_encode([
            "error" => "Failed to insert schedule"
        ]);
        exit;
    }

    $scheduleRow = pg_fetch_assoc($scheduleResult);
    $scheduleId = $scheduleRow['schedule_id'];

    // Link amortization
    $amortizationSQL = "
        INSERT INTO loans_amortization (
            loan_id,
            schedule_id
        )
        VALUES ($1, $2)
    ";

    pg_query_params(
        $conn,
        $amortizationSQL,
        array($loanId, $scheduleId)
    );
}

// Update loan application
$updateSQL = "
    UPDATE loan_applications
    SET approved_by = $1,
        status = 'credited'
    WHERE id = $2
";

$updateResult = pg_query_params(
    $conn,
    $updateSQL,
    array($user_id, $loan_application_id)
);

if (!$updateResult) {
    echo json_encode([
        "error" => pg_last_error($conn)
    ]);
    exit;
}

echo json_encode([
    "success" => "Loan application approved",
    "loan_id" => $loanId
]);

pg_close($conn);
?>
