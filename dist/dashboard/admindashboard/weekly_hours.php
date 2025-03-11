<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
include '../dbcon.php';

// Get the trainee ID from the query string and sanitize it
$trainee_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get filter parameters (defaults to current year if not specified)
$filter_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$filter_month = isset($_GET['month']) ? (int)$_GET['month'] : 0; // 0 means all months

// Fetch trainee details using a prepared statement
$sql = "
    SELECT 
        trainees.id,
        trainees.name, 
        trainees.email,
        trainees.required_hours,
        courses.course_name, 
        agencies.agency_name
    FROM 
        trainees
    LEFT JOIN courses ON trainees.course_id = courses.id
    LEFT JOIN agencies ON trainees.agency_id = agencies.id
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

// Build the query to calculate weekly hours with filtering
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

// Add month filter if specified
if ($filter_month > 0) {
    $weekly_hours_query .= " AND MONTH(date) = ?";
}

$weekly_hours_query .= "
    GROUP BY 
        week_start_date
    ORDER BY 
        week_start_date DESC
";

// Prepare and bind parameters
$stmt = $conn->prepare($weekly_hours_query);
if ($filter_month > 0) {
    $stmt->bind_param("iii", $trainee_id, $filter_year, $filter_month);
} else {
    $stmt->bind_param("ii", $trainee_id, $filter_year);
}

$stmt->execute();
$result = $stmt->get_result();

$weekly_hours_data = [];
$this_week_start = date('Y-m-d', strtotime('monday this week'));
$this_week_hours = 0;
$all_total_hours = 0;

while ($row = $result->fetch_assoc()) {
    // Format the hours to have consistent decimal places
    $row['total_weekly_hours'] = round($row['total_weekly_hours'], 1);
    $weekly_hours_data[] = $row;

    // Add to all total hours
    $all_total_hours += $row['total_weekly_hours'];

    // Check if this is the current week
    if ($row['week_start_date'] == $this_week_start) {
        $this_week_hours = $row['total_weekly_hours'];
    }
}

// Get available years for filter dropdown
$years_query = "
    SELECT DISTINCT YEAR(date) as year
    FROM studentloggeddata
    WHERE trainee_id = ?
    ORDER BY year DESC
";

$stmt = $conn->prepare($years_query);
$stmt->bind_param("i", $trainee_id);
$stmt->execute();
$years_result = $stmt->get_result();
$available_years = [];

while ($year_row = $years_result->fetch_assoc()) {
    $available_years[] = $year_row['year'];
}

