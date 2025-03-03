<?php
require_once '../connection/db.php';
require_once '../connection/sms.php';

// Set response headers for JSON
header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate JSON input
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $input) {
    $phone_number = $input['phone_number'] ?? null;
    $password = $input['password'] ?? null;

    // Validate inputs
    if (empty($phone_number) || empty($password)) {
        echo json_encode(["success" => false, "message" => "Phone number and password are required."]);
        exit;
    }

    // Check if the phone number exists in the database
    $stmt = $db->prepare("SELECT user_id, username, password, is_active FROM users WHERE phone_number = ?");
    $stmt->bind_param("s", $phone_number);
    $stmt->execute();
    $stmt->store_result();
    
    // If no user found with the given phone number
    if ($stmt->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "User not found."]);
        exit;
    }

    // Fetch the user details
    $stmt->bind_result($user_id, $username, $hashed_password, $is_active);
    $stmt->fetch();
    $stmt->close();

    // Check if the user is active
    if ($is_active == 0) {
        echo json_encode(["success" => false, "message" => "Your account is not activated yet. Please verify your phone number."]);
        exit;
    }

    // Verify the password
    if (!password_verify($password, $hashed_password)) {
        echo json_encode(["success" => false, "message" => "Invalid password."]);
        exit;
    }

    // Login successful, send user details in the response
    echo json_encode([
        "success" => true,
        "message" => "Login successful.",
        "user_id" => $user_id,
        "username" => $username // Include username in the response
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
}
?>
