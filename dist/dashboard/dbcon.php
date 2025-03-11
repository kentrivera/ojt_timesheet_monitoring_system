<?php
// Database connection parameters
$servername = "143.198.204.167/phpmyadmin";   // Replace with your server name
$username = "admin"; // Replace with your database username
$password = "5616e568a366a29e03222368cb0860a86b5bb928cf4a7624"; // Replace with your database password
$database = "ojttimesheetmonitoringdb2"; // Replace with your database name

// Create a new MySQLi connection
$conn = new mysqli($servername, $username, $password, $database);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: Set the character set to UTF-8
$conn->set_charset("utf8");

?>
