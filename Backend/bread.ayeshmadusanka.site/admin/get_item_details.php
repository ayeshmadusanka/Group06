<?php
require("auth.php");
checkLoggedIn($db);

if (isset($_GET['ids'])) {
    $ids = $_GET['ids']; // Comma-separated list of item IDs
    $idsArray = explode(',', $ids);
    // Sanitize: convert to integers
    $idsArray = array_map('intval', $idsArray);
    $idsList = implode(',', $idsArray);

    $sql = "SELECT item_id, name, image_path FROM items WHERE item_id IN ($idsList)";
    $result = mysqli_query($db, $sql);

    $items = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode($items);
} else {
    header('Content-Type: application/json');
    echo json_encode([]);
}
?>
