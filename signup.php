<?php
require 'db_connect.php'; // Include your database connection file

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_REQUEST['email']);
    $password = trim($_REQUEST['password']);
    $passwordhash = password_hash($password, PASSWORD_BCRYPT); // Encrypt password

    // Check if the email exists in the USERS table
    $stmt = $conn->prepare("SELECT user_id FROM USERS WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 0) {
        // Email does not exist
        echo json_encode([
            'status' => 'error',
            'error' => 'email does not exist.'
        ]);
    } else {
        if (empty($password)) {
            $passwordhash = "";
        }

        // Update privilege_passcode for the given email
        $stmt->close();
        $update_stmt = $conn->prepare("UPDATE USERS SET password = ? WHERE email = ?");
        $update_stmt->bind_param("ss", $passwordhash, $email);

        if ($update_stmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Password updated successfully.'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'error' => 'Failed to update password.'
            ]);
        }

        $update_stmt->close();
    }

    $conn->close();
} else {
    echo json_encode([
        'status' => 'error',
        'error' => 'Invalid request method.'
    ]);
}
?>
