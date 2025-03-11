<?php
require '../dbcon.php'; // Include database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $trainee_id = $_POST['trainee_id'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $middle_name = trim($_POST['middle_name'] ?? ''); // Optional field
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $student_id = trim($_POST['student_id'] ?? ''); // Optional field
    $required_hours = trim($_POST['required_hours']);
    $gender = trim($_POST['gender']);
    $date_of_birth = trim($_POST['date_of_birth']);
    $address = trim($_POST['address']);
    $emergency_contact_name = trim($_POST['emergency_contact_name']);
    $emergency_contact_number = trim($_POST['emergency_contact_number']);
    $agency_id = $_POST['agency_id'];
    $password = trim($_POST['password'] ?? ''); // Optional field

    // Combine first_name and last_name into name
    $full_name = $first_name . ' ' . $last_name;

    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone_number) || empty($required_hours) || empty($agency_id) || empty($gender) || empty($date_of_birth) || empty($address) || empty($emergency_contact_name) || empty($emergency_contact_number)) {
        die("Error: All required fields are missing.");
    }

    // Handle image upload (if a new image is selected)
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "uploads/trainees/"; // Ensure this directory exists

        // Check if directory exists, if not, create it
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true); // Create directory with full permissions
        }

        $image_name = basename($_FILES["image"]["name"]);
        $image_path = $target_dir . time() . "_" . $image_name; // Unique file name
        $image_file_type = strtolower(pathinfo($image_path, PATHINFO_EXTENSION));

        // Allowed file types
        $allowed_types = ["jpg", "jpeg", "png"];
        if (!in_array($image_file_type, $allowed_types)) {
            die("Error: Only JPG, JPEG, and PNG files are allowed.");
        }

        // Optional: Check file size (limit to 2MB)
        if ($_FILES["image"]["size"] > 2 * 1024 * 1024) {
            die("Error: File size exceeds 2MB.");
        }

        // Move uploaded file to target directory
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $image_path)) {
            die("Error: Failed to upload image. Check folder permissions.");
        }
    } else {
        // Fetch the existing image path before updating
        $sql = "SELECT image FROM trainees WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $trainee_id);
        $stmt->execute();
        $stmt->bind_result($existing_image);
        $stmt->fetch();
        $stmt->close();
        $image_path = $existing_image; // Use existing image if no new one is uploaded
    }

    // Handle password update (if a new password is provided)
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Hash the new password
    } else {
        // Fetch the existing password
        $sql = "SELECT password FROM trainees WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $trainee_id);
        $stmt->execute();
        $stmt->bind_result($existing_password);
        $stmt->fetch();
        $stmt->close();
        $hashed_password = $existing_password; // Use existing password if no new one is provided
    }

    // Update trainee with all fields
    $sql = "UPDATE trainees SET 
            first_name=?, 
            last_name=?, 
            middle_name=?, 
            name=?, 
            email=?, 
            phone_number=?, 
            student_id=?, 
            required_hours=?, 
            gender=?, 
            date_of_birth=?, 
            address=?, 
            emergency_contact_name=?, 
            emergency_contact_number=?, 
            agency_id=?, 
            image=?, 
            password=? 
            WHERE id=?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssssssssssssssi",
        $first_name,
        $last_name,
        $middle_name,
        $full_name,
        $email,
        $phone_number,
        $student_id,
        $required_hours,
        $gender,
        $date_of_birth,
        $address,
        $emergency_contact_name,
        $emergency_contact_number,
        $agency_id,
        $image_path,
        $hashed_password,
        $trainee_id
    );

    // Execute update
    if ($stmt->execute()) {
        // SweetAlert success message
        echo "
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Trainee updated successfully!',
                    confirmButtonColor: '#28a745',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'view_details.php?id={$trainee_id}';
                    }
                });
            });
        </script>
        ";
    } else {
        // SweetAlert error message
        echo "
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Error updating record: {$conn->error}',
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'OK'
                });
            });
        </script>
        ";
    }

    // Close connection
    $stmt->close();
    $conn->close();
}