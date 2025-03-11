<?php
// Start session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page if not logged in
    header("Location: ../login.php");
    exit();
}
// Include the database connection file
include '../dbcon.php';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>View Coordinators</title>
    <style>
        .main-layout {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }

        .content-wrapper {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        .side-nav {
            width: 16rem;
        }

        .main-content {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            padding-top: 6%;
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="main-layout">
        <?php include 'nav/top-nav.php'; ?>
        <div class="content-wrapper">
            <div class="text-white bg-gray-800 side-nav">
                <?php include 'nav/side-nav.php'; ?>
            </div>

        </div>
    </div>
</body>

</html>