// Calculate remaining hours
$remaining_hours = max(0, $trainee['required_hours'] - $all_total_hours);

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Worked Hours - <?php echo htmlspecialchars($trainee['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                        },
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-primary-50 p-6 min-h-screen">
    <?php
    // Sort the weekly_hours_data array by week_start_date in ascending order
    usort($weekly_hours_data, function ($a, $b) {
        return strtotime($a['week_start_date']) - strtotime($b['week_start_date']);
    });
    ?>

    <div class="max-w-4xl mx-auto bg-white p-4 sm:p-6 shadow-xl rounded-xl border border-primary-200">
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row items-center justify-between mb-6">
            <div class="mb-4 sm:mb-0">
                <h2 class="text-xl sm:text-2xl font-bold text-primary-700">
                    <i class="fas fa-clock mr-2"></i> Weekly Worked Hours
                </h2>
                <p class="text-sm sm:text-base text-gray-600">
                    <i class="fas fa-user mr-1"></i> <?php echo htmlspecialchars($trainee['name']); ?> -
                    <?php echo htmlspecialchars($trainee['course_name']); ?> |
                    <?php echo htmlspecialchars($trainee['agency_name']); ?>
                </p>
            </div>
            <div class="w-16 h-1 bg-secondary-500 rounded-full"></div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mb-6">
            <div class="p-4 bg-primary-100 rounded-lg border-l-4 border-primary-600">
                <div class="text-sm text-gray-600">Total Hours Logged</div>
                <div class="text-xl sm:text-2xl font-bold text-primary-700">
                    <?php echo number_format($all_total_hours, 1); ?> <span class="text-sm sm:text-base">hrs</span>
                </div>
            </div>

            <div class="p-4 bg-secondary-100 rounded-lg border-l-4 border-secondary-600">
                <div class="text-sm text-gray-600">Required Hours</div>
                <div class="text-xl sm:text-2xl font-bold text-secondary-700">
                    <?php echo number_format($trainee['required_hours'], 1); ?> <span class="text-sm sm:text-base">hrs</span>
                </div>
            </div>

            <div class="p-4 bg-blue-100 rounded-lg border-l-4 border-blue-600">
                <div class="text-sm text-gray-600">Remaining Hours</div>
                <div class="text-xl sm:text-2xl font-bold text-blue-700">
                    <?php echo number_format($remaining_hours, 1); ?> <span class="text-sm sm:text-base">hrs</span>
                </div>
            </div>
        </div>

        <!-- Weekly Data Table -->
        <div class="bg-white rounded-lg shadow-sm border border-primary-100 overflow-hidden mb-6">
            <div class="p-4 bg-primary-600 text-white font-semibold">
                <div class="flex flex-col sm:flex-row justify-between items-center">
                    <span><i class="fas fa-calendar-week mr-2"></i>Weekly Hours Report</span>
                    <span class="mt-2 sm:mt-0"><?php echo count($weekly_hours_data); ?> entries</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-primary-100 text-primary-800">
                            <th class="px-4 py-3 text-left font-semibold">Week Start Date</th>
                            <th class="px-4 py-3 text-center font-semibold">Week</th>
                            <th class="px-4 py-3 text-center font-semibold">Days Worked</th>
                            <th class="px-4 py-3 text-center font-semibold">Total Hours</th>
                            <th class="px-4 py-3 text-center font-semibold">Daily Average</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-primary-100">
                        <?php if (!empty($weekly_hours_data)) : ?>
                            <?php foreach ($weekly_hours_data as $week) : ?>
                                <?php
                                $is_current_week = ($week['week_start_date'] == $this_week_start);
                                $row_class = $is_current_week ? 'bg-primary-50' : '';
                                $daily_avg = $week['days_worked'] > 0 ?
                                    round($week['total_weekly_hours'] / $week['days_worked'], 1) : 0;
                                ?>
                                <tr class="<?php echo $row_class; ?> hover:bg-primary-50 transition-colors duration-150">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center">
                                            <?php if ($is_current_week): ?>
                                                <span class="inline-block w-2 h-2 rounded-full bg-secondary-500 mr-2"></span>
                                            <?php endif; ?>
                                            <?php
                                            $week_end = date('F j', strtotime($week['week_start_date'] . ' +6 days'));
                                            echo date('F j', strtotime($week['week_start_date'])) . ' - ' . $week_end;
                                            ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-block px-2 py-1 text-xs rounded bg-primary-100 text-primary-800">
                                            Week <?php echo $week['week_number']; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-gray-700"><?php echo $week['days_worked']; ?></span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="font-medium <?php echo $is_current_week ? 'text-secondary-600' : 'text-gray-700'; ?>">
                                            <?php echo number_format($week['total_weekly_hours'], 1); ?>
                                        </span>
                                        <span class="text-xs text-gray-500 ml-1">hrs</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-gray-700"><?php echo number_format($daily_avg, 1); ?></span>
                                        <span class="text-xs text-gray-500 ml-1">hrs/day</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="5" class="text-center py-6 text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <i class="far fa-calendar-times text-2xl mb-2 text-gray-400"></i>
                                        No logs found for this trainee with the selected filters.
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="mb-6">
            <div class="text-sm font-medium text-gray-700 mb-1">Overall Progress</div>
            <div class="h-6 flex items-center mb-1">
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <?php
                    $required_hours = isset($trainee['required_hour']) ? $trainee['required_hour'] : 100;
                    $progress = min(100, ($all_total_hours / max(1, $required_hours)) * 100);
                    ?>
                    <div class="bg-gradient-to-r from-primary-500 to-secondary-500 h-2.5 rounded-full" style="width: <?php echo $progress; ?>%"></div>
                </div>
                <div class="ml-3 text-sm font-medium text-gray-700 w-12 text-right"><?php echo round($progress); ?>%</div>
            </div>
            <div class="text-xs text-gray-500">
                <?php echo number_format($all_total_hours, 1); ?> of <?php echo number_format($required_hours, 0); ?> required hours completed
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap gap-3">
            <a href="view_details.php?id=<?php echo $trainee_id; ?>" class="group flex items-center justify-center py-2.5 px-4 bg-secondary-500 hover:bg-secondary-600 text-white font-medium rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg">
                <i class="fas fa-arrow-left mr-2 transform group-hover:-translate-x-1 transition-transform duration-200"></i>
                Back to Trainee Details
            </a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Chart.js implementation
            const ctx = document.getElementById('weeklyChart').getContext('2d');

            // Prepare data for chart (reverse array to show chronological order)
            const chartData = <?php echo json_encode(array_reverse($weekly_hours_data)); ?>;

            const labels = chartData.map(week => 'Week ' + week.week_number);
            const hours = chartData.map(week => week.total_weekly_hours);

            const chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Weekly Hours',
                        data: hours,
                        backgroundColor: 'rgba(34, 197, 94, 0.6)',
                        borderColor: 'rgba(34, 197, 94, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Weekly Hours Distribution'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.raw.toFixed(1) + ' hours';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Hours'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Week'
                            }
                        }
                    }
                }
            });

            // Export functionality (simplified - just alerts for now)
            document.getElementById('exportPdfBtn').addEventListener('click', function() {
                alert('PDF export would be implemented here');
                // In a real implementation, you would use a library like jsPDF
            });

            document.getElementById('exportCsvBtn').addEventListener('click', function() {
                // Simple CSV export implementation
                let csvContent = "data:text/csv;charset=utf-8,";

                // Header row
                csvContent += "Week Start Date,Week Number,Days Worked,Total Hours,Daily Average\n";

                // Data rows
                <?php foreach ($weekly_hours_data as $week): ?>
                    <?php
                    $daily_avg = $week['days_worked'] > 0 ? round($week['total_weekly_hours'] / $week['days_worked'], 1) : 0;
                    $week_end = date('Y-m-d', strtotime($week['week_start_date'] . ' +6 days'));
                    ?>
                    csvContent += "<?php echo $week['week_start_date']; ?> to <?php echo $week_end; ?>,";
                    csvContent += "<?php echo $week['week_number']; ?>,";
                    csvContent += "<?php echo $week['days_worked']; ?>,";
                    csvContent += "<?php echo number_format($week['total_weekly_hours'], 1); ?>,";
                    csvContent += "<?php echo number_format($daily_avg, 1); ?>\n";
                <?php endforeach; ?>

                // Create download link
                const encodedUri = encodeURI(csvContent);
                const link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                link.setAttribute("download", "weekly_report_<?php echo $trainee['name']; ?>.csv");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        });
    </script>
</body>

</html>