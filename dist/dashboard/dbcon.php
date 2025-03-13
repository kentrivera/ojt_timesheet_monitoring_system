<?php
$host = "ojttimesheetdb-do-user-19797851-0.j.db.ondigitalocean.com";
$port = 25060;
$username = "doadmin";
$password = "AVNS_gzkXBSCUDhPADjjZegh";  // Make sure to keep this safe and hidden in production
$database = "defaultdb";

// Create connection
$conn = new mysqli($host, $username, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected successfully!";
?>
