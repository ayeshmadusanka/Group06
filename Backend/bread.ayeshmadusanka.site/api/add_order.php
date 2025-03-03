<?php
require_once '../connection/db.php';

// Enable error logging without specifying a log file path
ini_set('log_errors', 1);
ini_set('display_errors', 0); // Do not display errors on the page
error_reporting(E_ALL);

// Set response headers for JSON
header('Content-Type: application/json');

try {
    // Validate the request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method.");
    }

    // Decode the incoming JSON request body
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate required data
    if (!isset($data['user_id'], $data['order_date'], $data['order_items']) || !is_array($data['order_items'])) {
        throw new Exception("Required data (user_id, order_date, order_items) is missing or invalid.");
    }

    $user_id = $data['user_id'];
    $order_date = $data['order_date'];
    $order_items = $data['order_items'];
    $total_price = 0;

    // Enable MySQLi error reporting
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Start a transaction
    $db->begin_transaction();

    // Calculate total price
    foreach ($order_items as $item) {
        if (!isset($item['item_id'], $item['quantity'], $item['price'])) {
            throw new Exception("Invalid order item data.");
        }
        $total_price += $item['quantity'] * $item['price'];
    }

    // Insert order into orders table
    $query = "INSERT INTO orders (user_id, order_date, update_date, status, total_price) VALUES (?, ?, NOW(), 'Confirmed', ?)";
    $stmt = $db->prepare($query);
    $stmt->bind_param('isd', $user_id, $order_date, $total_price);
    $stmt->execute();
    $order_id = $stmt->insert_id;

    // Insert order items into order_items table
    $query = "INSERT INTO order_items (order_id, item_id, quantity, price) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($query);

    foreach ($order_items as $item) {
        $stmt->bind_param('iiid', $order_id, $item['item_id'], $item['quantity'], $item['price']);
        $stmt->execute();
    }

    // Commit transaction
    $db->commit();

    // Success response
    echo json_encode(["success" => true, "order_id" => $order_id, "message" => "Order successfully added."]);
} catch (Exception $e) {
    // Rollback transaction on error
    if ($db->errno) {
        $db->rollback();
    }

    // Log the error
    error_log("Order Error: " . $e->getMessage());

    // Error response
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>