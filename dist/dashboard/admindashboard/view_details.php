<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
require '../dbcon.php';

// Get the trainee ID from the query string and sanitize it
$trainee_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch trainee details
$sql = "
    SELECT 
        trainees.id,
        trainees.first_name, 
        trainees.last_name,
        trainees.middle_name, 
        trainees.name, 
        trainees.email, 
        trainees.phone_number, 
        trainees.image, 
        trainees.student_id, 
        trainees.required_hours,
        trainees.gender,
        trainees.date_of_birth,
        trainees.address,
        trainees.emergency_contact_name,
        trainees.emergency_contact_number,
        trainees.agency_id,
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

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $trainee_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if trainee exists
if ($result->num_rows === 0) {
    echo "<h1 class='text-red-600'>Trainee not found.</h1>";
    exit();
}

$trainee = $result->fetch_assoc();

// Fetch total worked hours for the current week
$this_week_query = "
    SELECT IFNULL(SUM(
        TIME_TO_SEC(TIMEDIFF(first_timeout, first_time_in)) + 
        IFNULL(TIME_TO_SEC(TIMEDIFF(second_timeout, second_time_in)), 0)
    ) / 3600, 0) AS weekly_worked_hours
    FROM studentloggeddata
    WHERE trainee_id = ? 
    AND WEEK(date, 1) = WEEK(NOW(), 1) 
    AND YEAR(date) = YEAR(NOW())
";

$stmt = $conn->prepare($this_week_query);
$stmt->bind_param("i", $trainee_id);
$stmt->execute();
$week_result = $stmt->get_result()->fetch_assoc();
$this_week_hours = $week_result['weekly_worked_hours'] ?? 0;

// Fetch weekly hours data with filtering
$filter_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$filter_month = isset($_GET['month']) ? (int)$_GET['month'] : 0;

$weekly_hours_query = "
    SELECT 
        DATE(DATE_FORMAT(date, '%Y-%m-%d') - INTERVAL WEEKDAY(date) DAY) as week_start_date,
        YEAR(date) as year,
        MONTH(date) as month,
        WEEKOFYEAR(date) as week_number,
        SUM(
            TIME_TO_SEC(TIMEDIFF(first_timeout, first_time_in)) + 
            IFNULL(TIME_TO_SEC(TIMEDIFF(second_timeout, second_time_in)), 0)
        ) / 3600 as total_weekly_hours,
        COUNT(DISTINCT date) as days_worked
    FROM 
        studentloggeddata
    WHERE 
        trainee_id = ? 
        AND first_time_in IS NOT NULL 
        AND first_timeout IS NOT NULL
        AND YEAR(date) = ?
";

if ($filter_month > 0) {
    $weekly_hours_query .= " AND MONTH(date) = ?";
}

$weekly_hours_query .= "
    GROUP BY 
        week_start_date
    ORDER BY 
        week_start_date DESC
";

$stmt = $conn->prepare($weekly_hours_query);
if ($filter_month > 0) {
    $stmt->bind_param("iii", $trainee_id, $filter_year, $filter_month);
} else {
    $stmt->bind_param("ii", $trainee_id, $filter_year);
}

$stmt->execute();
$result = $stmt->get_result();

$weekly_hours_data = [];

while ($row = $result->fetch_assoc()) {
    $row['total_weekly_hours'] = round($row['total_weekly_hours'], 1);
    $weekly_hours_data[] = $row;
}

// Fetch agencies from the database
$agencies = [];
$agency_query = "SELECT id, agency_name FROM agencies ORDER BY agency_name ASC";
$agency_result = $conn->query($agency_query);

if ($agency_result->num_rows > 0) {
    while ($row = $agency_result->fetch_assoc()) {
        $agencies[] = $row;
    }
}

