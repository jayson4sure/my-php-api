<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: authentication, Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json");

include 'db_connect.php';
include 'authenticate_user.php';
require_once 'notification_functions.php';

$auth = isset($_SERVER['HTTP_AUTHENTICATION'])
    ? trim($_SERVER['HTTP_AUTHENTICATION'])
    : '';

list($user_id, $auth_token) =
    explode(':', $auth) + [null, null];

$amount = isset($_REQUEST['amount'])
    ? intval($_REQUEST['amount'])
    : 0;

$status = isset($_REQUEST['status']) &&
          !empty(trim($_REQUEST['status']))
    ? trim($_REQUEST['status'])
    : 'pending';

// Validate fields
if ($user_id == 0 || $amount <= 0) {

    $response = array(
        'status' => 'error',
        'error' => 'Missing required fields.'
    );

} else {

    $sql = "
        INSERT INTO loan_applications (
            user_id,
            date,
            amount,
            status
        )
        VALUES (
            $1,
            CURRENT_DATE,
            $2,
            $3
        )
    ";

    $result = pg_query_params(
        $conn,
        $sql,
        array(
            $user_id,
            $amount,
            $status
        )
    );

    if ($result) {

        $response = array(
            'status' => 'success',
            'message' => 'Loan application added successfully.',
            'error' => ''
        );

    } else {

        $response = array(
            'status' => 'error',
            'message' => '',
            'error' => pg_last_error($conn)
        );
    }
}

// Add notification
addNotification(
    $conn,
    "loan-application",
    $user_id,
    "Loan Application Submitted",
    "Your loan request of ₱" .
    number_format($amount, 2) .
    " has been successfully submitted and is now under review."
);

pg_close($conn);

echo json_encode($response);
?>
