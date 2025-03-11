<?php
session_start();
if (isset($_SESSION['id'])) {
    $traineeId = $_SESSION['id'];
} else {
    die("Error: id is missing in session.");
}

// Set timezone to Manila
date_default_timezone_set('Asia/Manila');

// Include the database connection file
include '../dbcon.php';

// Set the default mode to 'timesheet' if no mode is set in the URL
$mode = isset($_GET['mode']) && $_GET['mode'] === 'dtr' ? 'dtr' : 'timesheet';

// Get the selected date range
$selectedYear = isset($_GET['year']) ? $_GET['year'] : date('Y');
$selectedMonth = isset($_GET['month']) ? str_pad($_GET['month'], 2, '0', STR_PAD_LEFT) : date('m');
$selectedYearMonth = $selectedYear . '-' . $selectedMonth;

// Check if the 'id' parameter is present in the URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $trainee_id = $_GET['id'];

    // Fetch the timesheet data for the trainee
    $sql = "
        SELECT s.id, s.first_time_in, s.first_timeout, s.second_time_in, s.second_timeout, 
               s.first_activity_details, s.second_activity_details, s.date, 
               s.first_image, s.second_image, s.first_timeout_image, s.second_timeout_image
        FROM studentloggeddata s
        WHERE s.trainee_id = ? 
        AND DATE_FORMAT(s.date, '%Y-%m') = ?
        ORDER BY s.date ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $trainee_id, $selectedYearMonth);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    // Fetch all available years and months for this trainee
    $yearsSql = "
        SELECT DISTINCT YEAR(date) as year
        FROM studentloggeddata
        WHERE trainee_id = ?
        ORDER BY year DESC
    ";
    $yearsStmt = $conn->prepare($yearsSql);
    $yearsStmt->bind_param('i', $trainee_id);
    $yearsStmt->execute();
    $yearsResult = $yearsStmt->get_result();
    $availableYears = $yearsResult->fetch_all(MYSQLI_ASSOC);

    // Fetch months for the selected year
    $monthsSql = "
        SELECT DISTINCT MONTH(date) as month
        FROM studentloggeddata
        WHERE trainee_id = ? 
        AND YEAR(date) = ?
        ORDER BY month ASC
    ";
    $monthsStmt = $conn->prepare($monthsSql);
    $monthsStmt->bind_param('ii', $trainee_id, $selectedYear);
    $monthsStmt->execute();
    $monthsResult = $monthsStmt->get_result();
    $availableMonths = $monthsResult->fetch_all(MYSQLI_ASSOC);

    // Fetch trainee name for the PDF title
    $nameSQL = "
        SELECT CONCAT(name) as fullname
        FROM trainees
        WHERE id = ?
    ";
    $nameStmt = $conn->prepare($nameSQL);
    $nameStmt->bind_param('i', $trainee_id);
    $nameStmt->execute();
    $nameResult = $nameStmt->get_result();
    $traineeData = $nameResult->fetch_assoc();
    $traineeName = $traineeData ? $traineeData['fullname'] : 'Unknown Trainee';

    // Calculate attendance statistics
    $totalDaysInMonth = cal_days_in_month(CAL_GREGORIAN, intval($selectedMonth), intval($selectedYear));
    $daysPresent = count($data);
    $attendanceRate = $totalDaysInMonth > 0 ? ($daysPresent / $totalDaysInMonth) * 100 : 0;

    // Calculate total hours
    $totalWorkedHours = 0;
    foreach ($data as $row) {
        $firstShiftHours = 0;
        $secondShiftHours = 0;

        if (!empty($row['first_time_in']) && !empty($row['first_timeout'])) {
            $firstShiftHours = (strtotime($row['first_timeout']) - strtotime($row['first_time_in'])) / 3600;
        }

        if (!empty($row['second_time_in']) && !empty($row['second_timeout'])) {
            $secondShiftHours = (strtotime($row['second_timeout']) - strtotime($row['second_time_in'])) / 3600;
        }

        $totalWorkedHours += $firstShiftHours + $secondShiftHours;
    }
} else {
    // Redirect to a selection or error page if no valid 'id' is provided
    header("Location: select_trainee.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainee Timesheet - <?php echo htmlspecialchars($traineeName); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js" defer></script>
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
                        },
                        secondary: {
                            50: '#fff7ed',
                            100: '#ffedd5',
                            200: '#fed7aa',
                            300: '#fdba74',
                            400: '#fb923c',
                            500: '#f97316',
                            600: '#ea580c',
                            700: '#c2410c',
                            800: '#9a3412',
                            900: '#7c2d12',
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-100">
    <!-- Top Navigation -->
    <?php include 'nav/top-nav.php'; ?>

    <!-- Main Content -->
    <div class="flex pt-14">
        <!-- Content -->
        <main class="flex-1 p-4">
            <!-- Header and Toggle Bar -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-2">
                <h1 class="text-lg font-bold text-primary-700 flex items-center gap-1">
                    <i class="fas fa-user-circle"></i>
                    <span class="truncate"><?php echo $mode === 'dtr' ? 'Daily Time Record' : 'Timesheet'; ?>: <?php echo htmlspecialchars($traineeName); ?></span>
                </h1>

                <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                    <button id="toggleMode" class="px-3 py-1.5 text-xs font-medium text-white bg-primary-600 rounded shadow hover:bg-primary-700 transition flex items-center gap-1">
                        <i class="fas fa-<?php echo $mode === 'dtr' ? 'list' : 'calendar-check'; ?>"></i>
                        <span>Switch to <?php echo $mode === 'dtr' ? 'Timesheet' : 'DTR'; ?></span>
                    </button>

                    <div id="dtrButtons" class="<?php echo $mode === 'dtr' ? '' : 'hidden'; ?> flex gap-1 print:hidden">
                        <!-- Download PDF Button -->
                        <button id="downloadButton" class="px-3 py-1.5 text-xs font-medium text-white bg-secondary-600 rounded shadow hover:bg-secondary-700 transition flex items-center gap-1">
                            <i class="fas fa-download"></i>
                            <span class="hidden xs:inline">PDF</span>
                        </button>
                        <!-- print Button -->
                        <button id="printButton" class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded shadow hover:bg-blue-700 transition flex items-center gap-1">
                            <i class="fas fa-print"></i>
                            <span class="hidden xs:inline">Print</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 mb-2">
                <!-- Days Present -->
                <div class="p-4 transition-transform duration-200 ease-out bg-white rounded shadow hover:scale-105">
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="block text-xs text-gray-500">Days Present</span>
                            <span class="text-sm font-bold"><?php echo $daysPresent; ?> / <?php echo $totalDaysInMonth; ?> days</span>
                        </div>
                        <div class="bg-green-100 p-1.5 rounded-full">
                            <i class="fas fa-calendar-check text-primary-600 text-sm"></i>
                        </div>
                    </div>
                    <div class="mt-1">
                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                            <div class="bg-primary-500 rounded-full h-1.5" style="width: <?php echo min(100, $attendanceRate); ?>%"></div>
                        </div>
                        <div class="text-right text-xs mt-0.5"><?php echo number_format($attendanceRate, 1); ?>%</div>
                    </div>
                </div>

                <!-- Total Hours -->
                <div class="p-4 transition-transform duration-200 ease-out bg-white rounded shadow hover:scale-105">
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="block text-xs text-gray-500">Total Hours</span>
                            <span class="text-sm font-bold"><?php echo number_format($totalWorkedHours, 1); ?> hours</span>
                        </div>
                        <div class="bg-blue-100 p-1.5 rounded-full">
                            <i class="fas fa-clock text-blue-600 text-sm"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="group relative inline-block text-xs text-gray-500">
                            <?php
                            $averageHoursPerDay = $daysPresent > 0 ? $totalWorkedHours / $daysPresent : 0;
                            echo 'Avg. ' . number_format($averageHoursPerDay, 1) . ' hrs/day';
                            ?>
                            <span class="hidden group-hover:block absolute bottom-full left-0 transform -translate-y-1 w-32 bg-gray-800 text-white text-center py-1 px-2 rounded text-xs z-10">Average hours per day present</span>
                        </div>
                    </div>
                </div>

                <!-- Month Selection -->
                <div class="p-4 transition-transform duration-200 ease-out bg-white rounded shadow hover:scale-105">
                    <div class="flex flex-row sm:flex-col h-full justify-between">
                        <span class="block text-xs text-gray-500 mb-1">Current Period</span>
                        <div class="flex items-center gap-1 mb-1">
                            <i class="fas fa-calendar-alt text-primary-500 text-sm"></i>
                            <span class="text-sm font-bold"><?php echo date('F Y', strtotime($selectedYearMonth . '-01')); ?></span>
                        </div>
                        <div class="flex flex-wrap w-full items-center gap-1">
                            <select id="yearSelect" class="text-xs border border-gray-300 rounded px-2 py-1 w-24 focus:outline-none focus:ring-1 focus:ring-primary-500">
                                <?php foreach ($availableYears as $year): ?>
                                    <option value="<?php echo $year['year']; ?>" <?php echo $year['year'] == $selectedYear ? 'selected' : ''; ?>>
                                        <?php echo $year['year']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <select id="monthSelect" class="text-xs border border-gray-300 rounded px-2 py-1 w-24 focus:outline-none focus:ring-1 focus:ring-primary-500">
                                <?php foreach ($availableMonths as $month): ?>
                                    <?php
                                    $monthNum = str_pad($month['month'], 2, '0', STR_PAD_LEFT);
                                    $monthName = date('F', mktime(0, 0, 0, $month['month'], 1));
                                    $selected = $monthNum === $selectedMonth ? 'selected' : '';
                                    ?>
                                    <option value="<?php echo $monthNum; ?>" <?php echo $selected; ?>>
                                        <?php echo $monthName; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timesheet Table -->
            <div id="timesheetTable" class="<?php echo $mode === 'dtr' ? 'hidden' : ''; ?> bg-white rounded-lg shadow p-2 md:p-3 mt-4">
                <div class="flex flex-col xs:flex-row items-start xs:items-center justify-between mb-3 gap-2">
                    <div class="flex items-center gap-1">
                        <i class="fas fa-clipboard-list text-primary-600"></i>
                        <h2 class="text-base font-bold text-gray-800">Timesheet Details</h2>
                    </div>

                    <div class="flex gap-1 w-full xs:w-auto">
                        <button id="showTimesheet" class="flex-1 xs:flex-none px-2 py-1 text-xs text-white bg-primary-600 rounded hover:bg-primary-700 transition">
                            <i class="fas fa-clock mr-1"></i>Time Data
                        </button>
                        <button id="showActivities" class="flex-1 xs:flex-none px-2 py-1 text-xs text-white bg-gray-500 rounded hover:bg-gray-600 transition">
                            <i class="fas fa-tasks mr-1"></i>Activities
                        </button>
                    </div>
                </div>

                <?php if (!empty($data)): ?>
                    <!-- Timesheet View -->
                    <div id="timesheetView" class="overflow-x-auto -mx-2 px-2">
                        <!-- Mobile View (Card-based layout) -->
                        <div class="md:hidden space-y-2">
                            <?php foreach ($data as $row): ?>
                                <div class="border border-gray-200 rounded overflow-hidden">
                                    <!-- Date Header -->
                                    <div class="bg-gray-50 p-2 font-medium text-xs border-b border-gray-200">
                                        <?php echo date('M d, Y (D)', strtotime($row['date'])); ?>
                                    </div>

                                    <!-- Time Entries -->
                                    <div class="p-2 space-y-2 text-xs">
                                        <div class="grid grid-cols-2 gap-1">
                                            <div>
                                                <span class="text-gray-500">First In (AM):</span>
                                                <span class="ml-1"><?php echo !empty($row['first_time_in']) ? date("H:i", strtotime($row['first_time_in'])) : '0'; ?></span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">First Out (AM):</span>
                                                <span class="ml-1"><?php echo !empty($row['first_timeout']) ? date("H:i", strtotime($row['first_timeout'])) : '0'; ?></span>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-1">
                                            <div>
                                                <span class="text-gray-500">Second In (PM):</span>
                                                <span class="ml-1"><?php echo !empty($row['second_time_in']) ? date("H:i", strtotime($row['second_time_in'])) : '0'; ?></span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">Second Out (PM):</span>
                                                <span class="ml-1"><?php echo !empty($row['second_timeout']) ? date("H:i", strtotime($row['second_timeout'])) : '0'; ?></span>
                                            </div>
                                        </div>

                                        <div>
                                            <span class="text-gray-500 inline-block mb-1">Images:</span>
                                            <div class="flex gap-2">
                                                <?php
                                                $images = [
                                                    ['src' => $row['first_image'], 'title' => 'First Time In'],
                                                    ['src' => $row['first_timeout_image'], 'title' => 'First Time Out'],
                                                    ['src' => $row['second_image'], 'title' => 'Second Time In'],
                                                    ['src' => $row['second_timeout_image'], 'title' => 'Second Time Out']
                                                ];

                                                foreach ($images as $image):
                                                    if (!empty($image['src'])):
                                                ?>
                                                        <div class="relative">
                                                            <img
                                                                src="<?php echo htmlspecialchars($image['src']); ?>"
                                                                alt="<?php echo $image['title']; ?>"
                                                                class="w-5 h-5 rounded shadow cursor-pointer"
                                                                onclick="openPreviewModal('<?php echo htmlspecialchars($image['src']); ?>', '<?php echo $image['title']; ?>')">
                                                            <div class="absolute -top-1 -right-1 w-2 h-2 bg-green-500 rounded-full border border-white"></div>
                                                        </div>
                                                <?php
                                                    endif;
                                                endforeach;
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Desktop View (Table layout) -->
                        <table class="hidden md:table w-full border-collapse text-xs min-w-max">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="border border-gray-200 p-1.5 text-left font-medium text-gray-600">Date</th>
                                    <th class="border border-gray-200 p-1.5 text-left font-medium text-gray-600">First In(AM)</th>
                                    <th class="border border-gray-200 p-1.5 text-left font-medium text-gray-600">First Out(AM)</th>
                                    <th class="border border-gray-200 p-1.5 text-left font-medium text-gray-600">Second In(PM)</th>
                                    <th class="border border-gray-200 p-1.5 text-left font-medium text-gray-600">Second Out(PM)</th>
                                    <th class="border border-gray-200 p-1.5 text-left font-medium text-gray-600">Images</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data as $row): ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="border border-gray-200 p-1.5 font-medium"><?php echo date('M d, Y (D)', strtotime($row['date'])); ?></td>
                                        <td class="border border-gray-200 p-1.5"><?php echo !empty($row['first_time_in']) ? date("H:i", strtotime($row['first_time_in'])) : '0'; ?></td>
                                        <td class="border border-gray-200 p-1.5"><?php echo !empty($row['first_timeout']) ? date("H:i", strtotime($row['first_timeout'])) : '0'; ?></td>
                                        <td class="border border-gray-200 p-1.5"><?php echo !empty($row['second_time_in']) ? date("H:i", strtotime($row['second_time_in'])) : '0'; ?></td>
                                        <td class="border border-gray-200 p-1.5"><?php echo !empty($row['second_timeout']) ? date("H:i", strtotime($row['second_timeout'])) : '0'; ?></td>
                                        <td class="border border-gray-200 p-1.5">
                                            <div class="flex gap-1">
                                                <?php
                                                $images = [
                                                    ['src' => $row['first_image'], 'title' => 'First Time In'],
                                                    ['src' => $row['first_timeout_image'], 'title' => 'First Time Out'],
                                                    ['src' => $row['second_image'], 'title' => 'Second Time In'],
                                                    ['src' => $row['second_timeout_image'], 'title' => 'Second Time Out']
                                                ];

                                                foreach ($images as $image):
                                                    if (!empty($image['src'])):
                                                ?>
                                                        <div class="relative">
                                                            <img
                                                                src="<?php echo htmlspecialchars($image['src']); ?>"
                                                                alt="<?php echo $image['title']; ?>"
                                                                class="w-5 h-5 rounded shadow cursor-pointer"
                                                                onclick="openPreviewModal('<?php echo htmlspecialchars($image['src']); ?>', '<?php echo $image['title']; ?>')">
                                                            <div class="absolute -top-1 -right-1 w-2 h-2 bg-green-500 rounded-full border border-white"></div>
                                                        </div>
                                                <?php
                                                    endif;
                                                endforeach;
                                                ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Activities View -->
                    <div id="activitiesView" class="hidden overflow-x-auto -mx-2 px-2">
                        <!-- Mobile View (Card-based layout) -->
                        <div class="md:hidden space-y-2">
                            <?php foreach ($data as $row): ?>
                                <div class="border border-gray-200 rounded overflow-hidden">
                                    <!-- Date Header -->
                                    <div class="bg-gray-50 p-2 font-medium text-xs border-b border-gray-200">
                                        <?php echo date('M d, Y (D)', strtotime($row['date'])); ?>
                                    </div>

                                    <!-- Activity Entries -->
                                    <div class="p-2 space-y-2 text-xs">
                                        <div>
                                            <span class="text-gray-500">First Activity:</span>
                                            <div class="mt-1">
                                                <?php if (!empty($row['first_activity_details'])): ?>
                                                    <div class="line-clamp-2">
                                                        <?php echo htmlspecialchars($row['first_activity_details']); ?>
                                                    </div>
                                                    <?php if (strlen($row['first_activity_details']) > 60): ?>
                                                        <button
                                                            class="text-primary-600 text-xs mt-1"
                                                            onclick="showFullActivity('<?php echo addslashes(htmlspecialchars($row['first_activity_details'])); ?>', 'First Activity')">
                                                            Show more
                                                        </button>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-gray-400">No activity recorded</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div>
                                            <span class="text-gray-500">Second Activity:</span>
                                            <div class="mt-1">
                                                <?php if (!empty($row['second_activity_details'])): ?>
                                                    <div class="line-clamp-2">
                                                        <?php echo htmlspecialchars($row['second_activity_details']); ?>
                                                    </div>
                                                    <?php if (strlen($row['second_activity_details']) > 60): ?>
                                                        <button
                                                            class="text-primary-600 text-xs mt-1"
                                                            onclick="showFullActivity('<?php echo addslashes(htmlspecialchars($row['second_activity_details'])); ?>', 'Second Activity')">
                                                            Show more
                                                        </button>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-gray-400">No activity recorded</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Desktop View (Table layout) -->
                        <table class="hidden md:table w-full border-collapse text-xs min-w-max">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="border border-gray-200 p-1.5 text-left font-medium text-gray-600">Date</th>
                                    <th class="border border-gray-200 p-1.5 text-left font-medium text-gray-600">First Activity</th>
                                    <th class="border border-gray-200 p-1.5 text-left font-medium text-gray-600">Second Activity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data as $row): ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="border border-gray-200 p-1.5 font-medium"><?php echo date('M d, Y (D)', strtotime($row['date'])); ?></td>
                                        <td class="border border-gray-200 p-1.5">
                                            <?php if (!empty($row['first_activity_details'])): ?>
                                                <div class="group relative">
                                                    <div class="line-clamp-2">
                                                        <?php echo htmlspecialchars($row['first_activity_details']); ?>
                                                    </div>
                                                    <?php if (strlen($row['first_activity_details']) > 100): ?>
                                                        <div class="hidden group-hover:block absolute z-10 -ml-2 p-2 w-64 bg-white border border-gray-200 shadow-lg rounded-md">
                                                            <?php echo htmlspecialchars($row['first_activity_details']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-gray-400">No activity recorded</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="border border-gray-200 p-1.5">
                                            <?php if (!empty($row['second_activity_details'])): ?>
                                                <div class="group relative">
                                                    <div class="line-clamp-2">
                                                        <?php echo htmlspecialchars($row['second_activity_details']); ?>
                                                    </div>
                                                    <?php if (strlen($row['second_activity_details']) > 100): ?>
                                                        <div class="hidden group-hover:block absolute z-10 -ml-2 p-2 w-64 bg-white border border-gray-200 shadow-lg rounded-md">
                                                            <?php echo htmlspecialchars($row['second_activity_details']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-gray-400">No activity recorded</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-6 text-gray-500">
                        <i class="fas fa-calendar-times text-2xl mb-2"></i>
                        <p>No timesheet data available for the selected month.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Simple Modal for Activity Details on Mobile -->
            <div id="activityModal" class="fixed inset-0 bg-black bg-opacity-40 z-50 items-center justify-center hidden">
                <div class="bg-white rounded max-w-lg mx-auto mt-20 max-h-[80vh] overflow-y-auto">
                    <div class="p-3 border-b border-gray-200 flex justify-between items-center">
                        <h3 id="activityModalTitle" class="font-medium text-sm"></h3>
                        <button onclick="closeActivityModal()" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div id="activityModalContent" class="p-3 text-sm"></div>
                </div>
            </div>

            <script>
                // Activity modal functionality
                function showFullActivity(content, title) {
                    document.getElementById('activityModalTitle').textContent = title;
                    document.getElementById('activityModalContent').textContent = content;
                    document.getElementById('activityModal').classList.remove('hidden');
                    document.getElementById('activityModal').classList.add('flex');
                    document.body.style.overflow = 'hidden';
                }

                function closeActivityModal() {
                    document.getElementById('activityModal').classList.add('hidden');
                    document.getElementById('activityModal').classList.remove('flex');
                    document.body.style.overflow = '';
                }

                // Tab switching logic
                document.getElementById('showTimesheet').addEventListener('click', function() {
                    document.getElementById('timesheetView').classList.remove('hidden');
                    document.getElementById('activitiesView').classList.add('hidden');
                    this.classList.remove('bg-gray-500', 'hover:bg-gray-600');
                    this.classList.add('bg-primary-600', 'hover:bg-primary-700');
                    document.getElementById('showActivities').classList.remove('bg-primary-600', 'hover:bg-primary-700');
                    document.getElementById('showActivities').classList.add('bg-gray-500', 'hover:bg-gray-600');
                });

                document.getElementById('showActivities').addEventListener('click', function() {
                    document.getElementById('activitiesView').classList.remove('hidden');
                    document.getElementById('timesheetView').classList.add('hidden');
                    this.classList.remove('bg-gray-500', 'hover:bg-gray-600');
                    this.classList.add('bg-primary-600', 'hover:bg-primary-700');
                    document.getElementById('showTimesheet').classList.remove('bg-primary-600', 'hover:bg-primary-700');
                    document.getElementById('showTimesheet').classList.add('bg-gray-500', 'hover:bg-gray-600');
                });
            </script>
            
            <!-- DTR Table -->
            <div id="dtrTable" class="<?php echo $mode === 'dtr' ? '' : 'hidden'; ?> bg-white rounded-lg shadow p-3 mt-4">
                <div class="flex items-center gap-1 mb-3">
                    <i class="fas fa-calendar-check text-primary-600"></i>
                    <h2 class="text-base font-bold text-gray-800">Daily Time Record</h2>
                </div>

                <div id="dtrContent">
                    <!-- Trainee info for PDF/Print -->
                    <div class="mb-3 pb-2 border-b border-gray-200 flex justify-between items-center">
                        <div>
                            <h3 class="text-sm font-bold text-primary-700"><?php echo htmlspecialchars($traineeName); ?></h3>
                            <p class="text-xs text-gray-600">
                                <span class="font-medium">Period:</span> <?php echo date('F Y', strtotime($selectedYearMonth . '-01')); ?>
                            </p>
                        </div>
                    </div>
                    <?php if (!empty($data)): ?>
                        <?php $totalWorkedHours = 0; ?>
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse text-xs">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="border border-gray-200 p-1.5 text-left font-medium text-gray-600">Date</th>
                                        <th class="border border-gray-200 p-1.5 text-left font-medium text-gray-600">First In(AM)</th>
                                        <th class="border border-gray-200 p-1.5 text-left font-medium text-gray-600">First Out(AM)</th>
                                        <th class="border border-gray-200 p-1.5 text-left font-medium text-gray-600">Second In(PM)</th>
                                        <th class="border border-gray-200 p-1.5 text-left font-medium text-gray-600">Second Out(PM)</th>
                                        <th class="border border-gray-200 p-1.5 text-left font-medium text-gray-600">Total Hours</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data as $row): ?>
                                        <?php
                                        $firstShiftHours = !empty($row['first_time_in']) && !empty($row['first_timeout'])
                                            ? (strtotime($row['first_timeout']) - strtotime($row['first_time_in'])) / 3600
                                            : 0;

                                        $secondShiftHours = !empty($row['second_time_in']) && !empty($row['second_timeout'])
                                            ? (strtotime($row['second_timeout']) - strtotime($row['second_time_in'])) / 3600
                                            : 0;

                                        $totalWorkedHours += $firstShiftHours + $secondShiftHours;
                                        ?>
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="border border-gray-200 p-1.5"><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                            <td class="border border-gray-200 p-1.5"><?php echo !empty($row['first_time_in']) ? date("H:i", strtotime($row['first_time_in'])) : ''; ?></td>
                                            <td class="border border-gray-200 p-1.5"><?php echo !empty($row['first_timeout']) ? date("H:i", strtotime($row['first_timeout'])) : ''; ?></td>
                                            <td class="border border-gray-200 p-1.5"><?php echo !empty($row['second_time_in']) ? date("H:i", strtotime($row['second_time_in'])) : ''; ?></td>
                                            <td class="border border-gray-200 p-1.5"><?php echo !empty($row['second_timeout']) ? date("H:i", strtotime($row['second_timeout'])) : ''; ?></td>
                                            <td class="border border-gray-200 p-1.5">
                                                <?php echo ($firstShiftHours + $secondShiftHours) > 0
                                                    ? '<span class="font-medium">' . number_format($firstShiftHours + $secondShiftHours, 1) . '</span> hrs'
                                                    : ''; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="bg-gray-50 font-medium">
                                        <td colspan="5" class="border-t-2 border-gray-300 p-1.5 text-right">Total Hours:</td>
                                        <td class="border-t-2 border-gray-300 p-1.5 text-primary-700 font-bold"><?php echo number_format($totalWorkedHours, 1); ?> hrs</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <?php
                        if (isset($_GET['id'])) {
                            $id = intval($_GET['id']); // Ensure it's an integer
                        } elseif (isset($_POST['id'])) {
                            $id = intval($_POST['id']);
                        } else {
                            die("Error: id is not provided.");
                        }

                        // Default supervisor name if not found
                        $supervisorName = "Not Assigned";

                        // Query to fetch the person_incharge directly using JOIN
                        $query = "
                         SELECT a.person_incharge 
                         FROM trainees t
                         JOIN agencies a ON t.agency_id = a.id
                         WHERE t.id = ?
                     ";

                        if ($stmt = $conn->prepare($query)) {
                            $stmt->bind_param("i", $id);
                            $stmt->execute();
                            $stmt->bind_result($supervisor);

                            if ($stmt->fetch() && !empty($supervisor)) {
                                $supervisorName = htmlspecialchars($supervisor);
                            }

                            $stmt->close();
                        }
                        ?>

                        <!-- Display Supervisor -->
                        <div class="m-6 p-4 flex justify-end text-xs supervisor_container">
                            <div class="border-t border-gray-400 pt-2 mt-6 text-center supervisor">
                                <p class="font-medium supervisor-name"><?php echo $supervisorName; ?></p>
                                <p class="text-gray-500 font-bold supervisor-role">Supervisor</p>
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="text-center py-6 text-gray-500">
                            <i class="fas fa-calendar-times text-2xl mb-2"></i>
                            <p>No DTR data available for the selected month.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>



            <!-- Modal for Image Preview -->
            <div id="previewModal" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center p-4 z-50 print:hidden" onclick="closePreviewModal()">
                <div class="bg-white rounded-lg shadow-lg p-4 max-w-2xl w-full relative" onclick="event.stopPropagation()">
                    <!-- Close Button -->
                    <button onclick="closePreviewModal()" class="absolute top-2 right-2 text-gray-700 hover:text-gray-900">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <!-- Image in Modal -->
                    <img id="modalImage" src="" alt="" class="w-full h-auto rounded">
                    <!-- Title in Modal -->
                    <p id="modalTitle" class="text-center mt-2 text-sm font-semibold"></p>
                </div>
            </div>
            <script src="view_timesheet.js"></script>
            <!-- Include jsPDF and html2canvas -->
            <script src="coordinator_print_function.js"></script>

            <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Get reference to the main UI elements
                    const showTimesheetBtn = document.getElementById('showTimesheet');
                    const showActivitiesBtn = document.getElementById('showActivities');
                    const timesheetView = document.getElementById('timesheetView');
                    const activitiesView = document.getElementById('activitiesView');

                    // Tab switching functionality
                    if (showTimesheetBtn && showActivitiesBtn) {
                        // Show timesheet data and highlight its button
                        showTimesheetBtn.addEventListener('click', function() {
                            timesheetView.classList.remove('hidden');
                            activitiesView.classList.add('hidden');

                            // Update button styling
                            showTimesheetBtn.classList.remove('bg-gray-500', 'hover:bg-gray-600');
                            showTimesheetBtn.classList.add('bg-primary-600', 'hover:bg-primary-700');

                            showActivitiesBtn.classList.remove('bg-primary-600', 'hover:bg-primary-700');
                            showActivitiesBtn.classList.add('bg-gray-500', 'hover:bg-gray-600');
                        });

                        // Show activities data and highlight its button
                        showActivitiesBtn.addEventListener('click', function() {
                            activitiesView.classList.remove('hidden');
                            timesheetView.classList.add('hidden');

                            // Update button styling
                            showActivitiesBtn.classList.remove('bg-gray-500', 'hover:bg-gray-600');
                            showActivitiesBtn.classList.add('bg-primary-600', 'hover:bg-primary-700');

                            showTimesheetBtn.classList.remove('bg-primary-600', 'hover:bg-primary-700');
                            showTimesheetBtn.classList.add('bg-gray-500', 'hover:bg-gray-600');
                        });
                    }

                    // Image preview modal functionality
                    window.openPreviewModal = function(imageSrc, title) {
                        // Create modal elements
                        const modal = document.createElement('div');
                        modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75';

                        const modalContent = document.createElement('div');
                        modalContent.className = 'bg-white rounded-lg overflow-hidden shadow-xl max-w-3xl mx-4';

                        const modalHeader = document.createElement('div');
                        modalHeader.className = 'flex items-center justify-between p-4 border-b';
                        modalHeader.innerHTML = `
                            <h3 class="font-medium text-gray-800">${title}</h3>
                            <button id="closeModal" class="text-gray-500 hover:text-gray-700">
                              <i class="fas fa-times"></i>
                             </button>
                        `;

                        const modalBody = document.createElement('div');
                        modalBody.className = 'p-4';
                        modalBody.innerHTML = `
                             <img src="${imageSrc}" alt="${title}" class="max-w-full h-auto mx-auto">
                         `;

                        // Assemble and append modal to the body
                        modalContent.appendChild(modalHeader);
                        modalContent.appendChild(modalBody);
                        modal.appendChild(modalContent);
                        document.body.appendChild(modal);

                        // Add close functionality
                        document.getElementById('closeModal').addEventListener('click', function() {
                            document.body.removeChild(modal);
                        });

                        // Close on click outside of modal content
                        modal.addEventListener('click', function(e) {
                            if (e.target === modal) {
                                document.body.removeChild(modal);
                            }
                        });

                        // Close on escape key
                        document.addEventListener('keydown', function(e) {
                            if (e.key === 'Escape') {
                                if (document.body.contains(modal)) {
                                    document.body.removeChild(modal);
                                }
                            }
                        });
                    };

                    // Optional: Add hover effects for activity details that are truncated
                    const activityCells = document.querySelectorAll('.group');
                    if (activityCells) {
                        activityCells.forEach(cell => {
                            cell.addEventListener('mouseenter', function() {
                                const tooltip = this.querySelector('.hidden');
                                if (tooltip) {
                                    tooltip.classList.remove('hidden');
                                    tooltip.classList.add('block');
                                }
                            });

                            cell.addEventListener('mouseleave', function() {
                                const tooltip = this.querySelector('.group-hover\\:block');
                                if (tooltip) {
                                    tooltip.classList.add('hidden');
                                    tooltip.classList.remove('block');
                                }
                            });
                        });
                    }
                });

                //export pdf function

                document.getElementById('downloadButton').addEventListener('click', function() {
                    // Get trainee name directly from the page
                    const traineeNameElement = document.querySelector('.text-primary-700');
                    const traineeName = traineeNameElement ?
                        traineeNameElement.textContent.replace(/\s+/g, '_') :
                        'Trainee_DTR';

                    // Get selected year and month
                    const yearSelect = document.getElementById('yearSelect');
                    const monthSelect = document.getElementById('monthSelect');
                    const selectedYear = yearSelect.options[yearSelect.selectedIndex].text;
                    const selectedMonth = monthSelect.options[monthSelect.selectedIndex].text;
                    const dateStr = `${selectedMonth}_${selectedYear}`; // Format: Month_Year

                    // Initialize jsPDF
                    const {
                        jsPDF
                    } = window.jspdf;
                    const doc = new jsPDF('p', 'mm', 'a4');

                    // Get the DTR table
                    const dtrTable = document.getElementById('dtrTable');

                    // Hide buttons in the table
                    const buttons = dtrTable.querySelectorAll('button');
                    buttons.forEach(button => button.style.display = 'none');

                    // Capture the table as an image using html2canvas
                    html2canvas(dtrTable, {
                            scale: 2,
                            useCORS: true,
                            logging: false
                        })
                        .then(canvas => {
                            const imgData = canvas.toDataURL('image/png'); // Convert canvas to image
                            const imgWidth = 190; // Width of the image in the PDF
                            const imgHeight = (canvas.height * imgWidth) / canvas.width; // Calculate height

                            // Add the image to the PDF
                            doc.addImage(imgData, 'PNG', 10, 10, imgWidth, imgHeight);

                            // Save the PDF with a dynamic filename
                            doc.save(`${traineeName}_DTR_Report_${dateStr}.pdf`);

                            // Restore the buttons after generating the PDF
                            buttons.forEach(button => button.style.display = '');
                        })
                        .catch(error => {
                            console.error('Error generating PDF:', error);
                            // Restore buttons in case of error
                            buttons.forEach(button => button.style.display = '');
                            alert('Failed to generate PDF. Please try again.');
                        });
                });
            </script>
        </main>
    </div>
</body>

</html>