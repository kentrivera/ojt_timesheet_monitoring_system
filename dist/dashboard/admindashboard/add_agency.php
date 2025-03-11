<?php
include '../dbcon.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $agency_name = $_POST['agency_name'];

    if (!empty($agency_name)) {
        $stmt = $conn->prepare("INSERT INTO agencies (agency_name) VALUES (?)");
        $stmt->bind_param("s", $agency_name);
        if ($stmt->execute()) {
            echo "Agency added successfully.";
        } else {
            echo "Error adding agency.";
        }
        $stmt->close();
    } else {
        echo "Agency name cannot be empty.";
    }
}
?>
