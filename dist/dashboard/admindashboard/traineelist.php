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

// Fetch options for dropdowns
$courses = $conn->query("SELECT DISTINCT course_name FROM courses");
$agencies = $conn->query("SELECT DISTINCT agency_name FROM agencies");
$school_years = $conn->query("SELECT DISTINCT school_year FROM school_years");

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Get filter values from dropdowns
$filterCourse = isset($_GET['course']) ? $_GET['course'] : '';
$filterAgency = isset($_GET['agency']) ? $_GET['agency'] : '';
$filterYear = isset($_GET['school_year']) ? $_GET['school_year'] : '';

// Build SQL filter conditions
$conditions = [];
if (!empty($filterCourse)) {
    $conditions[] = "courses.course_name = '" . $conn->real_escape_string($filterCourse) . "'";
}
if (!empty($filterAgency)) {
    $conditions[] = "agencies.agency_name = '" . $conn->real_escape_string($filterAgency) . "'";
}
if (!empty($filterYear)) {
    $conditions[] = "school_years.school_year = '" . $conn->real_escape_string($filterYear) . "'";
}

// Combine conditions
$whereClause = '';
if (count($conditions) > 0) {
    $whereClause = 'WHERE ' . implode(' AND ', $conditions);
}

// Fetch trainees with course, agency, and school year names
$sql = "
    SELECT 
        trainees.id, 
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
    $whereClause
    LIMIT $start, $limit
";
$result = $conn->query($sql);

// Fetch total count for pagination
$totalResults = $conn->query("
    SELECT COUNT(trainees.id) AS total 
    FROM 
        trainees
    LEFT JOIN courses ON trainees.course_id = courses.id
    LEFT JOIN agencies ON trainees.agency_id = agencies.id
    LEFT JOIN school_years ON trainees.school_year_id = school_years.id
    $whereClause
")->fetch_assoc()['total'];
$totalPages = ceil($totalResults / $limit);

// Fetch departments for the dropdown
$departments = $conn->query("SELECT * FROM department");

// Delete trainees SweetAlert
if (isset($_SESSION['alert'])) {
    $alert = $_SESSION['alert'];
    $icon = $alert['type'];
    $title = $alert['title'];
    $message = $alert['message'];
    $buttonColor = ($icon === 'success') ? '#10b981' : '#ef4444';

    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '$icon',
                title: '$title',
                text: '$message',
                confirmButtonColor: '$buttonColor'
            });
        });
    </script>";

    unset($_SESSION['alert']); // Clear alert after showing
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Trainee Management</title>
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

