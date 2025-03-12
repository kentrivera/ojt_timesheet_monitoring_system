<?php
// Database credentials
$host = 'ojttimesheetdb-do-user-19797851-0.j.db.ondigitalocean.com';
$username = 'doadmin';
$password = 'AVNS_gzkXBSCUDhPADjjZegh';
$database = 'defaultdb';
$port = 25060;

// SSL configuration
$ssl_cert = '/path/to/ssl/certificate.pem'; // Path to your SSL certificate file
$ssl_key = '/path/to/ssl/private-key.pem';  // Path to your SSL private key file
$ssl_ca = '/path/to/ssl/ca-certificate.pem'; // Path to your CA certificate file

// Create a MySQLi connection with SSL
$mysqli = new mysqli($host, $username, $password, $database, $port);

// Check for connection errors
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Set SSL options
if (!$mysqli->ssl_set($ssl_key, $ssl_cert, $ssl_ca, NULL, NULL)) {
    die("SSL configuration failed: " . $mysqli->error);
}

// Verify SSL connection
if (!$mysqli->real_connect($host, $username, $password, $database, $port, NULL, MYSQLI_CLIENT_SSL)) {
    die("Failed to establish a secure connection: " . $mysqli->error);
}

echo "Connected successfully to the database!";

// Perform database operations here...

// Close the connection
$mysqli->close();
?>