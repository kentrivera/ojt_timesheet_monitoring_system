<?php
// Database connection parameters
$servername = "localhost";   // Replace with your server name
$username = "root"; // Replace with your database username
$password = ""; // Replace with your database password
$database = "ojttimesheetmonitoringdb"; // Replace with your database name

// Create a new MySQLi connection
$conn = new mysqli($servername, $username, $password, $database);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: Set the character set to UTF-8
$conn->set_charset("utf8");

?>
