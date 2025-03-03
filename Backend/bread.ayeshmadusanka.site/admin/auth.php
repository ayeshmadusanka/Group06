<?php
require_once '../connection/db.php';
error_reporting(0);
session_start();

// Function to check if the user is logged in
function checkLoggedIn($db) {
    // Check if 'bread_admin' is not set in the session or if the user doesn't exist, redirect to index
    if (!isset($_SESSION['bread_admin']) || !fetchBreadAdminUsername($db, $_SESSION['bread_admin'])) {
        header("Location: index.php");
        exit();
    }
}

// Function to fetch bread_admin_username from the database
function fetchBreadAdminUsername($db, $bread_admin_id) {
    // Use a prepared statement to prevent SQL injection
    $stmt = $db->prepare("SELECT bread_admin_username FROM bread_admin WHERE bread_admin_id = ?");
    $stmt->bind_param("i", $bread_admin_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if the query returned any rows
    if ($result->num_rows > 0) {
        // Fetch the bread_admin_username from the result
        $row = $result->fetch_assoc();
        return $row['bread_admin_username'];
    }
    return false;
}

// Call the function to check if the user is logged in
checkLoggedIn($db);

// Optionally fetch the admin name to use later in the script
$breadAdminName = fetchBreadAdminUsername($db, $_SESSION['bread_admin']);
?>