<body class="bg-gray-50">
    <!-- Main Layout -->
    <div class="flex flex-col min-h-screen">
        <!-- Top Navigation -->
        <?php include 'nav/top-nav.php'; ?>

        <!-- Content Wrapper -->
        <div class="flex flex-1">
            <!-- Side Navigation -->
            <div class="hidden w-64 min-h-screen text-white bg-primary-800 md:block">
            </div>

            <!-- Main Content -->
            <div class="flex-1 p-4 md:p-6 overflow-x-hidden">
                <!-- Header Section -->
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
                    <h1 class="text-2xl md:text-3xl font-bold text-primary-700">Trainee Management</h1>

                    <!-- Search Trainee Field -->
                    <form method="GET" class="w-full md:w-auto">
                        <div class="flex items-center gap-2">
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input
                                    type="text"
                                    name="search_trainee"
                                    id="searchTraineeInput"
                                    value="<?php echo htmlspecialchars($searchTrainee ?? ''); ?>"
                                    placeholder="Search by name or ID"
                                    required
                                    class="w-full pl-10 pr-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            <button
                                type="submit"
                                id="searchTraineeBtn"
                                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition duration-200">
                                Search
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Search Results Container -->
                <div id="traineeCardContainer" class="mb-6"></div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row justify-between gap-4 mb-6">
                    <!-- Add Trainee Button -->
                    <a href="add_trainee.php"
                        class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition duration-200">
                        <i class="fas fa-plus mr-2"></i>Add Trainee
                    </a>

                    <!-- Toggle Filters Button (Mobile) -->
                    <button
                        type="button"
                        onclick="toggleFilters()"
                        class="sm:hidden inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-secondary-700 bg-secondary-100 rounded-lg hover:bg-secondary-200 focus:outline-none focus:ring-2 focus:ring-secondary-500 focus:ring-offset-2 transition duration-200">
                        <i class="fas fa-filter mr-2"></i>Toggle Filters
                    </button>
                </div>

                <!-- Filters Section -->
                <form method="GET" id="filtersContainer" class="mb-6 p-4 bg-white rounded-lg shadow-sm hidden sm:block border border-gray-100">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Filter by Course -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Filter by Course</label>
                            <select name="course" class="w-full px-3 py-2 text-sm bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500">
                                <option value="">All Courses</option>
                                <?php while ($course = $courses->fetch_assoc()): ?>
                                    <option
                                        value="<?php echo $course['course_name']; ?>"
                                        <?php echo $filterCourse == $course['course_name'] ? 'selected' : ''; ?>>
                                        <?php echo $course['course_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Filter by Agency -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Filter by Agency</label>
                            <select name="agency" class="w-full px-3 py-2 text-sm bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500">
                                <option value="">All Agencies</option>
                                <?php while ($agency = $agencies->fetch_assoc()): ?>
                                    <option
                                        value="<?php echo $agency['agency_name']; ?>"
                                        <?php echo $filterAgency == $agency['agency_name'] ? 'selected' : ''; ?>>
                                        <?php echo $agency['agency_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Filter by School Year -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Filter by School Year</label>
                            <select name="school_year" class="w-full px-3 py-2 text-sm bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500">
                                <option value="">All School Years</option>
                                <?php while ($school_year = $school_years->fetch_assoc()): ?>
                                    <option
                                        value="<?php echo $school_year['school_year']; ?>"
                                        <?php echo $filterYear == $school_year['school_year'] ? 'selected' : ''; ?>>
                                        <?php echo $school_year['school_year']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-end">
                            <button type="submit" class="w-full px-4 py-2 text-sm font-medium text-white bg-secondary-500 rounded-lg hover:bg-secondary-600 focus:outline-none focus:ring-2 focus:ring-secondary-500 focus:ring-offset-2 transition duration-200">
                                <i class="fas fa-filter mr-2"></i>Apply Filters
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Trainee List - List View -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-100">
                    <!-- Table Header -->
                    <div class="hidden md:grid md:grid-cols-12 gap-4 p-4 bg-gray-50 border-b border-gray-200 text-xs font-medium text-gray-600 uppercase">
                        <div class="md:col-span-1"></div> <!-- Image column -->
                        <div class="md:col-span-2">Name</div>
                        <div class="md:col-span-3">Course</div>
                        <div class="md:col-span-2">Agency</div>
                        <div class="md:col-span-2">School Year</div>
                        <div class="md:col-span-2 text-right">Actions</div>
                    </div>

                    <!-- Table Body -->
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <div class="border-b border-gray-200 hover:bg-gray-50 transition duration-150">
                                <!-- Mobile View -->
                                <div class="block md:hidden p-4">
                                    <div class="flex items-center space-x-3">
                                        <img src="<?php echo $row['image']; ?>"
                                            alt="<?php echo $row['name']; ?>"
                                            class="w-12 h-12 rounded-full object-cover border-2 border-primary-200">
                                        <div>
                                            <h3 class="font-medium text-gray-900"><?php echo $row['name']; ?></h3>
                                            <p class="text-xs text-gray-600">Course: <?php echo $row['course_name']; ?></p>
                                            <p class="text-xs text-gray-600">Agency: <?php echo $row['agency_name']; ?></p>
                                            <p class="text-xs text-gray-600">Year: <?php echo $row['school_year']; ?></p>
                                        </div>
                                    </div>
                                    <div class="mt-3 flex space-x-2">
                                        <a href="view_details.php?id=<?php echo $row['id']; ?>"
                                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-primary-700 bg-primary-100 rounded-md hover:bg-primary-200 transition duration-150">
                                            <i class="fas fa-eye mr-1.5"></i>Details
                                        </a>
                                        <a href="view_timesheet.php?id=<?php echo $row['id']; ?>"
                                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-secondary-700 bg-secondary-100 rounded-md hover:bg-secondary-200 transition duration-150">
                                            <i class="fas fa-clock mr-1.5"></i>Timesheet
                                        </a>
                                    </div>
                                </div>

                                <!-- Desktop View -->
                                <div class="hidden md:grid md:grid-cols-12 gap-4 p-4 items-center">
                                    <div class="md:col-span-1">
                                        <?php if (!empty($row['image']) && file_exists($row['image'])): ?>
                                            <img src="<?php echo $row['image']; ?>"
                                                alt="<?php echo $row['name']; ?>"
                                                class="w-10 h-10 rounded-full object-cover border-2 border-primary-200">
                                        <?php else: ?>
                                            <!-- Fallback image or placeholder if the image is missing -->
                                            <img src="path/to/default/image.jpg"
                                                alt="Placeholder"
                                                class="w-10 h-10 rounded-full object-cover border-2 border-primary-200">
                                        <?php endif; ?>
                                    </div>
                                    <div class="md:col-span-2 font-medium text-gray-900">
                                        <?php echo $row['name']; ?>
                                    </div>
                                    <div class="md:col-span-3 text-sm text-gray-600">
                                        <?php echo $row['course_name']; ?>
                                    </div>
                                    <div class="md:col-span-2 text-sm text-gray-600">
                                        <?php echo $row['agency_name']; ?>
                                    </div>
                                    <div class="md:col-span-2 text-sm text-gray-600">
                                        <?php echo $row['school_year']; ?>
                                    </div>
                                    <div class="md:col-span-2 flex justify-end space-x-2">
                                        <a href="view_details.php?id=<?php echo $row['id']; ?>"
                                            class="inline-flex items-center p-1.5 text-xs font-medium text-primary-700 bg-primary-100 rounded-md hover:bg-primary-200 transition duration-150"
                                            title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="view_timesheet.php?id=<?php echo $row['id']; ?>"
                                            class="inline-flex items-center p-1.5 text-xs font-medium text-secondary-700 bg-secondary-100 rounded-md hover:bg-secondary-200 transition duration-150"
                                            title="View Timesheet">
                                            <i class="fas fa-clock"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="p-8 text-center text-gray-500">
                            <i class="fas fa-search text-4xl mb-3 text-gray-300"></i>
                            <p>No trainees found matching your criteria.</p>
                            <p class="text-sm mt-2">Try adjusting your filters or search terms.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="flex justify-center md:justify-end mt-6">
                        <nav class="inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&course=<?php echo urlencode($filterCourse); ?>&agency=<?php echo urlencode($filterAgency); ?>&school_year=<?php echo urlencode($filterYear); ?>"
                                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Previous</span>
                                    <i class="fas fa-chevron-left text-xs"></i>
                                </a>
                            <?php endif; ?>

                            <?php
                            // Calculate range of pages to show
                            $range = 2; // Show 2 pages on each side of current page
                            $start_page = max(1, $page - $range);
                            $end_page = min($totalPages, $page + $range);

                            // Show first page if not in range
                            if ($start_page > 1) {
                                echo '<a href="?page=1&course=' . urlencode($filterCourse) . '&agency=' . urlencode($filterAgency) . '&school_year=' . urlencode($filterYear) . '" 
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    1
                                  </a>';
                                if ($start_page > 2) {
                                    echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                        ...
                                      </span>';
                                }
                            }

                            // Show page links
                            for ($i = $start_page; $i <= $end_page; $i++) {
                                echo '<a href="?page=' . $i . '&course=' . urlencode($filterCourse) . '&agency=' . urlencode($filterAgency) . '&school_year=' . urlencode($filterYear) . '" 
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 ' .
                                    ($i == $page ? 'bg-primary-50 text-primary-600 border-primary-500' : 'bg-white text-gray-700 hover:bg-gray-50') .
                                    ' text-sm font-medium">
                                    ' . $i . '
                                  </a>';
                            }

                            // Show last page if not in range
                            if ($end_page < $totalPages) {
                                if ($end_page < $totalPages - 1) {
                                    echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                        ...
                                      </span>';
                                }
                                echo '<a href="?page=' . $totalPages . '&course=' . urlencode($filterCourse) . '&agency=' . urlencode($filterAgency) . '&school_year=' . urlencode($filterYear) . '" 
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    ' . $totalPages . '
                                  </a>';
                            }
                            ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&course=<?php echo urlencode($filterCourse); ?>&agency=<?php echo urlencode($filterAgency); ?>&school_year=<?php echo urlencode($filterYear); ?>"
                                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Next</span>
                                    <i class="fas fa-chevron-right text-xs"></i>
                                </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Function to toggle filters visibility on mobile
        function toggleFilters() {
            const filtersContainer = document.getElementById('filtersContainer');
            if (filtersContainer.classList.contains('hidden')) {
                filtersContainer.classList.remove('hidden');
            } else {
                filtersContainer.classList.add('hidden');
            }
        }

        // Search functionality
        document.getElementById('searchTraineeBtn').addEventListener('click', function(e) {
            e.preventDefault(); // Prevent the default form submission

            // Validate search input
            const searchInput = document.getElementById('searchTraineeInput');
            if (!searchInput.value.trim()) {
                // Highlight the input field if empty
                searchInput.classList.add('ring-2', 'ring-red-500', 'border-red-500');
                setTimeout(() => {
                    searchInput.classList.remove('ring-2', 'ring-red-500', 'border-red-500');
                }, 2000);
                return;
            }

            // Get the search query value
            let searchQuery = searchInput.value;

            // Prepare the request
            let xhr = new XMLHttpRequest();
            xhr.open('GET', 'search_trainee.php?search_trainee=' + encodeURIComponent(searchQuery), true);

            // Show loading indicator
            document.getElementById('traineeCardContainer').innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-primary-500 text-3xl"></i><p class="mt-2 text-gray-600">Searching...</p></div>';

            // Set up the callback
            xhr.onload = function() {
                if (xhr.status == 200) {
                    // Update the trainee list container with the response
                    document.getElementById('traineeCardContainer').innerHTML = xhr.responseText;
                } else {
                    console.error('Error fetching data');
                    document.getElementById('traineeCardContainer').innerHTML = '<div class="text-center py-8 text-red-500"><i class="fas fa-exclamation-circle text-3xl mb-2"></i><p>Error loading results. Please try again.</p></div>';
                }
            };

            // Error handling
            xhr.onerror = function() {
                document.getElementById('traineeCardContainer').innerHTML = '<div class="text-center py-8 text-red-500"><i class="fas fa-exclamation-circle text-3xl mb-2"></i><p>Network error. Please check your connection and try again.</p></div>';
            };

            // Send the request
            xhr.send();
        });
    </script>
</body>

</html>