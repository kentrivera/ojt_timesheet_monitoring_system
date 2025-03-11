<?php
include '../dbcon.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_year = $_POST['school_year'];

    if (!empty($school_year)) {
        $stmt = $conn->prepare("INSERT INTO school_years (school_year) VALUES (?)");
        $stmt->bind_param("s", $school_year);
        if ($stmt->execute()) {
            echo "School Year added successfully.";
        } else {
            echo "Error adding school year.";
        }
        $stmt->close();
    } else {
        echo "School Year cannot be empty.";
    }
}
?>
