<?php
// Include the database connection file
include '../dbcon.php';

// Initialize response
$response = ['success' => false, 'courses' => []];

// Check if department_id parameter exists
if (isset($_GET['department_id']) && !empty($_GET['department_id'])) {
    $department_id = $conn->real_escape_string($_GET['department_id']);
    
    // Query to get courses by department
    $query = "SELECT id, course_name FROM courses WHERE department_id = '$department_id'";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $courses = [];
        while ($course = $result->fetch_assoc()) {
            $courses[] = [
                'id' => $course['id'],
                'course_name' => $course['course_name']
            ];
        }
        
        $response['success'] = true;
        $response['courses'] = $courses;
    } else {
        $response['message'] = 'No courses found for this department.';
    }
} else {
    $response['message'] = 'Department ID is required.';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit();
?>