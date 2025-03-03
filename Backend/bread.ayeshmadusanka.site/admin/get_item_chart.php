<?php 
require_once 'auth.php';
session_start();

// Get the item ID from the AJAX request
$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;
if ($item_id <= 0) {
    echo json_encode(['labels' => [], 'counts' => []]);
    exit;
}

// Query to get daily order counts for the specific item by joining orders and order_items tables.
// Instead of COUNT(*) we sum the quantity to reflect the total number of that item sold.
$query = "SELECT DATE(o.order_date) AS order_day, SUM(oi.quantity) AS order_count 
          FROM orders o 
          JOIN order_items oi ON o.order_id = oi.order_id 
          WHERE oi.item_id = $item_id 
          GROUP BY DATE(o.order_date) 
          ORDER BY order_day";
$result = $db->query($query);

$dates = [];
$counts = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $dates[] = $row['order_day'];
        $counts[] = $row['order_count'];
    }
    $result->free();
}

echo json_encode(['labels' => $dates, 'counts' => $counts]);
?>
