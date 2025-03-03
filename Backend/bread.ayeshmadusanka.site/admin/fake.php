<?php
// user_rec_generate_orders.php

// Include your auth file which initializes $db as a MySQLi connection.
require 'auth.php';

// Fetch items price from the items table and store in an array keyed by item id.
$items = [];
$result = $db->query("SELECT item_id, price FROM items");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $items[$row['item_id']] = $row['price'];
    }
} else {
    die("Error fetching items: " . $db->error);
}

// Set up the date range: from August 1, 2024 to March 2, 2025 (inclusive)
$startDate = new DateTime('2024-08-01');
$endDate   = new DateTime('2025-03-02');
$endDate->modify('+1 day');  // include the final day

// Global counter for cycling through item IDs 1 to 10
$itemCounter = 0;

// Prepare list of existing user IDs (assumed to be 1 to 21)
$userIds = range(1, 21);

// Prepare SQL statements for orders and order items.
$orderSql = "INSERT INTO orders (user_id, order_date, update_date, status, total_price)
             VALUES (?, ?, ?, ?, ?)";
$orderStmt = $db->prepare($orderSql);
if (!$orderStmt) {
    die("Order Statement Preparation Failed: " . $db->error);
}

$itemSql = "INSERT INTO order_items (order_id, item_id, quantity, price)
            VALUES (?, ?, ?, ?)";
$itemStmt = $db->prepare($itemSql);
if (!$itemStmt) {
    die("Order Item Statement Preparation Failed: " . $db->error);
}

// Loop through each day in the date range.
$currentDate = clone $startDate;
while ($currentDate < $endDate) {
    // PHP's format('w'): 0 (Sunday) to 6 (Saturday)
    // Adjust to MySQL's DAYOFWEEK where Sunday=1, Monday=2, ..., Saturday=7
    $phpDayOfWeek = (int)$currentDate->format('w');
    $mysqlDay = $phpDayOfWeek + 1;
    
    // For Monday to Thursday: base order count is randomly chosen between 5 and 8.
    $baseOrders = rand(5, 8);
    
    // Adjust based on day of week:
    // MySQL: Sunday=1, Monday=2, Tuesday=3, Wednesday=4, Thursday=5, Friday=6, Saturday=7
    if ($mysqlDay == 6) {         // Friday
        $ordersToday = $baseOrders + 5;
    } elseif ($mysqlDay == 7) {     // Saturday
        $ordersToday = $baseOrders + 10;
    } elseif ($mysqlDay == 1) {     // Sunday
        $ordersToday = $baseOrders + 15;
    } else {
        $ordersToday = $baseOrders;
    }
    
    // Loop to insert orders for the day.
    for ($i = 0; $i < $ordersToday; $i++) {
        // Select a random user ID from 1 to 21.
        $userId = $userIds[array_rand($userIds)];
        
        // Generate a random time for the order on the current day.
        $orderTime = sprintf('%02d:%02d:%02d', rand(0, 23), rand(0, 59), rand(0, 59));
        $orderDatetime = $currentDate->format('Y-m-d') . ' ' . $orderTime;
        
        $status = 'Pending';
        // Calculate a dummy total price; adjust as needed.
        $totalPrice = round(10 + (mt_rand() / mt_getrandmax()) * 20, 2);
        
        // Bind parameters and execute the order insert statement.
        $orderStmt->bind_param("isssd", $userId, $orderDatetime, $orderDatetime, $status, $totalPrice);
        if (!$orderStmt->execute()) {
            echo "Order Insert Error: " . $orderStmt->error . "\n";
            continue;
        }
        $orderId = $db->insert_id;
        
        // For each order, insert between 1 and 3 order items.
        $numItems = rand(1, 3);
        for ($j = 0; $j < $numItems; $j++) {
            // Use item IDs sequentially in a round-robin fashion (1 to 10).
            $itemId = ($itemCounter % 10) + 1;
            $itemCounter++;
            
            $quantity = rand(1, 3);
            // Use the price from the items table.
            // If an item doesn't exist in the fetched list, you may handle it as needed.
            $price = isset($items[$itemId]) ? $items[$itemId] : 0;
            
            // Bind parameters and execute the order item insert statement.
            $itemStmt->bind_param("iiid", $orderId, $itemId, $quantity, $price);
            if (!$itemStmt->execute()) {
                echo "Order Item Insert Error: " . $itemStmt->error . "\n";
            }
        }
    }
    
    // Move to the next day.
    $currentDate->modify('+1 day');
}

echo "Fake orders generated successfully.\n";

// Close prepared statements.
$orderStmt->close();
$itemStmt->close();

// Optionally close the database connection.
$db->close();
?>
