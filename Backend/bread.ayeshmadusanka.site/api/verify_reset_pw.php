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
    $otp_code = $input['otp_code'] ?? null;
    $new_password = $input['new_password'] ?? null;

    // Validate inputs
    if (empty($phone_number) || empty($otp_code) || empty($new_password)) {
        echo json_encode(["success" => false, "message" => "All fields are required."]);
        exit;
    }

    // Check if the phone number exists and is active
    $stmt = $db->prepare("SELECT user_id, is_active FROM users WHERE phone_number = ?");
    $stmt->bind_param("s", $phone_number);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Phone number not registered."]);
        exit;
    }

    // Bind the result to variables
    $stmt->bind_result($user_id, $is_active);
    $stmt->fetch();
    $stmt->close();

    // Check if the user is active
    if ($is_active == 0) {
        echo json_encode(["success" => false, "message" => "Your account is not active."]);
        exit;
    }

    // Verify OTP
    $stmt = $db->prepare("SELECT otp_code, expires_at FROM otp WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "OTP not found."]);
        exit;
    }

    // Bind OTP result to variables
    $stmt->bind_result($stored_otp_code, $expires_at);
    $stmt->fetch();
    $stmt->close();

    // Check if OTP is valid (not expired)
    if ($otp_code !== $stored_otp_code) {
        echo json_encode(["success" => false, "message" => "Invalid OTP."]);
        exit;
    }

    $current_time = new DateTime();
    $expiration_time = new DateTime($expires_at);
    if ($current_time > $expiration_time) {
        echo json_encode(["success" => false, "message" => "OTP has expired."]);
        exit;
    }

    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

    // Update the password in the database
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $stmt->bind_param("si", $hashed_password, $user_id);
    if ($stmt->execute()) {
        // Optionally, delete OTP after successful reset
        $stmt = $db->prepare("DELETE FROM otp WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        echo json_encode([
            "success" => true,
            "message" => "Password reset successfully. You can now log in with your new password."
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update password."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
}
?>
