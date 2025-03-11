<?php
session_start();
include '../dbcon.php'; // Ensure correct database connection

// Check if 'id' parameter exists and is numeric
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $trainee_id = $_GET['id']; // Use the same trainee_id as in the timesheet query

    // Fetch the trainee's details using the provided ID
    $query = "
        SELECT 
            trainees.name, 
            trainees.email, 
            trainees.image, 
            courses.course_name, 
            agencies.agency_name, 
            school_years.school_year 
        FROM 
            trainees
        LEFT JOIN courses ON trainees.course_id = courses.id
        LEFT JOIN agencies ON trainees.agency_id = agencies.id
        LEFT JOIN school_years ON trainees.school_year_id = school_years.id
        WHERE trainees.id = ?
    ";

    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param("i", $trainee_id);
        $stmt->execute();
        $stmt->bind_result($traineeName, $email, $image, $course, $agency, $schoolYear);
        $stmt->fetch();
        $stmt->close();

        // Return JSON response with all trainee details
        echo json_encode([
            "name" => $traineeName ?? "Unknown Trainee",
            "email" => $email ?? "N/A",
            "image" => $image ?? "default.png",
            "course" => $course ?? "N/A",
            "agency" => $agency ?? "N/A",
            "school_year" => $schoolYear ?? "N/A"
        ]);
    } else {
        echo json_encode(["error" => "Database Error"]);
    }
} else {
    // Redirect if no valid 'id' is provided, consistent with the timesheet logic
    header("Location: select_trainee.php");
    exit();
}
?>
