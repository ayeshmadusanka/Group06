<?php

// Main connection file for both admin & front end
$host = 'localhost'; // Host
$dbname = 'bread'; // Database name
$username = 'breaduser'; // Username
$password = 'l7ix8xOqF1xqv5ufM9se'; // Password

// Create a new mysqli instance
$db = new mysqli($host, $username, $password, $dbname);

// Check for connection errors
if ($db->connect_error) {
    // Redirect to error page
    header("Location: connection/error.php");
    exit(); // Stop further execution
}

// Function to prepare SQL statements with error handling
function prepare_stmt($sql) {
    global $db;
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        // Print the error message from the database
        echo "Error preparing statement: " . $db->error;
        exit(); // Stop further execution
    }
    return $stmt;
}

?>
