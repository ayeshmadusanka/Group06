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

    // Validate inputs
    if (empty($user_id)) {
        echo json_encode(["success" => false, "message" => "User ID is required."]);
        exit;
    }

    // Check if the user exists
    $stmt = $db->prepare("SELECT phone_number, is_active FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "User not found."]);
        exit;
    }

    // Fetch user details
    $stmt->bind_result($phone_number, $is_active);
    $stmt->fetch();
    $stmt->close();

    // Check if user is active
    if ($is_active == 0) {
        echo json_encode(["success" => false, "message" => "User is not active. OTP cannot be resent."]);
        exit;
    }

    // Check if OTP exists for the user and has not expired
    $stmt = $db->prepare("SELECT otp_code, expires_at FROM otp WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "No OTP found for this user."]);
        exit;
    }

    // Fetch OTP details
    $stmt->bind_result($stored_otp, $expires_at);
    $stmt->fetch();
    $stmt->close();

    // Check if the previous OTP has expired
    if (strtotime($expires_at) < time()) {
        // If expired, generate a new OTP
        $otp_code = rand(100000, 999999);
        $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        // Update the OTP in the database
        $stmt = $db->prepare("UPDATE otp SET otp_code = ?, expires_at = ? WHERE user_id = ?");
        $stmt->bind_param("ssi", $otp_code, $expires_at, $user_id);
        $stmt->execute();
        $stmt->close();
    } else {
        // If OTP is still valid, don't generate a new one
        $otp_code = $stored_otp;
        $expires_at = $expires_at;
    }

    // Send OTP via SMS with Bread and Butter Hospitality Group message
    sendSMS($phone_number, "Your OTP is $otp_code. It expires in 5 minutes. - Bread and Butter Hospitality Group");

    echo json_encode([
        "success" => true,
        "message" => "OTP resent successfully! A new OTP has been sent to your phone.",
        "otp_code" => $otp_code
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
}
?>
