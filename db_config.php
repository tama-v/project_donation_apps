<?php
// --- db_config.php ---

// Database connection variables
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Default user for XAMPP
define('DB_PASS', '');     // Default password for XAMPP is empty
define('DB_NAME', 'donation_apps_db'); // The database name you created

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    // If connection fails, stop the script and show the error
    die("Connection failed: " . $conn->connect_error);
}

// Set the character set to utf8mb4 for full Unicode support
$conn->set_charset("utf8mb4");

// Set header to return JSON content type
header("Content-Type: application/json");
?>