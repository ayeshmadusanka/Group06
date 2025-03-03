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

    // Validate inputs
    if (empty($phone_number)) {
        echo json_encode(["success" => false, "message" => "Phone number is required."]);
        exit;
    }

    // Check if the phone number exists in the database and is active
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

    // Generate and store OTP
    $otp_code = rand(100000, 999999);
    $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    $stmt = $db->prepare("INSERT INTO otp (user_id, otp_code, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $otp_code, $expires_at);
    $stmt->execute();
    $stmt->close();

    // Send OTP via SMS
    sendSMS($phone_number, "Your OTP is $otp_code. It expires in 5 minutes. - Bread and Butter Hospitality Group");

    echo json_encode([
        "success" => true,
        "message" => "OTP sent to your phone. It expires in 5 minutes.",
        "user_id" => $user_id  // Include user_id in the response
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
}
?>
