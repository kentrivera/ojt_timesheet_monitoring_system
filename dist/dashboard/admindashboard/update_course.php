<?php
// Include the database connection
include '../dbcon.php'; // Adjust the path as needed

// Get the POST data
$course_id = $_POST['course_id'];
$course_name = $_POST['course_name'];
$department_id = $_POST['department_id'];
$course_description = isset($_POST['course_description']) ? $_POST['course_description'] : null;

// Check if the required fields are provided
if (empty($course_name) || empty($department_id)) {
    echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
    exit;
}

// Update the course
$sql = "UPDATE courses SET course_name = ?, department_id = ?, course_description = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssi", $course_name, $department_id, $course_description, $course_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Course updated successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update course.']);
}

$stmt->close();
$conn->close();
?>
