<?php
require '../dbcon.php';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['id'], $data['name'])) {
    $departmentId = $data['id'];
    $departmentName = $data['name'];

    $query = "UPDATE department SET department_name = ? WHERE department_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $departmentName, $departmentId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update department.']);
    }
}
