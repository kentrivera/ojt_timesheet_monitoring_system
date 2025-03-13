<?php
// Database connection parameters
$servername = "ojttimesheetdb-do-user-19797851-0.j.db.ondigitalocean.com";   // Replace with your server name
$username = "doadmin"; // Replace with your database username
$password = "AVNS_gzkXBSCUDhPADjjZegh"; // Replace with your database password
$database = "defaultdb"; // Replace with your database name

// Create a new MySQLi connection
$conn = new mysqli($servername, $username, $password, $database);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: Set the character set to UTF-8
$conn->set_charset("utf8");

?>
