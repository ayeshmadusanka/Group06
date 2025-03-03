<?php
require_once '../connection/db.php';
require_once '../connection/sms.php';

// Set response headers for JSON
header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate JSON input
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $input) {
    $username = $input['username'] ?? null;
    $password = $input['password'] ?? null;
    $phone_number = $input['phone_number'] ?? null;

    // Validate inputs
    if (empty($username) || empty($password) || empty($phone_number)) {
        echo json_encode(["success" => false, "message" => "All fields are required."]);
        exit;
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Check if the username already exists
    $stmt = $db->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "Username already exists."]);
        exit;
    }
    $stmt->close();

    // Check if the phone number already exists
    $stmt = $db->prepare("SELECT user_id FROM users WHERE phone_number = ?");
    $stmt->bind_param("s", $phone_number);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "Phone number already exists."]);
        exit;
    }
    $stmt->close();

    // Insert the user into the database, with is_active set to 0 (inactive)
    $stmt = $db->prepare("INSERT INTO users (username, password, phone_number, is_active) VALUES (?, ?, ?, 0)");
    $stmt->bind_param("sss", $username, $hashed_password, $phone_number);
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        $stmt->close();

        // Generate and store OTP
        $otp_code = rand(100000, 999999);
        $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        $stmt = $db->prepare("INSERT INTO otp (user_id, otp_code, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $otp_code, $expires_at);
        $stmt->execute();
        $stmt->close();

        // Send OTP via SMS with Bread and Butter Hospitality Group message
        sendSMS($phone_number, "Your OTP is $otp_code. It expires in 5 minutes. - Bread and Butter Hospitality Group");

        echo json_encode([
            "success" => true,
            "message" => "User registered successfully! OTP sent to your phone.",
            "user_id" => $user_id
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to register user."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
}
?>