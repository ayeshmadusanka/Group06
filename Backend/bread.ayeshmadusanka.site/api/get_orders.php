<?php
require_once '../connection/db.php';

// Enable error logging without displaying errors on the page.
ini_set('log_errors', 1);
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Set response headers for JSON.
header('Content-Type: application/json');

try {
    // Allow only GET requests.
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception("Invalid request method. Only GET allowed.");
    }

    // Endpoint to get all orders for a specific user.
    if (isset($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);

        // Prepare a statement to select orders for the user, including total quantity.
        // Orders are sorted so that the newest orders come first.
        $query = "SELECT o.order_id, o.user_id, o.order_date, o.update_date, o.status, o.total_price,
                         COALESCE(SUM(oi.quantity), 0) AS total_quantity
                  FROM orders o
                  LEFT JOIN order_items oi ON o.order_id = oi.order_id
                  WHERE o.user_id = ?
                  GROUP BY o.order_id
                  ORDER BY o.order_date DESC";
                  
        $stmt = $db->prepare($query);
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $db->error);
        }
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }

        echo json_encode(["success" => true, "orders" => $orders]);
        exit;
    }

    // Endpoint to get order details for a given order id.
    if (isset($_GET['order_id'])) {
        $order_id = intval($_GET['order_id']);

        // Query the orders table for the given order id.
        $query = "SELECT order_id, user_id, order_date, update_date, status, total_price FROM orders WHERE order_id = ?";
        $stmt = $db->prepare($query);
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $db->error);
        }
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $orderResult = $stmt->get_result();
        if ($orderResult->num_rows === 0) {
            throw new Exception("Order not found.");
        }
        $order = $orderResult->fetch_assoc();

        // Query to get order items with item names.
        $query_items = "SELECT oi.order_item_id, oi.order_id, oi.item_id, i.name AS item_name, oi.quantity, oi.price 
                          FROM order_items oi
                          JOIN items i ON oi.item_id = i.item_id
                          WHERE oi.order_id = ?";
        $stmt_items = $db->prepare($query_items);
        if (!$stmt_items) {
            throw new Exception("Failed to prepare statement: " . $db->error);
        }
        $stmt_items->bind_param("i", $order_id);
        $stmt_items->execute();
        $itemsResult = $stmt_items->get_result();

        $order_items = [];
        while ($row = $itemsResult->fetch_assoc()) {
            $order_items[] = $row;
        }

        // Get total quantity from order_items where order_id matches.
        $query_total_quantity = "SELECT COALESCE(SUM(quantity), 0) AS total_quantity FROM order_items WHERE order_id = ?";
        $stmt_total_quantity = $db->prepare($query_total_quantity);
        if (!$stmt_total_quantity) {
            throw new Exception("Failed to prepare statement: " . $db->error);
        }
        $stmt_total_quantity->bind_param("i", $order_id);
        $stmt_total_quantity->execute();
        $totalQuantityResult = $stmt_total_quantity->get_result();
        $total_quantity = $totalQuantityResult->fetch_assoc()['total_quantity'] ?? 0;

        echo json_encode([
            "success" => true,
            "order" => $order,
            "order_items" => $order_items,
            "total_quantity" => (int)$total_quantity
        ]);
        exit;
    }

    // If neither user_id nor order_id parameter is provided.
    throw new Exception("Required parameter not provided. Provide user_id or order_id.");
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
