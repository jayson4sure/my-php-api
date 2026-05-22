<?php
// Always set CORS headers
header("Access-Control-Allow-Origin: *"); // Change '*' to your frontend domain in production
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight (OPTIONS) requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Include the database connection
require 'db.php';

// Use $_POST for POST request; fallback to $_REQUEST
$user_input = $_POST['username'] ?? $_REQUEST['username'] ?? '';

if (empty($user_input)) {
    echo json_encode(["error" => "field is empty"]);
    exit();
}

// Prepare SQL statement to fetch user data
$sql = "SELECT * FROM USERS WHERE (username = ? OR email = ?) AND password != ''";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $user_input, $user_input);
$stmt->execute();
$result = $stmt->get_result();

// Check if user exists
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode(["status" => "success", "msg" => "user found"]);
} else {
    echo json_encode(["status" => "fail", "error" => "user not found"]);
}

// Close statement and connection
$stmt->close();
$conn->close();
?>
