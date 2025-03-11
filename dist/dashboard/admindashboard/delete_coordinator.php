<?php
// Include the database connection file
include '../dbcon.php';

// Check if 'id' is set in the URL and is a valid number
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];

    // Prepare and execute the DELETE statement
    $stmt = $conn->prepare("DELETE FROM coordinators WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Redirect back to the main page with a success message
        header("Location: view_coordinators.php?message=Coordinator+deleted+successfully");
    } else {
        // Redirect back with an error message if the deletion failed
        header("Location: view_coordinators.php?message=Failed+to+delete+coordinator");
    }

    // Close the statement and connection
    $stmt->close();
} else {
    // Redirect if ID is invalid
    header("Location: view_coordinators.php?message=Invalid+coordinator+ID");
}

$conn->close();
?>
