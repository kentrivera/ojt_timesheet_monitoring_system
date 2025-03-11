<?php
require '../dbcon.php'; // Ensure database connection is included

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['trainee'])) {
    error_log("POST Data: " . print_r($_POST, true)); // Debugging: Log all POST data

    // Get hidden field values
    $agencyId = isset($_POST['agency_id']) ? $conn->real_escape_string($_POST['agency_id']) : '';
    $schoolYearId = isset($_POST['school_year_id']) ? $conn->real_escape_string($_POST['school_year_id']) : '';
    $departmentId = isset($_POST['department_id']) ? $conn->real_escape_string($_POST['department_id']) : '';
    $courseId = isset($_POST['course_id']) ? $conn->real_escape_string($_POST['course_id']) : '';

    $response = ['success' => false, 'message' => ''];

    try {
        $conn->begin_transaction();
        $successCount = 0;
        $errorMessages = [];

        foreach ($_POST['trainee'] as $index => $trainee) {
            // ✅ Fix: Retrieve required_hours correctly
            $requiredHours = isset($trainee['required_hours']) ? (int)$trainee['required_hours'] : 0;
            error_log("Trainee $index Required Hours: " . $requiredHours); // Debugging

            // Prepare other fields
            $studentId = $conn->real_escape_string($trainee['student_id']);
            $firstName = $conn->real_escape_string($trainee['first_name']);
            $lastName = $conn->real_escape_string($trainee['last_name']);
            $middleName = !empty($trainee['middle_name']) ? $conn->real_escape_string($trainee['middle_name']) : null;
            $email = $conn->real_escape_string($trainee['email']);
            $phoneNumber = $conn->real_escape_string($trainee['phone_number']);
            $gender = $conn->real_escape_string($trainee['gender']);
            $dateOfBirth = $conn->real_escape_string($trainee['date_of_birth']);
            $address = $conn->real_escape_string($trainee['address']);
            $emergencyName = $conn->real_escape_string($trainee['emergency_contact_name']);
            $emergencyNumber = $conn->real_escape_string($trainee['emergency_contact_number']);
            $hashedPassword = password_hash($trainee['password'], PASSWORD_DEFAULT);

            // Handle Image Upload
            $imagePath = null;
            if (!empty($_FILES['trainee']['tmp_name'][$index]['photo'])) {
                $uploadDir = 'uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                $fileName = basename($_FILES['trainee']['name'][$index]['photo']);
                $uploadFilePath = $uploadDir . uniqid() . '_' . $fileName;

                if (move_uploaded_file($_FILES['trainee']['tmp_name'][$index]['photo'], $uploadFilePath)) {
                    $imagePath = $uploadFilePath;
                } else {
                    $errorMessages[] = "Failed to upload image for trainee $firstName $lastName.";
                }
            }

            // Insert Query
            $sql = "INSERT INTO trainees (
                        name, first_name, last_name, middle_name, email, phone_number, student_id,
                        gender, date_of_birth, address, emergency_contact_name, emergency_contact_number,
                        password, image, agency_id, school_year_id, department_id, course_id,
                        user_role, required_hours, status, created_at
                    ) VALUES (
                        '$firstName $lastName', '$firstName', '$lastName', " . ($middleName ? "'$middleName'" : "NULL") . ", 
                        '$email', '$phoneNumber', '$studentId',
                        '$gender', '$dateOfBirth', '$address', '$emergencyName', '$emergencyNumber',
                        '$hashedPassword', " . ($imagePath ? "'$imagePath'" : "NULL") . ", '$agencyId', '$schoolYearId', '$departmentId', '$courseId',
                        'trainee', '$requiredHours', 'Active', NOW()
                    )";

            error_log("SQL Query: " . $sql); // Debugging

            if ($conn->query($sql)) {
                $successCount++;
            } else {
                $errorMessages[] = "Error adding trainee $firstName $lastName: " . $conn->error;
            }
        }

        // Transaction Handling
        if ($successCount === count($_POST['trainee'])) {
            $conn->commit();
            $response['success'] = true;
            $response['message'] = "Successfully registered $successCount trainee(s).";
        } else {
            $conn->rollback();
            $response['message'] = "Error: Only $successCount out of " . count($_POST['trainee']) . " trainee(s) were processed.";
            $response['errors'] = $errorMessages;
        }
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = "An error occurred: " . $e->getMessage();
    }

    // Output JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>
