<?php
// Start session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page if not logged in
    header("Location: ../login.php");
    exit();
}

// Include the database connection file
include '../dbcon.php';

// Fetch agencies, school years, courses, and departments
$agencies = $conn->query("SELECT id, agency_name FROM agencies");
$school_years = $conn->query("SELECT id, school_year FROM school_years ORDER BY school_year ASC");
$courses = $conn->query("SELECT id, course_name FROM courses");
$departments = $conn->query("SELECT department_id, department_name FROM department");


// Create an endpoint for getting courses by department (used by AJAX)
if (isset($_GET['action']) && $_GET['action'] == 'get_courses_by_department') {
    $department_id = isset($_GET['department_id']) ? $conn->real_escape_string($_GET['department_id']) : '';
    $response = ['success' => false, 'courses' => []];

    if (!empty($department_id)) {
        $courses_query = "SELECT id, course_name FROM courses WHERE department_id = '$department_id'";
        $courses_result = $conn->query($courses_query);

        if ($courses_result && $courses_result->num_rows > 0) {
            $courses = [];
            while ($row = $courses_result->fetch_assoc()) {
                $courses[] = $row;
            }
            $response['success'] = true;
            $response['courses'] = $courses;
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Handle form submission via AJAX
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
            // Fix: Correctly retrieve required_hours per trainee
            $requiredHours = isset($trainee['required_hours']) ? $conn->real_escape_string($trainee['required_hours']) : 0;
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

    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <title>Add Trainees</title>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 text-gray-800 min-h-screen">
    <?php
    include 'nav/top-nav.php';
    ?>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto bg-white rounded-xl shadow-md overflow-hidden border border-gray-100">
            <!-- Header -->
            <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-4">
                <h1 class="text-2xl font-bold text-white">Add Trainees</h1>
                <p class="text-primary-100 text-sm">Register new trainees to the system</p>
            </div>

            <!-- Content -->
            <div class="p-6">
                <!-- Selection Form -->
                <div class="space-y-5 mb-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Agency</label>
                            <select id="agency-select" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                                <option value="">Select Agency</option>
                                <?php while ($agency = $agencies->fetch_assoc()): ?>
                                    <option value="<?php echo $agency['id']; ?>"><?php echo $agency['agency_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">School Year</label>
                            <select id="school-year-select" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                                <option value="">Select School Year</option>
                                <?php while ($school_year = $school_years->fetch_assoc()): ?>
                                    <option value="<?php echo $school_year['id']; ?>"><?php echo $school_year['school_year']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                            <select id="department-select" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                                <option value="">Select Department</option>
                                <?php while ($department = $departments->fetch_assoc()): ?>
                                    <?php if ($department['department_name'] != 'University Wide'): ?> <!-- Exclude University Wide -->
                                        <option value="<?php echo $department['department_id']; ?>"><?php echo $department['department_name']; ?></option>
                                    <?php endif; ?>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Course</label>
                            <select id="course-select" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                                <option value="">Select Course</option>
                                <?php while ($course = $courses->fetch_assoc()): ?>
                                    <option value="<?php echo $course['id']; ?>"><?php echo $course['course_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Load Trainees Button -->
                <button id="load-trainees" class="w-full sm:w-auto bg-primary-600 hover:bg-primary-700 text-white font-medium py-2.5 px-5 rounded-lg transition-colors duration-200 flex items-center justify-center shadow-md">
                    <i class="fas fa-users mr-2"></i> Load Trainees
                </button>

                <!-- Trainee Form -->
                <form id="trainee-form" action="submit_trainee.php" method="POST" enctype="multipart/form-data" class="hidden mt-8">
                    <input type="hidden" name="agency_id" id="hidden-agency-id">
                    <input type="hidden" name="school_year_id" id="hidden-school-year-id">
                    <input type="hidden" name="course_id" id="hidden-course-id">
                    <input type="hidden" name="department_id" id="hidden-department-id">

                    <div id="trainee-list" class="space-y-6">
                        <!-- Trainee Rows Will Be Dynamically Loaded Here -->
                    </div>

                    <div class="flex flex-wrap gap-3 mt-8 justify-end">
                        <button type="button" id="add-trainee" class="bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 flex items-center shadow-md">
                            <i class="fas fa-plus mr-2"></i> Add Another Trainee
                        </button>
                        <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 flex items-center shadow-md">
                            <i class="fas fa-paper-plane mr-2"></i> Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Counter for unique trainee IDs
            let traineeCounter = 0;

            // Department change handler to filter courses
            $('#department-select').on('change', function() {
                const departmentId = $(this).val();

                if (!departmentId) {
                    // Reset course select
                    $('#course-select').html('<option value="">Select Course</option>');
                    return;
                }

                // Show loading in course select
                $('#course-select').html('<option value="">Loading courses...</option>').prop('disabled', true);

                // AJAX to get courses by department
                $.ajax({
                    url: 'get_courses_by_department.php', // Create this PHP file
                    type: 'GET',
                    data: {
                        department_id: departmentId
                    },
                    dataType: 'json',
                    success: function(response) {
                        let options = '<option value="">Select Course</option>';

                        if (response.success && response.courses && response.courses.length > 0) {
                            response.courses.forEach(function(course) {
                                options += `<option value="${course.id}">${course.course_name}</option>`;
                            });
                        } else {
                            options = '<option value="">No courses available</option>';
                        }

                        $('#course-select').html(options).prop('disabled', false);
                    },
                    error: function() {
                        $('#course-select').html('<option value="">Error loading courses</option>').prop('disabled', false);

                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to load courses. Please try again.',
                            confirmButtonColor: '#16a34a'
                        });
                    }
                });
            });

            // Load trainees button click handler
            $('#load-trainees').on('click', function() {
                // Get selected values
                const agencyId = $('#agency-select').val();
                const schoolYearId = $('#school-year-select').val();
                const departmentId = $('#department-select').val();
                const courseId = $('#course-select').val();

                // Validate selections
                if (!agencyId || !schoolYearId || !departmentId || !courseId) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Missing Information',
                        text: 'Please select all required fields (Agency, School Year, Department, and Course).',
                        confirmButtonColor: '#16a34a'
                    });
                    return;
                }

                // Set hidden inputs
                $('#hidden-agency-id').val(agencyId);
                $('#hidden-school-year-id').val(schoolYearId);
                $('#hidden-department-id').val(departmentId);
                $('#hidden-course-id').val(courseId);

                // Show trainee form and add initial trainee row
                $('#trainee-form').removeClass('hidden');
                $('#trainee-list').empty();
                addTraineeRow();

                // Scroll to form
                $('html, body').animate({
                    scrollTop: $('#trainee-form').offset().top - 100
                }, 500);
            });

            // Add another trainee button click handler
            $('#add-trainee').on('click', function() {
                addTraineeRow();
            });

            // Function to add a new trainee row
            function addTraineeRow() {
                traineeCounter++;
                const rowId = `trainee-${traineeCounter}`;

                const traineeRow = `
<div id="${rowId}" class="trainee-row border border-gray-200 rounded-lg p-5 bg-gray-50 relative">
    <button type="button" class="remove-trainee absolute top-3 right-3 text-red-500 hover:text-red-700 transition-colors duration-200">
        <i class="fas fa-times-circle text-lg"></i>
    </button>
    
    <h3 class="text-lg font-medium text-gray-800 mb-3 flex items-center">
        <i class="fas fa-user-graduate text-primary-600 mr-2"></i>
        Trainee #${traineeCounter}
    </h3>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
            <input type="text" name="trainee[${traineeCounter}][first_name]" required
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
            <input type="text" name="trainee[${traineeCounter}][last_name]" required
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
            <input type="text" name="trainee[${traineeCounter}][middle_name]"
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email Address <span class="text-red-500">*</span></label>
            <input type="email" name="trainee[${traineeCounter}][email]" required
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number <span class="text-red-500">*</span></label>
            <input type="tel" name="trainee[${traineeCounter}][phone_number]" required
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Student ID <span class="text-red-500">*</span></label>
            <input type="text" name="trainee[${traineeCounter}][student_id]" required
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Gender <span class="text-red-500">*</span></label>
            <select name="trainee[${traineeCounter}][gender]" required
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                <option value="">Select Gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth <span class="text-red-500">*</span></label>
            <input type="date" name="trainee[${traineeCounter}][date_of_birth]" required
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
        </div>
        
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Address <span class="text-red-500">*</span></label>
            <textarea name="trainee[${traineeCounter}][address]" required rows="2"
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"></textarea>
        </div>
        
        <!-- Required Hours Field -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Required Hours <span class="text-red-500">*</span></label>
            <input type="number" name="trainee[${traineeCounter}][required_hours]" required min="0"
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Profile Photo</label>
            <input type="file" name="trainee[${traineeCounter}][photo]" accept="image/*"
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Emergency Contact Name <span class="text-red-500">*</span></label>
            <input type="text" name="trainee[${traineeCounter}][emergency_contact_name]" required
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Emergency Contact Number <span class="text-red-500">*</span></label>
            <input type="tel" name="trainee[${traineeCounter}][emergency_contact_number]" required
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
            <input type="password" name="trainee[${traineeCounter}][password]" required
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
        </div>
    </div>
</div>`;

                $('#trainee-list').append(traineeRow);

                // Initialize remove button for the new row
                $(`#${rowId} .remove-trainee`).on('click', function() {
                    removeTraineeRow(rowId);
                });
            }

            // Function to remove a trainee row
            function removeTraineeRow(rowId) {
                if ($('.trainee-row').length <= 1) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Cannot Remove',
                        text: 'At least one trainee must be added.',
                        confirmButtonColor: '#16a34a'
                    });
                    return;
                }

                Swal.fire({
                    title: 'Remove Trainee?',
                    text: "Are you sure you want to remove this trainee?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#16a34a',
                    cancelButtonColor: '#ef4444',
                    confirmButtonText: 'Yes, remove it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $(`#${rowId}`).fadeOut(300, function() {
                            $(this).remove();

                            // Renumber the remaining trainee rows
                            $('.trainee-row').each(function(index) {
                                $(this).find('h3').html(`<i class="fas fa-user-graduate text-primary-600 mr-2"></i> Trainee #${index + 1}`);
                            });
                        });
                    }
                });
            }

            /// Form submission handler
            $('#trainee-form').on('submit', function(e) {
                e.preventDefault(); // Prevent default form submission

                // Validate form
                const form = this;
                if (!form.checkValidity()) {
                    form.reportValidity(); // Trigger browser validation
                    return;
                }

                // Show loading state
                Swal.fire({
                    title: 'Submitting...',
                    html: 'Please wait while we register the trainees',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    },
                });

                // Get the form data
                const formData = new FormData(form);

                // Debug FormData
                console.log("Form data being sent:");
                for (let [key, value] of formData.entries()) {
                    console.log(key, value);
                }

                // AJAX submission - Send data to submit_trainee.php
                $.ajax({
                    url: 'submit_trainee.php', // Use the correct script handling the submission
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log('Server response:', response);
                        try {
                            const result = typeof response === 'string' ? JSON.parse(response) : response;

                            if (result.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: result.message || 'Trainees have been successfully registered.',
                                    confirmButtonColor: '#16a34a',
                                }).then(() => {
                                    // Reset form
                                    form.reset();
                                    $('#trainee-list').empty();
                                    $('#trainee-form').addClass('hidden');
                                    traineeCounter = 0;
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: result.message || 'Something went wrong. Please try again.',
                                    confirmButtonColor: '#16a34a',
                                });
                            }
                        } catch (e) {
                            console.error('JSON parsing error:', e);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                html: 'Invalid response from server',
                                confirmButtonColor: '#16a34a',
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        console.error('Response Text:', xhr.responseText);
                        Swal.fire({
                            icon: 'error',
                            title: 'Server Error',
                            text: 'Could not connect to the server. Please try again later.',
                            confirmButtonColor: '#16a34a',
                        });
                    },
                });
            });

        });
    </script>
</body>

</html>