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

/**
 * Fetches coordinator profile data.
 *
 * @param int $coordinatorId The ID of the coordinator.
 * @return array|null Returns an associative array with coordinator data or null if not found.
 */
function fetchCoordinatorProfile($coordinatorId)
{
    global $conn;

    // Validate coordinator ID
    if (!is_numeric($coordinatorId) || $coordinatorId <= 0) {
        return null;
    }

    // Fetch coordinator details
    $sql = "SELECT coordinators.id, coordinators.name, coordinators.email, coordinators.image, 
                   coordinators.phone_number,
                   CASE 
                       WHEN coordinators.id = 1 THEN 'University Wide Coordinator' 
                       ELSE department.department_name 
                   END AS department_name 
            FROM coordinators 
            LEFT JOIN department ON coordinators.department_id = department.department_id 
            WHERE coordinators.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $coordinatorId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return null; // Coordinator not found
    }

    return $result->fetch_assoc();
}

// Example usage
$coordinatorId = $_GET['id'] ?? 0; // Get coordinator ID from the URL
$coordinatorData = fetchCoordinatorProfile($coordinatorId);

if ($coordinatorData === null) {
    echo "Coordinator not found.";
    exit();
}

// Use the fetched data in your HTML template
$row = $coordinatorData;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinator Profile - <?php echo htmlspecialchars($row['name']); ?></title>
    <!-- Include Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Custom styles for green and orange palette -->
    <style>
        :root {
            --color-green-light: #4ade80;
            --color-green: #16a34a;
            --color-green-dark: #15803d;
            --color-orange-light: #fb923c;
            --color-orange: #f97316;
            --color-orange-dark: #ea580c;
        }

        .bg-green-gradient {
            background: linear-gradient(to bottom, var(--color-green-light), var(--color-green));
        }

        .border-green {
            border-color: var(--color-green);
        }

        .text-green {
            color: var(--color-green);
        }

        .border-orange {
            border-color: var(--color-orange);
        }

        .text-orange {
            color: var(--color-orange);
        }

        .btn-orange {
            background-color: var(--color-orange);
            color: white;
            transition: background-color 0.3s;
        }

        .btn-orange:hover {
            background-color: var(--color-orange-dark);
        }
    </style>
</head>

<body class="bg-gray-50">
    <!-- Top navigation - fixed position to avoid conflicts -->
    <div class="sticky top-0 z-50 w-full">
        <?php include 'nav/top-nav.php'; ?>
    </div>

    <!-- Main content area with proper spacing from top nav -->
    <div class="container mx-auto px-4 py-6 mt-2">
        <div class="max-w-4xl mx-auto">
            <!-- Profile card -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="flex flex-col lg:flex-row">
                    <!-- Profile sidebar with green gradient -->
                    <div class="w-full lg:w-1/3 bg-green-gradient p-6">
                        <div class="flex flex-col items-center">
                            <!-- Profile image -->
                            <div class="relative">
                                <div class="w-24 h-24 mb-3 rounded-full overflow-hidden border-4 border-white/30 shadow">
                                    <img src="<?php echo htmlspecialchars($row['image']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="object-cover w-full h-full">
                                </div>
                            </div>
                            <!-- Name and department -->
                            <h2 class="text-xl font-semibold text-white mt-2"><?php echo htmlspecialchars($row['name']); ?></h2>
                            <p class="text-sm font-medium text-white/90 mt-1"><?php echo htmlspecialchars($row['department_name']); ?></p>

                            <!-- Contact information -->
                            <div class="mt-6 w-full text-white">
                                <div class="flex items-center mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-sm break-all"><?php echo htmlspecialchars($row['email'] ?? 'email@example.com'); ?></span>
                                </div>
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                    <span class="text-sm"><?php echo htmlspecialchars($row['phone_number'] ?? '(555) 123-4567'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Profile content -->
                    <div class="w-full lg:w-2/3 p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100">Profile Information</h3>

                        <!-- Profile details grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="text-xs text-gray-500 block mb-1">Full Name</label>
                                <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($row['name']); ?></p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="text-xs text-gray-500 block mb-1">Department</label>
                                <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($row['department_name']); ?></p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="text-xs text-gray-500 block mb-1">Email Address</label>
                                <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($row['email'] ?? 'email@example.com'); ?></p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="text-xs text-gray-500 block mb-1">Phone Number</label>
                                <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($row['phone_number'] ?? '(555) 123-4567'); ?></p>
                            </div>
                        </div>

                        <!-- Additional information -->
                        <h4 class="text-md font-medium text-gray-700 mb-3">Additional Information</h4>
                        <div class="bg-gray-50 p-4 rounded-lg mb-4">
                            <p class="text-sm text-gray-600">
                                <?php echo htmlspecialchars($row['bio'] ?? 'No additional information available for this coordinator.'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>