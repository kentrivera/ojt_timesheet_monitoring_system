<?php
include '../dbcon.php';

$courses = $conn->query("SELECT course_name FROM courses");

$response = [];
while ($course = $courses->fetch_assoc()) {
    $response[] = $course['course_name'];
}

echo json_encode($response);
?>
