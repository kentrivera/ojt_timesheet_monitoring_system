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

// Define how many results you want per page
$limit = 5;

// Get the current page number from the URL, if not present set default to 1
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Fetch the coordinators along with department_name using JOIN
$sql = "SELECT coordinators.id, coordinators.name, coordinators.email, coordinators.image, coordinators.phone_number, 
               CASE 
                   WHEN coordinators.id = 1 THEN 'University Wide Coordinator' 
                   ELSE department.department_name 
               END AS department_name 
        FROM coordinators 
        LEFT JOIN department ON coordinators.department_id = department.department_id 
        LIMIT $start, $limit";
$result = $conn->query($sql);

// Fetch the total number of coordinators
$totalResults = $conn->query("SELECT COUNT(id) AS total FROM coordinators")->fetch_assoc()['total'];
$totalPages = ceil($totalResults / $limit);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Add SweetAlert CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>View Coordinators</title>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: '#16a34a', // Green
                            light: '#22c55e',
                            dark: '#15803d'
                        },
                        secondary: {
                            DEFAULT: '#f97316', // Orange
                            light: '#fb923c',
                            dark: '#ea580c'
                        }
                    },
                    fontFamily: {
                        poppins: ['Poppins', 'sans-serif']
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 font-poppins">
    <?php include 'nav/top-nav.php'; ?>
    <div class="flex flex-col min-h-screen">
        <!-- Sidebar Spacer for Wide Screens -->
        <div class="hidden lg:block lg:w-64"></div>

        <main class="flex-1 p-4 md:p-6 lg:ml-64">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-4 md:mb-0 relative after:content-[''] after:absolute after:-bottom-2 after:left-0 after:w-16 after:h-1 after:bg-secondary">
                    Coordinators Directory
                </h1>
                <a href="add_coordinators.php" class="inline-flex items-center px-4 py-2 bg-secondary text-white rounded-lg hover:bg-secondary-dark transition shadow-md">
                    <i class="fas fa-plus-circle mr-2"></i>
                    <span>Add New Coordinator</span>
                </a>
            </div>

            <?php if (isset($_GET['message'])): ?>
                <div class="bg-green-100 border-l-4 border-primary text-primary-dark p-4 mb-6 rounded shadow" role="alert">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <p><?php echo htmlspecialchars($_GET['message']); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // Check if coordinator_id is 1
                        $departmentName = ($row['id'] == 1) ? 'University Wide Coordinator' : $row['department_name'];
                ?>
                        <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-100 hover:shadow-lg transition transform hover:-translate-y-1">
                            <div class="relative p-4">
                                <div class="absolute top-4 right-4">
                                    <button class="text-gray-500 hover:text-gray-800 focus:outline-none" onclick="toggleMenu('<?php echo $row['id']; ?>')">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div id="menu-<?php echo $row['id']; ?>" class="hidden absolute z-20 right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100">
                                        <div class="py-1">
                                            <a href="edit_coordinator.php?id=<?php echo $row['id']; ?>" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                <i class="fas fa-edit w-5 mr-2 text-primary"></i>
                                                <span>Edit</span>
                                            </a>
                                            <a href="view_coordinators_profile.php?id=<?php echo $row['id']; ?>" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                <i class="fas fa-eye w-5 mr-2 text-primary"></i>
                                                <span>View</span>
                                            </a>
                                            <a href="#" onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo addslashes($row['name']); ?>')" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                <i class="fas fa-trash-alt w-5 mr-2 text-secondary"></i>
                                                <span>Delete</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-col items-center">
                                    <div class="relative">
                                        <div class="w-24 h-24 mb-3 rounded-full overflow-hidden border-4 border-primary-light/20 shadow">
                                            <img src="<?php echo $row['image']; ?>" alt="<?php echo $row['name']; ?>" class="object-cover w-full h-full">
                                        </div>
                                        <?php if (rand(0, 1)): ?>
                                            <div class="absolute bottom-3 right-0 w-4 h-4 bg-primary rounded-full border-2 border-white"></div>
                                        <?php endif; ?>
                                    </div>

                                    <h2 class="text-base font-semibold text-gray-800"><?php echo $row['name']; ?></h2>
                                    <p class="text-sm font-medium text-primary"><?php echo $departmentName; ?></p>

                                    <div class="w-full mt-4 pt-4 border-t border-gray-100">
                                        <div class="flex items-center text-xs text-gray-500 mb-1.5">
                                            <i class="fas fa-envelope mr-2 text-gray-400"></i>
                                            <span class="truncate"><?php echo $row['email']; ?></span>
                                        </div>
                                        <div class="flex items-center text-xs text-gray-500">
                                            <i class="fas fa-phone-alt mr-2 text-gray-400"></i>
                                            <span><?php echo $row['phone_number']; ?></span>
                                        </div>
                                    </div>

                                    <div class="flex justify-center space-x-3 mt-4 w-full">
                                        <a href="#" class="p-2 text-blue-500 hover:bg-blue-50 rounded-full transition">
                                            <i class="fas fa-comment-alt"></i>
                                        </a>
                                        <a href="tel:<?php echo $row['phone_number']; ?>" class="p-2 text-primary hover:bg-primary-light/10 rounded-full transition">
                                            <i class="fas fa-phone"></i>
                                        </a>
                                        <a href="mailto:<?php echo $row['email']; ?>" class="p-2 text-secondary hover:bg-secondary-light/10 rounded-full transition">
                                            <i class="fas fa-envelope"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                <?php
                    }
                } else {
                    echo '<div class="col-span-full text-center py-12 bg-white rounded-lg shadow-md">';
                    echo '<i class="fas fa-user-slash text-5xl text-gray-300 mb-4"></i>';
                    echo '<p class="text-gray-500">No coordinators found in the system.</p>';
                    echo '<a href="add_coordinators.php" class="inline-block mt-4 px-4 py-2 bg-secondary text-white rounded-lg hover:bg-secondary-dark transition">Add Your First Coordinator</a>';
                    echo '</div>';
                }
                ?>
            </div>

            <!-- Pagination moved to the end -->
            <?php if ($totalPages > 1): ?>
                <div class="flex justify-center mt-8">
                    <div class="inline-flex rounded-md shadow">
                        <?php if ($page > 1): ?>
                            <a href="?page=1" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-l-md hover:bg-gray-50">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                            <a href="?page=<?php echo $page - 1; ?>" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border-t border-b border-gray-200 hover:bg-gray-50">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php
                        $startPage = max(1, min($page - 1, $totalPages - 2));
                        $endPage = min($totalPages, max($page + 1, 3));

                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <a href="?page=<?php echo $i; ?>" class="px-3 py-2 text-sm font-medium <?php echo $i == $page ? 'text-white bg-primary border-primary' : 'text-gray-700 bg-white border-gray-200 hover:bg-gray-50'; ?> border-t border-b">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border-t border-b border-gray-200 hover:bg-gray-50">
                                <i class="fas fa-angle-right"></i>
                            </a>
                            <a href="?page=<?php echo $totalPages; ?>" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-r-md hover:bg-gray-50">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="text-center text-xs text-gray-500 mt-6">
                Showing <?php echo ($start + 1); ?> to <?php echo min($start + $limit, $totalResults); ?> of <?php echo $totalResults; ?> coordinators
            </div>
        </main>
    </div>

    <script>
        // Function to toggle coordinator action menu
        function toggleMenu(id) {
            const menu = document.getElementById('menu-' + id);
            // Close all other menus first
            document.querySelectorAll('[id^="menu-"]').forEach(element => {
                if (element.id !== 'menu-' + id) {
                    element.classList.add('hidden');
                }
            });
            // Toggle the clicked menu
            menu.classList.toggle('hidden');
        }

        // Close menus when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.fa-ellipsis-v')) {
                document.querySelectorAll('[id^="menu-"]').forEach(element => {
                    element.classList.add('hidden');
                });
            }
        });

        // Function to confirm deletion using SweetAlert
        function confirmDelete(id, name) {
            Swal.fire({
                title: 'Are you sure?',
                text: `You are about to delete coordinator: ${name}`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#16a34a', // Green button for confirm
                cancelButtonColor: '#f97316', // Orange button for cancel
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'delete_coordinator.php?id=' + id;
                }
            });
        }

        // Show SweetAlert for success messages from URL parameters
        <?php if (isset($_GET['message'])): ?>
            Swal.fire({
                title: 'Success!',
                text: '<?php echo htmlspecialchars($_GET['message']); ?>',
                icon: 'success',
                confirmButtonColor: '#16a34a'
            });
        <?php endif; ?>
    </script>
</body>

</html>