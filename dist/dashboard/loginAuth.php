<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'dbcon.php'; // Ensure the connection to the database

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capture and sanitize input
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);

    // Validate input
    if (empty($username) || empty($password) || empty($role)) {
        header("Location: login.php?error=missing_fields");
        exit();
    }

    // Sanitize username to prevent SQL injection
    $username = $conn->real_escape_string($username);

    // Define role-specific settings
    $table = '';
    $redirect_url = '';
    $id_column = '';
    $username_column = '';
    $password_column = 'password'; // Default password column name

    switch (strtolower($role)) {
        case 'admin':
            $table = 'users';
            $redirect_url = 'admindashboard/index.php';
            $id_column = 'user_id';
            $username_column = 'email'; // Admins log in using email
            break;
        case 'coordinator':
            $table = 'coordinators';
            $redirect_url = 'coordinators/index.php';
            $id_column = 'id';
            $username_column = 'email'; // Coordinators log in using email
            break;
        case 'trainee':
            $table = 'trainees';
            $redirect_url = 'traineedashboard/index.php';
            $id_column = 'id';
            $username_column = 'student_id'; // Trainees log in using student ID
            break;
        default:
            header("Location: login.php?error=invalid_role");
            exit();
    }

    // Build query to fetch user data based on the username
    $query = "SELECT * FROM $table WHERE $username_column = ?";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        // Handle preparation errors
        header("Location: login.php?error=server_error");
        exit();
    }

    // Bind the username parameter to the query and execute
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Verify the password
        if (password_verify($password, $row[$password_column])) {
            // Set session variables
            $_SESSION['id'] = $row[$id_column]; // Store the unique user ID
            $_SESSION['role'] = ucfirst($role); // Store role (e.g., Admin, Trainee)
            $_SESSION['user_id'] = $row[$id_column]; // Store user ID
            $_SESSION['department_id'] = $row['department_id'] ?? null; // Optional for coordinators

            // Redirect to the appropriate dashboard based on the role
            header("Location: $redirect_url");
            exit();
        } else {
            // Invalid password
            header("Location: login.php?error=invalid_password");
            exit();
        }
    } else {
        // User not found in the database
        header("Location: login.php?error=user_not_found");
        exit();
    }
} else {
    // Invalid request method, should only be POST
    header("Location: login.php?error=invalid_request");
    exit();
}