// Close connections
$stmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainee Profile - <?php echo htmlspecialchars($trainee['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            light: '#4ade80',
                            DEFAULT: '#16a34a',
                            dark: '#166534'
                        },
                        secondary: {
                            light: '#fdba74',
                            DEFAULT: '#f97316',
                            dark: '#c2410c'
                        }
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-gray-50">
    <!-- Nav bar outside the centering container -->
    <?php include 'nav/top-nav.php'; ?>

    <!-- Main layout with sidebar spacer on wide screens -->
    <div class="flex flex-col md:flex-row">
        <!-- Sidebar spacer - only visible on wide screens -->
        <div class="hidden lg:block w-64 flex-shrink-0">
            <!-- This div serves as a spacer for the sidebar -->
        </div>

        <!-- Main content container -->
        <div class="flex-grow flex items-center justify-center min-h-screen py-8">
            <div class="w-full max-w-5xl p-0 bg-white rounded-xl shadow-lg overflow-hidden">
                <!-- Header Banner with Progress Bar -->
                <div class="relative bg-gradient-to-r from-primary-dark to-primary h-32 p-6">
                    <h1 class="text-3xl font-bold text-white mt-2 text-center">Trainee Profile</h1>
                    <a href="traineelist.php" class="inline-flex items-center gap-2 mb-2 text-sm text-white bg-white/20 px-3 py-1 rounded-md hover:bg-white/30 transition ">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                </div>

                <!-- Profile Card -->
                <div class="flex flex-col md:flex-row -mt-16 px-6">
                    <!-- Profile Image and Basic Info -->
                    <div class="w-full md:w-1/3 flex flex-col items-center">
                        <div class="relative">
                            <img src="<?php echo $trainee['image'] ?: 'default.jpg'; ?>" alt="Trainee Image"
                                class="w-32 h-32 border-4 border-white rounded-full shadow-md object-cover bg-gray-200">
                            <div class="absolute bottom-0 right-0 bg-primary rounded-full w-8 h-8 flex items-center justify-center border-2 border-white cursor-pointer"
                                onclick="openModal()">
                                <i class="fas fa-pencil-alt text-white text-sm"></i>
                            </div>
                        </div>
                        <h2 class="mt-4 text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($trainee['name']); ?></h2>
                        <p class="text-gray-600"><?php echo htmlspecialchars($trainee['student_id']); ?></p>
                        <p class="mt-1 text-sm text-primary-dark font-semibold"><?php echo htmlspecialchars($trainee['course_name']); ?></p>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($trainee['school_year']); ?></p>

                        <!-- Contact Info Section -->
                        <div class="mt-6 w-full p-4 bg-gray-50 rounded-lg">
                            <h3 class="text-lg font-semibold text-gray-700 mb-3">Contact Information</h3>
                            <div class="space-y-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-primary-light/20 rounded-full flex items-center justify-center">
                                        <i class="fas fa-envelope text-primary"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Email</p>
                                        <p class="text-sm"><?php echo htmlspecialchars($trainee['email']); ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-primary-light/20 rounded-full flex items-center justify-center">
                                        <i class="fas fa-phone text-primary"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Phone</p>
                                        <p class="text-sm"><?php echo htmlspecialchars($trainee['phone_number']); ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-primary-light/20 rounded-full flex items-center justify-center">
                                        <i class="fas fa-building text-primary"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Agency</p>
                                        <p class="text-sm"><?php echo htmlspecialchars($trainee['agency_name']); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Progress and Stats -->
                    <div class="w-full md:w-2/3 md:pl-6 mt-6 md:mt-0">
                        <!-- Stats Cards -->
                        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 mt-20">
                            <div class="grid grid-cols-2 gap-4 mb-6">
                                <!-- This Week Hours -->
                                <div class="bg-secondary-light/10 p-4 rounded-lg">
                                    <p class="text-xs text-gray-600 mb-1">This Week</p>
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar-week text-secondary mr-2"></i>
                                        <span class="font-medium <?php echo $this_week_hours > 0 ? 'text-secondary-600' : 'text-gray-700'; ?>">
                                            <?php echo number_format($this_week_hours, 1); ?>
                                        </span>
                                        <span class="text-xs text-gray-500 ml-1">hrs</span>
                                    </div>
                                </div>

                                <!-- Remaining Hours -->
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <p class="text-xs text-gray-600 mb-1">Remaining</p>
                                    <div class="flex items-center">
                                        <i class="fas fa-hourglass-half text-gray-500 mr-2"></i>
                                        <span class="text-xl font-bold text-gray-800"><?php echo number_format(max(0, $trainee['required_hours'] - $this_week_hours), 0); ?></span>
                                        <span class="text-xs ml-1">hrs</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Required Hours -->
                            <div class="bg-gray-50 p-3 rounded-lg flex items-center justify-between">
                                <div class="flex items-center">
                                    <i class="fas fa-check-circle text-primary-dark mr-2"></i>
                                    <span class="text-sm font-medium">Required Hours</span>
                                </div>
                                <span class="font-bold"><?php echo number_format($trainee['required_hours'], 0); ?> hrs</span>
                            </div>
                        </div>

                        <!-- Action Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <!-- Weekly Report Card -->
                            <div class="bg-gradient-to-r from-primary-light to-primary p-5 rounded-lg shadow-sm hover:shadow-md transition text-white">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h3 class="font-bold text-lg mb-1">Weekly Report</h3>
                                        <p class="text-white/80 text-sm mb-4">Week of <?php echo date('F j, Y', strtotime('monday this week')); ?></p>
                                    </div>
                                    <div class="text-white/90 bg-white/20 p-2 rounded-full">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                </div>
                                <a href="weekly_hours.php?id=<?php echo $trainee_id; ?>" class="mt-2 inline-block px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg text-sm text-white transition">
                                    View Report <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>

                            <!-- Timesheet Card -->
                            <div class="bg-gradient-to-r from-secondary-light to-secondary p-5 rounded-lg shadow-sm hover:shadow-md transition text-white">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h3 class="font-bold text-lg mb-1">Timesheet</h3>
                                        <p class="text-white/80 text-sm mb-4">View detailed attendance records</p>
                                    </div>
                                    <div class="text-white/90 bg-white/20 p-2 rounded-full">
                                        <i class="fas fa-table"></i>
                                    </div>
                                </div>
                                <a href="view_timesheet.php?id=<?php echo $trainee_id; ?>" class="mt-2 inline-block px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg text-sm text-white transition">
                                    View Timesheet <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex justify-end mt-6 space-x-3">
                            <button onclick="confirmDelete(<?php echo $trainee_id; ?>)" class="px-4 py-2 text-sm font-medium text-white bg-red-500 rounded-md hover:bg-red-600 transition flex items-center gap-2">
                                <i class="fas fa-trash-alt"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>

                <div class="p-6 border-t border-gray-100 mt-8 text-center text-sm text-gray-500">
                    <p>Last updated: <?php echo date('F j, Y'); ?></p>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div id="editModal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black bg-opacity-50">
            <div class="w-full max-w-2xl bg-white rounded-xl shadow-2xl mx-4 max-h-[90vh] overflow-y-auto">
                <!-- Modal Header -->
                <div class="sticky top-0 px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-primary text-white rounded-t-xl">
                    <h3 class="text-lg font-semibold">Edit Trainee Information</h3>
                    <button onclick="closeModal()" class="text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Modal Form -->
                <form method="POST" action="update_trainee.php" enctype="multipart/form-data" class="p-6">
                    <input type="hidden" name="trainee_id" value="<?php echo $trainee['id']; ?>">

                    <div class="flex flex-col md:flex-row gap-6 mb-6">
                        <!-- Current Image Preview -->
                        <div class="w-full md:w-1/3 flex flex-col items-center">
                            <img id="currentImage" src="<?php echo !empty($trainee['image']) ? $trainee['image'] : 'path/to/default/image.jpg'; ?>"
                                alt="Current Image" class="w-28 h-28 object-cover rounded-full border-4 border-gray-200 mb-2">
                            <label for="image" class="cursor-pointer text-primary text-sm hover:text-primary-dark">
                                <i class="fas fa-camera mr-1"></i> Change Photo
                            </label>
                            <input type="file" name="image" id="image" accept="image/*" class="hidden" onchange="previewImage(this)">
                        </div>

                        <!-- Basic Info -->
                        <div class="w-full md:w-2/3">
                            <div class="mb-4">
                                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                                <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($trainee['first_name']); ?>"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary" required>
                            </div>
                            <div class="mb-4">
                                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                                <input type="text" name="last_name" id="last_name" value="<?php echo htmlspecialchars($trainee['last_name']); ?>"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary" required>
                            </div>
                            <div class="mb-4">
                                <label for="middle_name" class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                                <input type="text" name="middle_name" id="middle_name" value="<?php echo htmlspecialchars($trainee['middle_name'] ?? ''); ?>"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($trainee['email']); ?>"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary" required>
                        </div>
                        <div>
                            <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-1">Phone Number <span class="text-red-500">*</span></label>
                            <input type="tel" name="phone_number" id="phone_number" value="<?php echo htmlspecialchars($trainee['phone_number']); ?>"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary" required>
                        </div>
                        <div>
                            <label for="student_id" class="block text-sm font-medium text-gray-700 mb-1">Student ID <span class="text-red-500">*</span></label>
                            <input type="text" name="student_id" id="student_id" value="<?php echo htmlspecialchars($trainee['student_id'] ?? ''); ?>"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary" required>
                        </div>
                        <div>
                            <label for="required_hours" class="block text-sm font-medium text-gray-700 mb-1">Required Hours <span class="text-red-500">*</span></label>
                            <input type="number" name="required_hours" id="required_hours" value="<?php echo htmlspecialchars($trainee['required_hours']); ?>"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary" required>
                        </div>
                    </div>

                    <!-- Personal Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">Gender <span class="text-red-500">*</span></label>
                            <select name="gender" id="gender"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary" required>
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo ($trainee['gender'] ?? '') == 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($trainee['gender'] ?? '') == 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo ($trainee['gender'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div>
                            <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-1">Date of Birth <span class="text-red-500">*</span></label>
                            <input type="date" name="date_of_birth" id="date_of_birth" value="<?php echo htmlspecialchars($trainee['date_of_birth'] ?? ''); ?>"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary" required>
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="mb-6">
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address <span class="text-red-500">*</span></label>
                        <textarea name="address" id="address" rows="2"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary" required><?php echo htmlspecialchars($trainee['address'] ?? ''); ?></textarea>
                    </div>

                    <!-- Emergency Contact Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label for="emergency_contact_name" class="block text-sm font-medium text-gray-700 mb-1">Emergency Contact Name <span class="text-red-500">*</span></label>
                            <input type="text" name="emergency_contact_name" id="emergency_contact_name" value="<?php echo htmlspecialchars($trainee['emergency_contact_name'] ?? ''); ?>"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary" required>
                        </div>
                        <div>
                            <label for="emergency_contact_number" class="block text-sm font-medium text-gray-700 mb-1">Emergency Contact Number <span class="text-red-500">*</span></label>
                            <input type="tel" name="emergency_contact_number" id="emergency_contact_number" value="<?php echo htmlspecialchars($trainee['emergency_contact_number'] ?? ''); ?>"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary" required>
                        </div>
                    </div>

                    <!-- Agency Dropdown -->
                    <div class="mb-6">
                        <label for="agency_id" class="block text-sm font-medium text-gray-700 mb-1">Agency <span class="text-red-500">*</span></label>
                        <select name="agency_id" id="agency_id"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary" required>
                            <option value="" disabled>Select an agency</option>
                            <?php foreach ($agencies as $agency): ?>
                                <option value="<?php echo $agency['id']; ?>" <?php echo ($trainee['agency_id'] == $agency['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($agency['agency_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Password Field -->
                    <div class="mb-6">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Change Password</label>
                        <input type="password" name="password" id="password" placeholder="Leave blank to keep current password"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                        <p class="text-xs text-gray-500 mt-1">Only fill this field if you want to change the password</p>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-3 border-t border-gray-100 pt-4">
                        <button type="button" onclick="closeModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-md hover:bg-primary-dark transition flex items-center gap-2">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openModal() {
                document.getElementById('editModal').classList.remove('hidden');
            }

            function closeModal() {
                document.getElementById('editModal').classList.add('hidden');
            }

            function previewImage(input) {
                if (input.files && input.files[0]) {
                    var reader = new FileReader();

                    reader.onload = function(e) {
                        document.getElementById('currentImage').src = e.target.result;
                    }

                    reader.readAsDataURL(input.files[0]);
                }
            }

            // SweetAlert Confirmation for Delete
            function confirmDelete(traineeId) {
                Swal.fire({
                    title: 'Delete Trainee?',
                    text: 'You are about to delete this trainee and all related records. This action cannot be undone!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f97316', // Orange
                    cancelButtonColor: '#9ca3af', // Gray
                    confirmButtonText: '<i class="fas fa-trash-alt mr-1"></i> Yes, delete',
                    cancelButtonText: 'Cancel',
                    customClass: {
                        confirmButton: 'text-sm',
                        cancelButton: 'text-sm'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Redirect to delete_trainee_function.php
                        window.location.href = `delete_trainee_function.php?id=${traineeId}`;
                    }
                });
            }
        </script>
</body>

</html>