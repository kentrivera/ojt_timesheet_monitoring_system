<?php
// Assuming you have a function to handle database connection
require '../dbcon.php';

// Check if the form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Get the form data
    $courseName = $_POST['course_name'];
    $departmentId = $_POST['department_id'];
    $courseDescription = isset($_POST['course_description']) ? $_POST['course_description'] : '';

    // Check if course_id is set to determine whether we are adding or updating
    if (isset($_POST['course_id']) && !empty($_POST['course_id'])) {
        // Update course
        $courseId = $_POST['course_id'];
        
        // Prepare SQL query to update the course
        $sql = "UPDATE courses SET course_name = ?, department_id = ?, course_description = ? WHERE course_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $courseName, $departmentId, $courseDescription, $courseId);
        
        // Execute the query and check for success
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Course updated successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update course.']);
        }
    } else {
        // Add new course
        $sql = "INSERT INTO courses (course_name, department_id, course_description) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $courseName, $departmentId, $courseDescription);
        
        // Execute the query and check for success
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Course added successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add course.']);
        }
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();
}
?>
