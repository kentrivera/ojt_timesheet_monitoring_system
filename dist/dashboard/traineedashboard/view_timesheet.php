<?php
include '../dbcon.php'; // Include database connection
session_start();

// Ensure user is logged in
if (!isset($_SESSION['id'])) {
    header('Location: ../login.php');
    exit();
}

$trainee_id = $_SESSION['id'];

// Fetch timesheet records for the logged-in trainee
$stmt = $conn->prepare("SELECT * FROM studentloggeddata WHERE trainee_id = ? ORDER BY date DESC");
$stmt->bind_param("i", $trainee_id);
$stmt->execute();
$result = $stmt->get_result();

// Function to format time as HH:MM
function formatTime($time)
{
    if (empty($time)) {
        return '-';
    }
    return date('H:i', strtotime($time)); // Format as HH:MM
}

// Function to calculate hours between two time strings
function calculateHours($timeIn, $timeOut)
{
    if (empty($timeIn) || empty($timeOut)) {
        return 0;
    }

    $in = strtotime($timeIn);
    $out = strtotime($timeOut);

    if ($out < $in) {
        return 0; // Handle case where time out is before time in
    }

    return round(($out - $in) / 3600, 1); // Convert seconds to hours with 1 decimal
}

// Calculate total hours and days logged for summary cards
$totalHours = 0;
$daysLogged = $result->num_rows;

// Clone the result to use it twice
$tempResult = $result;
while ($row = $tempResult->fetch_assoc()) {
    $amHours = calculateHours($row['first_time_in'], $row['first_timeout']);
    $pmHours = calculateHours($row['second_time_in'], $row['second_timeout']);
    $totalHours += ($amHours + $pmHours);
}

// Reset the result pointer
$result->data_seek(0);

// Define filter values at the top
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$selectedYearMonth = $selectedYear . '-' . $selectedMonth;

// Get available years from database for dropdowns
$yearsQuery = "SELECT DISTINCT YEAR(date) as year FROM studentloggeddata WHERE trainee_id = ? ORDER BY year DESC";
$stmtYears = $conn->prepare($yearsQuery);
$stmtYears->bind_param("i", $trainee_id); // Use $trainee_id instead of $_SESSION['trainee_id']
$stmtYears->execute();
$availableYears = $stmtYears->get_result()->fetch_all(MYSQLI_ASSOC);

// Get available months from database for dropdowns
$monthsQuery = "SELECT DISTINCT MONTH(date) as month FROM studentloggeddata WHERE trainee_id = ? AND YEAR(date) = ? ORDER BY month ASC";
$stmtMonths = $conn->prepare($monthsQuery);
$stmtMonths->bind_param("ii", $trainee_id, $selectedYear); // Use $trainee_id instead of $_SESSION['trainee_id']
$stmtMonths->execute();
$availableMonths = $stmtMonths->get_result()->fetch_all(MYSQLI_ASSOC);

// Build WHERE clause for filtering
$whereClause = "WHERE trainee_id = ? AND YEAR(date) = ? AND MONTH(date) = ?";

// Prepare and execute the filtered query
$query = "SELECT * FROM studentloggeddata $whereClause ORDER BY date DESC";
$stmt = $conn->prepare($query);

// Store the integer value of the selected month in a variable
$selectedMonthInt = intval($selectedMonth);

// Bind parameters using variables
$stmt->bind_param("iii", $trainee_id, $selectedYear, $selectedMonthInt);
$stmt->execute();
$result = $stmt->get_result();

