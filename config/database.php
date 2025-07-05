<?php
// config/database.php

define('DB_HOST', 'localhost'); // Your database host (usually 'localhost')
define('DB_USER', 'root');     // Your MySQL username (default for XAMPP/Wamp/MAMP is 'root')
define('DB_PASS', '');         // Your MySQL password (default for XAMPP/Wamp/MAMP is empty)
define('DB_NAME', 'doctor_portfolio'); // The database name we created

// Establish database connection using MySQLi (Recommended over older mysql_ functions)
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 for proper emoji and special character handling
$conn->set_charset("utf8mb4");

// You can optionally add a function for preparing statements to avoid repetition
function prepare_stmt($sql) {
    global $conn;
    return $conn->prepare($sql);
}

// For demonstration, we'll keep it simple and use $conn directly.
// In a larger application, you might use PDO for more flexibility.
?>