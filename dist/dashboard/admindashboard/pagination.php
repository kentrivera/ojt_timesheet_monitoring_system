<?php
include '../dbcon.php';

// Define how many results you want per page
$limit = 3;

// Get the current page number from the URL, if not present set default to 1
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Fetch the coordinators with limit and offset
$sql = "SELECT id, name, email, image, department, phone_number FROM coordinators LIMIT $start, $limit";
$result = $conn->query($sql);

// Fetch the total number of coordinators
$totalResults = $conn->query("SELECT COUNT(id) AS total FROM coordinators")->fetch_assoc()['total'];
$totalPages = ceil($totalResults / $limit);
?>