// Calculate summary statistics based on filtered results
$totalHours = 0;
$daysLogged = 0;
$tempResult = $result->fetch_all(MYSQLI_ASSOC);
foreach ($tempResult as $row) {
    $amHours = calculateHours($row['first_time_in'], $row['first_timeout']);
    $pmHours = calculateHours($row['second_time_in'], $row['second_timeout']);
    $totalHours += ($amHours + $pmHours);
    $daysLogged++;
}
$result->data_seek(0); // Reset result pointer
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Timesheet</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../style.css">
    <style>
        .glassmorphism {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.2);
        }

        body {
            background: linear-gradient(135deg, #2ecc71, #3498db);
            min-height: 100vh;
        }

        .card-hover:hover {
            transform: translateY(-3px);
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px 0 rgba(31, 38, 135, 0.4);
        }

        /* Updated card visibility with white blur background */
        .stats-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.6);
        }

        /* Improved visibility for table content */
        tbody tr {
            background-color: rgba(255, 255, 255, 0.25);
        }

        tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.4);
        }

        /* Modal styling */
        .modal-content {
            max-height: 80vh;
            overflow-y: auto;
        }

        /* Text color adjustments for better contrast with white background */
        .text-content {
            color: #1a3b5c;
        }
    </style>
</head>

<body>
    <!-- Include Top Navigation -->
    <div>
        <?php include 'nav/top-nav.php'; ?>
    </div>
    <div class="container mx-auto p-2 sm:p-4 lg:p-6 mt-14">
        <!-- Timesheet Header -->
        <div class="glassmorphism rounded-xl p-2 sm:p-3 mb-3 mt-2">
            <h1 class="text-base sm:text-lg md:text-xl font-bold text-gray-800 mb-1 flex items-center">
                <i class="fas fa-calendar-check mr-2"></i>Your Timesheet
            </h1>
            <p class="text-gray-600 text-xs sm:text-sm">Track your work hours and activities</p>
        </div>

        <!-- Filter Section -->
        <div class="mb-3 flex flex-col sm:flex-row gap-2">
            <!-- Month Selection -->
            <div class="bg-white/80 backdrop-blur-sm p-2 sm:p-3 rounded-lg border border-gray-200 shadow-sm">
                <div class="flex flex-col h-full justify-between">
                    <!-- Current Period Label -->
                    <span class="block text-xs text-gray-500 mb-1">Current Period</span>

                    <!-- Calendar Icon and Date -->
                    <div class="flex items-center gap-1 mb-2">
                        <i class="fas fa-calendar-alt text-orange-500 text-sm"></i>
                        <span class="text-xs sm:text-sm font-bold">
                            <?php echo date('F Y', strtotime($selectedYearMonth . '-01')); ?>
                        </span>
                    </div>

                    <!-- Year and Month Dropdowns -->
                    <div class="flex flex-wrap w-full items-center gap-2">
                        <!-- Year Dropdown -->
                        <select id="yearSelect" class="text-xs border border-gray-300 rounded px-1 sm:px-2 py-1 focus:outline-none focus:ring-1 focus:ring-orange-500">
                            <?php foreach ($availableYears as $year): ?>
                                <option value="<?php echo $year['year']; ?>" <?php echo $year['year'] == $selectedYear ? 'selected' : ''; ?>>
                                    <?php echo $year['year']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- Month Dropdown -->
                        <select id="monthSelect" class="text-xs border border-gray-300 rounded px-1 sm:px-2 py-1 focus:outline-none focus:ring-1 focus:ring-orange-500">
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

            <!-- Weekly Report Card -->
            <div class="bg-white/80 backdrop-blur-sm p-2 sm:p-3 rounded-lg border border-gray-200 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-bold text-sm sm:text-base mb-1">Weekly Report</h3>
                        <p class="text-gray-600 text-xs sm:text-sm mb-2">Week of <?php echo date('F j, Y', strtotime('monday this week')); ?></p>
                    </div>
                    <div class="text-gray-800 bg-white/50 p-1 sm:p-2 rounded-full">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
                <a href="weekly_hours.php?id=<?php echo $trainee_id; ?>" class="mt-2 inline-block px-2 sm:px-3 py-1 bg-gradient-to-r from-orange-400 to-orange-100 hover:from-orange-500 hover:to-orange-200 rounded-lg text-xs text-white font-semibold transition">
                    View Report <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-3 gap-2 sm:gap-3 mb-4">
            <div class="glassmorphism stats-card rounded-lg p-2 sm:p-3 text-center">
                <i class="fas fa-clock text-lg sm:text-xl md:text-2xl mb-1 text-green-600"></i>
                <h3 class="font-bold text-xs sm:text-sm md:text-base">Total Hours</h3>
                <p class="text-sm sm:text-base md:text-lg font-bold"><?= number_format($totalHours, 1) ?></p>
            </div>

            <div class="glassmorphism stats-card rounded-lg p-2 sm:p-3 text-center">
                <i class="fas fa-calendar-week text-lg sm:text-xl md:text-2xl mb-1 text-blue-600"></i>
                <h3 class="font-bold text-xs sm:text-sm md:text-base">Days Logged</h3>
                <p class="text-sm sm:text-base md:text-lg font-bold"><?= $daysLogged ?></p>
            </div>

            <div class="glassmorphism stats-card rounded-lg p-2 sm:p-3 text-center">
                <i class="fas fa-chart-line text-lg sm:text-xl md:text-2xl mb-1 text-purple-600"></i>
                <h3 class="font-bold text-xs sm:text-sm md:text-base">Avg Hours/Day</h3>
                <p class="text-sm sm:text-base md:text-lg font-bold"><?= $daysLogged > 0 ? number_format($totalHours / $daysLogged, 1) : '0.0' ?></p>
            </div>
        </div>

        <?php
        // Fetch all rows into an array
        $rows = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }

        // Sort the rows array by date in ascending order
        usort($rows, function ($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });

        $rowTotal = 0; // Initialize total hours
        ?>

        <!-- Timesheet Table -->
        <div class="glassmorphism rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gradient-to-r from-green-600 to-blue-600 text-white">
                        <tr>
                            <th class="px-2 sm:px-3 py-1 sm:py-2 text-left text-xs sm:text-sm"><i class="far fa-calendar-alt mr-1"></i>Date</th>
                            <th class="px-2 sm:px-3 py-1 sm:py-2 text-left text-xs sm:text-sm"><i class="fas fa-sign-in-alt mr-1"></i>In (AM)</th>
                            <th class="px-2 sm:px-3 py-1 sm:py-2 text-left text-xs sm:text-sm"><i class="fas fa-sign-out-alt mr-1"></i>Out (AM)</th>
                            <th class="px-2 sm:px-3 py-1 sm:py-2 text-left text-xs sm:text-sm"><i class="fas fa-sign-in-alt mr-1"></i>In (PM)</th>
                            <th class="px-2 sm:px-3 py-1 sm:py-2 text-left text-xs sm:text-sm"><i class="fas fa-sign-out-alt mr-1"></i>Out (PM)</th>
                            <th class="px-2 sm:px-3 py-1 sm:py-2 text-left text-xs sm:text-sm"><i class="fas fa-clock mr-1"></i>Hours</th>
                            <th class="px-2 sm:px-3 py-1 sm:py-2 text-left text-xs sm:text-sm"><i class="fas fa-tasks mr-1"></i>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (!empty($rows)) : ?>
                            <?php foreach ($rows as $row) : ?>
                                <?php
                                // Calculate hours for this day
                                $amHours = calculateHours($row['first_time_in'], $row['first_timeout']);
                                $pmHours = calculateHours($row['second_time_in'], $row['second_timeout']);
                                $dailyHours = $amHours + $pmHours;
                                $rowTotal += $dailyHours;
                                ?>
                                <tr>
                                    <td class="px-2 sm:px-3 py-1 sm:py-2 text-gray-800 font-medium text-xs sm:text-sm"><?= htmlspecialchars($row['date']) ?></td>
                                    <td class="px-2 sm:px-3 py-1 sm:py-2 text-gray-800 text-xs sm:text-sm"><?= formatTime($row['first_time_in']) ?></td>
                                    <td class="px-2 sm:px-3 py-1 sm:py-2 text-gray-800 text-xs sm:text-sm"><?= formatTime($row['first_timeout']) ?></td>
                                    <td class="px-2 sm:px-3 py-1 sm:py-2 text-gray-800 text-xs sm:text-sm"><?= formatTime($row['second_time_in']) ?></td>
                                    <td class="px-2 sm:px-3 py-1 sm:py-2 text-gray-800 text-xs sm:text-sm"><?= formatTime($row['second_timeout']) ?></td>
                                    <td class="px-2 sm:px-3 py-1 sm:py-2 text-gray-800 font-semibold text-xs sm:text-sm"><?= number_format($dailyHours, 1) ?></td>
                                    <td class="px-2 sm:px-3 py-1 sm:py-2">
                                        <button onclick="openActivityModal(
                                    '<?= addslashes($row['first_activity_details'] ?? 'No activity details available') ?>',
                                    '<?= addslashes($row['second_activity_details'] ?? 'No activity details available') ?>',
                                    '<?= $row['first_image'] ?? '' ?>',
                                    '<?= $row['second_image'] ?? '' ?>',
                                    '<?= $row['first_timeout_image'] ?? '' ?>',
                                    '<?= $row['second_timeout_image'] ?? '' ?>'
                                )"
                                            class="bg-gradient-to-r from-orange-500 to-orange-600 text-white px-2 py-1 rounded-lg hover:from-orange-600 hover:to-orange-700 transition-colors flex items-center space-x-1 text-xs">
                                            <i class="fas fa-eye"></i>
                                            <span class="hidden sm:inline ml-1">Details</span>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="7" class="px-3 py-4 text-center text-gray-800">No records found for this period.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="bg-gradient-to-r from-green-700 to-blue-700 text-white">
                        <tr>
                            <td colspan="5" class="px-3 py-2 text-right font-bold text-xs sm:text-sm">Total Hours:</td>
                            <td class="px-3 py-2 font-bold text-xs sm:text-sm"><?= number_format($rowTotal, 1) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Activity Details Modal with White Blur Background -->
    <div id="activityModal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black bg-opacity-70">
        <div class="glassmorphism rounded-xl shadow-lg w-11/12 md:w-3/4 lg:w-2/5 max-h-[90vh] p-4 text-content modal-content">
            <div class="flex justify-between items-center mb-3">
                <h2 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-clipboard-list mr-2"></i>Activity Details
                </h2>
                <button onclick="closeActivityModal()" class="text-gray-800 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="space-y-4 overflow-y-auto">
                <div class="bg-white bg-opacity-50 p-3 rounded-lg">
                    <h3 class="text-base font-semibold text-green-700 flex items-center">
                        <i class="fas fa-sun mr-2"></i>Morning Activity
                    </h3>
                    <p id="amActivity" class="mt-2 text-sm text-gray-800"></p>
                </div>

                <div class="bg-white bg-opacity-50 p-3 rounded-lg">
                    <h3 class="text-base font-semibold text-blue-700 flex items-center">
                        <i class="fas fa-moon mr-2"></i>Afternoon Activity
                    </h3>
                    <p id="pmActivity" class="mt-2 text-sm text-gray-800"></p>
                </div>

                <div>
                    <h3 class="text-base font-semibold text-green-700 flex items-center mb-2">
                        <i class="fas fa-images mr-2"></i>Morning Images
                    </h3>
                    <div id="amImages" class="grid grid-cols-1 sm:grid-cols-2 gap-3"></div>
                </div>

                <div>
                    <h3 class="text-base font-semibold text-blue-700 flex items-center mb-2">
                        <i class="fas fa-images mr-2"></i>Afternoon Images
                    </h3>
                    <div id="pmImages" class="grid grid-cols-1 sm:grid-cols-2 gap-3"></div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Script for handling the activity modal in the timesheet viewer
        document.addEventListener('DOMContentLoaded', function() {
            // Get references to the select elements
            const yearSelect = document.getElementById('yearSelect');
            const monthSelect = document.getElementById('monthSelect');

            // Function to update the URL with new year/month parameters
            function updateDateFilter() {
                const selectedYear = yearSelect.value;
                const selectedMonth = monthSelect.value;

                // Create the new URL with updated parameters
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('year', selectedYear);
                currentUrl.searchParams.set('month', selectedMonth);

                // Redirect to the new URL
                window.location.href = currentUrl.toString();
            }

            // Add event listeners to both dropdowns
            yearSelect.addEventListener('change', function() {
                // When year changes, update the filter
                updateDateFilter();
            });

            monthSelect.addEventListener('change', function() {
                // When month changes, update the filter
                updateDateFilter();
            });

            // Optional: Function to initialize any default values if needed
            function initializeFilter() {
                // This could be used to set default values or perform initial checks
                console.log('Date filter initialized with year:', yearSelect.value, 'and month:', monthSelect.value);
            }

            // Initialize the filter when page loads
            initializeFilter();
        });
        // Function to open the activity modal and display details
        function openActivityModal(amActivity, pmActivity, amInImage, pmInImage, amOutImage, pmOutImage) {
            // Set the activity details text
            document.getElementById('amActivity').textContent = amActivity || 'No morning activity details available';
            document.getElementById('pmActivity').textContent = pmActivity || 'No afternoon activity details available';

            // Clear previous images
            document.getElementById('amImages').innerHTML = '';
            document.getElementById('pmImages').innerHTML = '';

            // Add morning images if available
            const amImagesContainer = document.getElementById('amImages');
            if (amInImage) {
                addImageToContainer(amImagesContainer, amInImage, 'Check-in Image');
            }
            if (amOutImage) {
                addImageToContainer(amImagesContainer, amOutImage, 'Check-out Image');
            }
            if (!amInImage && !amOutImage) {
                amImagesContainer.innerHTML = '<p class="text-white opacity-70">No images available</p>';
            }

            // Add afternoon images if available
            const pmImagesContainer = document.getElementById('pmImages');
            if (pmInImage) {
                addImageToContainer(pmImagesContainer, pmInImage, 'Check-in Image');
            }
            if (pmOutImage) {
                addImageToContainer(pmImagesContainer, pmOutImage, 'Check-out Image');
            }
            if (!pmInImage && !pmOutImage) {
                pmImagesContainer.innerHTML = '<p class="text-white opacity-70">No images available</p>';
            }

            // Show the modal
            document.getElementById('activityModal').classList.remove('hidden');

            // Prevent background scrolling when modal is open
            document.body.style.overflow = 'hidden';
        }

        // Helper function to add an image to a container
        function addImageToContainer(container, imagePath, altText) {
            if (!imagePath) return;

            const imageWrapper = document.createElement('div');
            imageWrapper.className = 'bg-white bg-opacity-10 p-2 rounded-lg';

            const image = document.createElement('img');
            image.src = '../uploads/' + imagePath; // Assuming images are in an uploads directory
            image.alt = altText;
            image.className = 'w-full h-auto rounded object-cover';
            image.onerror = function() {
                this.onerror = null;
                this.src = '../assets/img/no-image.png'; // Fallback image
                this.alt = 'Image not available';
            };

            const caption = document.createElement('p');
            caption.className = 'text-center text-sm mt-1 text-white opacity-80';
            caption.textContent = altText;

            imageWrapper.appendChild(image);
            imageWrapper.appendChild(caption);
            container.appendChild(imageWrapper);
        }

        // Function to close the activity modal
        function closeActivityModal() {
            document.getElementById('activityModal').classList.add('hidden');

            // Re-enable scrolling
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside of it (optional)
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('activityModal');

            // Close when clicking outside the modal content
            modal.addEventListener('click', function(event) {
                if (event.target === modal) {
                    closeActivityModal();
                }
            });

            // Close when pressing Escape key
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeActivityModal();
                }
            });
        });
    </script>
</body>

</html>