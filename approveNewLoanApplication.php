<?php
    ";

    pg_query_params(
        $conn,
        $insertAmortizationSQL,
        array($loanId, $scheduleId)
    );
}

// Update loan application
$updateAppSQL = "
    UPDATE loan_applications
    SET approved_by = $1,
        status = 'approved'
    WHERE id = $2
";

$updateResult = pg_query_params(
    $conn,
    $updateAppSQL,
    array($user_id, $loan_application_id)
);

if (!$updateResult) {
    echo json_encode([
        "status" => "error",
        "message" => "",
        "error" => "Failed to update loan application approval"
    ]);
    exit;
}

echo json_encode([
    "status" => "success",
    "message" => "Loan application approved.",
    "error" => ""
]);

// Get amount
$getAmountSQL = "SELECT amount FROM loan_applications WHERE id = $1";
$getAmountResult = pg_query_params($conn, $getAmountSQL, array($loan_application_id));

$amount = 0;
if ($getAmountResult && pg_num_rows($getAmountResult) > 0) {
    $amountRow = pg_fetch_assoc($getAmountResult);
    $amount = $amountRow['amount'];
}

// Add notification
$notifResult = addNotification(
    $conn,
    "loan-approved",
    $loaner_user_id,
    "Application Approved",
    "Your loan application for ₱" . number_format($amount, 2) . " is approved. Amortization schedule is now active."
);

pg_close($conn);
?>
