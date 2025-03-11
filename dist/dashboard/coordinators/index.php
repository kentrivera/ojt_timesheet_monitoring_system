<?php
// Include the database connection file
include '../dbcon.php';
// Start session to access coordinator information
session_start();

// Check if user is logged in and has the 'Coordinator' role
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Coordinator') {
    session_destroy(); // Destroy session if unauthorized
    header("Location: ../login.php?error=unauthorized_access");
    exit();
}

/**
 * Fetches coordinator profile data.
 */
function fetchCoordinatorProfile($coordinatorId)
{
    global $conn;

    if (!is_numeric($coordinatorId)) {
        return null;
    }

    // Fetch coordinator details
    $sql = "SELECT coordinators.id, coordinators.name, coordinators.user_role, coordinators.image, 
                   coordinators.department_id,
                   CASE 
                       WHEN coordinators.department_id = 1 THEN 'University Wide Coordinator' 
                       ELSE department.department_name 
                   END AS department_name 
            FROM coordinators 
            LEFT JOIN department ON coordinators.department_id = department.department_id 
            WHERE coordinators.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $coordinatorId);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows ? $result->fetch_assoc() : null;
}

$coordinatorId = $_SESSION['id'];
$coordinatorData = fetchCoordinatorProfile($coordinatorId);

if (!$coordinatorData) {
    session_destroy();
    header("Location: ../login.php?error=coordinator_not_found");
    exit();
}

$row = $coordinatorData;

// Correct the image path
$imagePath = '../admindashboard/' . $row['image'];
if (!file_exists($imagePath) || empty($row['image'])) {
    $imagePath = '../admindashboard/uploads/default-profile.jpg'; // Default fallback
}

// Extract necessary data
$coordinator_name = $row['name'];
$coordinator_user_role = $row['user_role'];
$coordinator_department = $row['department_name'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Green to Blue Gradient Backdrop */
        body {
            background: linear-gradient(135deg, #10b981 0%, #3b82f6 100%);
            background-attachment: fixed;
            min-height: 100vh;
        }

        /* Glass Morphism Effect */
        .glass {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .stat-card {
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-icon {
            transition: all 0.3s ease;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1);
        }

        /* Larger profile image that responds to screen size */
        .profile-image {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #10b981;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }

        /* Responsive sizing and centering for small screens */
        @media (max-width: 640px) {
            .profile-image {
                width: 100px;
                height: 100px;
                margin: 0 auto;
            }

            .profile-container {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .profile-info {
                margin-top: 1rem;
                margin-left: 0;
                text-align: center;
                width: 100%;
            }

            .profile-details {
                justify-content: center;
            }

            .login-info {
                margin-top: 1rem;
                width: 100%;
                display: flex;
                justify-content: center;
            }

            .stat-info {
                justify-content: space-between;
                width: 100%;
            }
        }

        /* Even larger on very small screens to maintain prominence */
        @media (max-width: 380px) {
            .profile-image {
                width: 110px;
                height: 110px;
            }
        }
    </style>
</head>

<body class="text-gray-800">
    <!-- Top Navigation -->
    <div class="sticky top-0 z-50 w-full">
        <?php include 'nav/top-nav.php'; ?>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-6 mt-16">
        <div class="max-w-7xl mx-auto">
            <!-- Welcome Section -->
            <div class="glass p-6 sm:p-8 mb-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between profile-container">
                    <div class="flex flex-col sm:flex-row items-center sm:items-start w-full sm:w-auto">
                        <div class="flex justify-center w-full sm:w-auto">
                            <img src="<?php echo htmlspecialchars($imagePath); ?>"
                                onerror="this.onerror=null; this.src='../admindashboard/uploads/default-profile.jpg';"
                                alt="Profile Image"
                                class="profile-image">
                        </div>
                        <div class="profile-info ml-0 sm:ml-6 mt-4 sm:mt-0">
                            <h1 class="text-xl sm:text-2xl font-bold">
                                Welcome, <?php echo htmlspecialchars($coordinator_name); ?>!
                            </h1>
                            <div class="flex flex-col sm:flex-row sm:items-start gap-2 sm:gap-4 mt-2 text-sm text-gray-600 profile-details">
                                <span class="flex items-center justify-center sm:justify-start">
                                    <i class="fas fa-building mr-2 text-green-600"></i>
                                    Department: <?php echo htmlspecialchars($coordinator_department); ?>
                                </span>
                                <span class="flex items-center justify-center sm:justify-start">
                                    <i class="fas fa-user-shield mr-2 text-green-600"></i>
                                    Role: <?php echo htmlspecialchars($coordinator_user_role); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="login-info mt-4 sm:mt-0 w-full sm:w-auto">
                        <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium inline-block">
                            Last Login:
                            <?php
                            echo isset($_SESSION['last_login'])
                                ? date('M d, Y h:i A', strtotime($_SESSION['last_login']))
                                : 'No record found';
                            ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Stats Section -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <?php
                // Ensure department_id is set in the session
                if (isset($_SESSION['department_id'])) {
                    $department_id = $_SESSION['department_id'];

                    // Check if department_id is 1 (University Wide Coordinator)
                    if ($department_id == 1) {
                        // Count all trainees if department_id is 1
                        $trainee_query = "SELECT COUNT(*) as total FROM trainees";
                    } else {
                        // Count only trainees in the same department
                        $trainee_query = "SELECT COUNT(*) as total FROM trainees WHERE department_id = ?";
                    }

                    // Execute the query
                    $stmt = $conn->prepare($trainee_query);
                    if ($department_id != 1) {
                        $stmt->bind_param("i", $department_id);
                    }
                    $stmt->execute();
                    $trainee_result = $stmt->get_result();
                    $trainee_count = $trainee_result->fetch_assoc()['total'];
                } else {
                    // Handle cases where department_id is not set
                    $trainee_count = 0;
                }
                ?>
                <!-- Trainees Card -->
                <div class="glass p-5 stat-card">
                    <a href="../admindashboard/coordinator_traineelist.php" class="block">
                        <div class="flex items-center justify-between stat-info">
                            <div class="flex items-center">
                                <div class="p-3 bg-green-500 rounded-lg stat-icon">
                                    <i class="text-lg text-white fas fa-users"></i>
                                </div>
                                <div class="ml-3">
                                    <h2 class="text-base font-medium">Trainees</h2>
                                </div>
                            </div>
                            <p class="text-2xl font-bold"><?php echo $trainee_count; ?></p>
                        </div>
                        <div class="mt-4 text-center sm:text-right">
                            <span class="text-sm font-medium text-green-600 hover:text-green-800 hover:underline inline-flex items-end justify-end sm:justify-end w-full">
                                View All
                                <i class="fas fa-arrow-right ml-1 text-xs"></i>
                            </span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Quick Access Section - Mobile Only -->
            <div class="sm:hidden glass p-4 mb-6">
                <h2 class="text-lg font-medium mb-3 text-center">Quick Access</h2>
                <div class="grid grid-cols-1 xs:grid-cols-2 gap-3">
                    <a href="../admindashboard/coordinator_traineelist.php" class="bg-green-500 text-white rounded-lg p-3 text-center">
                        <i class="fas fa-users mb-1 block text-xl"></i>
                        <span class="text-sm">Trainees</span>
                    </a>
                    <!-- More quick links can be added here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        const menuToggle = document.querySelector('#menu-toggle');
        const sidebar = document.querySelector('#sidebar');
        if (menuToggle) {
            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('-translate-x-full');
            });
        }
    </script>
</body>

</html>