<?php
// Include the database connection file
include '../dbcon.php';

// Enable error reporting for debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Check if trainee ID is set in the query string
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $trainee_id = (int)$_GET['id'];

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Delete related data from studentloggeddata to maintain referential integrity
        $stmt = $conn->prepare("DELETE FROM studentloggeddata WHERE trainee_id = ?");
        $stmt->bind_param("i", $trainee_id);
        $stmt->execute();
        $stmt->close();

        // Delete the trainee from the trainees table
        $stmt = $conn->prepare("DELETE FROM trainees WHERE id = ?");
        $stmt->bind_param("i", $trainee_id);
        $stmt->execute();
        $stmt->close();

        // Commit the transaction
        $conn->commit();

        // Store a session message to show the alert on the redirected page
        session_start();
        $_SESSION['alert'] = [
            'type' => 'success',
            'title' => 'Deleted!',
            'message' => 'Trainee has been deleted successfully.'
        ];

        // Redirect to the trainee list page
        header("Location: traineelist.php");
        exit();
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();

        session_start();
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Error',
            'message' => 'Failed to delete trainee.'
        ];

        header("Location: traineelist.php");
        exit();
    }
} else {
    session_start();
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'Invalid Request',
        'message' => 'Trainee ID is missing or invalid.'
    ];

    header("Location: traineelist.php");
    exit();
}
