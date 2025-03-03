<?php
require("auth.php");
checkLoggedIn($db);

if (isset($_GET['id']) && isset($_GET['status'])) {
    $item_id = intval($_GET['id']);
    $current_status = intval($_GET['status']);

    // Toggle status
    $new_status = ($current_status == 1) ? 0 : 1;

    // Update the item status in the database
    $sql = "UPDATE items SET is_active = '$new_status' WHERE item_id = '$item_id'";
    $query = mysqli_query($db, $sql);

    if ($query) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
}

mysqli_close($db);
?>
