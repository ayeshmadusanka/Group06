<?php
require_once '../connection/db.php';
require_once '../connection/sms.php';

// Set response headers for JSON
header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate JSON input
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $input) {
    $user_id = $input['user_id'] ?? null;
    $otp_code = $input['otp_code'] ?? null;

    // Validate inputs
    if (empty($user_id) || empty($otp_code)) {
        echo json_encode(["success" => false, "message" => "User ID and OTP code are required."]);
        exit;
    }

    // Check if the OTP exists for the given user and has not expired
    $stmt = $db->prepare("SELECT otp_code, expires_at FROM otp WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "OTP not found."]);
        exit;
    }

    // Fetch OTP details
    $stmt->bind_result($stored_otp, $expires_at);
    $stmt->fetch();
    $stmt->close();

    // Check if OTP has expired
    if (strtotime($expires_at) < time()) {
        echo json_encode(["success" => false, "message" => "OTP has expired."]);
        exit;
    }

    // Check if the provided OTP matches the stored OTP
    if ($otp_code !== $stored_otp) {
        echo json_encode(["success" => false, "message" => "Invalid OTP code."]);
        exit;
    }

    // OTP is valid, activate the user by setting is_active to 1
    $stmt = $db->prepare("UPDATE users SET is_active = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        // Fetch the user's phone number
        $stmt = $db->prepare("SELECT phone_number FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($phone_number);
        $stmt->fetch();
        $stmt->close();

        // Send registration confirmation SMS
        $message = "You are registered at Bread and Butter Hospitality Group.";
        sendSMS($phone_number, $message);

        echo json_encode([
            "success" => true,
            "message" => "User activated successfully. A confirmation message has been sent to your phone."
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to activate user."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
}
?>
