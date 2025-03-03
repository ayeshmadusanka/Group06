<?php
require_once '../connection/db.php';

// Set response headers for JSON
header('Content-Type: application/json');

// Validate the request method
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get item_type filter from the request if provided
    $itemType = isset($_GET['item_type']) ? trim($_GET['item_type']) : '';

    // Base query
    $query = "SELECT item_id, name, description, price, item_type, cuisine_type, beverage_type, 
                     image_path, is_active 
              FROM items";

    // Apply filter if item_type is provided
    if (!empty($itemType)) {
        $query .= " WHERE item_type = ?";
    }

    // Prepare the statement
    if ($stmt = $db->prepare($query)) {
        // Bind parameters if filtering by item_type
        if (!empty($itemType)) {
            $stmt->bind_param("s", $itemType);
        }

        // Execute query
        $stmt->execute();
        $result = $stmt->get_result();

        // Fetch items
        $items = [];
        while ($row = $result->fetch_assoc()) {
            // Remove '..' prefix from image_path if present
            if (strpos($row['image_path'], '..') === 0) {
                $row['image_path'] = substr($row['image_path'], 2);
            }
            $items[] = $row;
        }

        // Send response
        echo json_encode(["success" => true, "items" => $items]);

        // Close statement
        $stmt->close();
    } else {
        // Handle query preparation failure
        echo json_encode(["success" => false, "message" => "Database query failed."]);
    }
} else {
    // Handle invalid request method
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>
