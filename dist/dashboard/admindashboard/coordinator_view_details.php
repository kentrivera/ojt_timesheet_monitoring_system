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
        trainees.name, 
        trainees.email, 
        trainees.phone_number, 
        trainees.image, 
        trainees.student_id, 
        trainees.required_hours,
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

    $stmt->close();
    $conn->close();
}
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
    <div class="flex items-center justify-center min-h-screen py-8">
        <div class="w-full max-w-5xl p-0 bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Header Banner with Progress Bar -->
            <div class="relative bg-gradient-to-r from-primary-dark to-primary h-32 p-6">
                <h1 class="text-3xl font-bold text-white mt-2 text-center">Trainee Profile</h1>
                <a href="coordinator_traineelist.php" class="inline-flex items-center gap-2 mb-2 text-sm text-white bg-white/20 px-3 py-1 rounded-md hover:bg-white/30 transition ">
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
                            <a href="coordinators_weekly_hours.php?id=<?php echo $trainee_id; ?>" class="mt-2 inline-block px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg text-sm text-white transition">
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
                            <a href="../coordinators/view_timesheet.php?id=<?php echo $trainee_id; ?>" class="mt-2 inline-block px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg text-sm text-white transition">
                                View Timesheet <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-6 border-t border-gray-100 mt-8 text-center text-sm text-gray-500">
                <p>Last updated: <?php echo date('F j, Y'); ?></p>
            </div>
        </div>
    </div>
</body>

</html>