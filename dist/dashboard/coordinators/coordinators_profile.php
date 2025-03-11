<?php
// Start session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Include the database connection file
include '../dbcon.php';

/**
 * Fetches coordinator profile data.
 */
function fetchCoordinatorProfile($coordinatorId)
{
    global $conn;

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

    return $result->num_rows ? $result->fetch_assoc() : null;
}

$coordinatorId = $_GET['id'] ?? 0;
$coordinatorData = fetchCoordinatorProfile($coordinatorId);

if (!$coordinatorData) {
    echo "Coordinator not found.";
    exit();
}

$row = $coordinatorData;

// Correct the image path
$imagePath = '../admindashboard/' . $row['image'];
if (!file_exists($imagePath) || empty($row['image'])) {
    $imagePath = '../admindashboard/uploads/default-profile.jpg'; // Default fallback
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinator Profile - <?php echo htmlspecialchars($row['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #10b981 0%, #3b82f6 100%);
            background-attachment: fixed;
        }

        .profile-card {
            backdrop-filter: blur(12px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .bg-green-custom {
            background-color: rgba(16, 185, 129, 0.95);
        }

        .orange-accent {
            color: #f97316;
        }

        .border-orange {
            border-color: #f97316;
        }

        .btn-orange {
            background-color: #f97316;
            transition: all 0.3s ease;
        }

        .btn-orange:hover {
            background-color: #ea580c;
            transform: translateY(-2px);
        }

        .info-box {
            transition: all 0.3s ease;
        }

        .info-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
    </style>
</head>

<body class="min-h-screen py-8">
    <div class="sticky top-0 z-50 w-full">
        <?php include 'nav/top-nav.php'; ?>
    </div>

    <div class="container mx-auto px-4 py-6 mt-16 sm:mt-20">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white bg-opacity-95 rounded-xl shadow-lg overflow-hidden profile-card">
                <div class="flex flex-col lg:flex-row">
                    <div class="w-full lg:w-1/3 bg-green-custom p-6">
                        <div class="flex flex-col items-center">
                            <div class="relative">
                                <div class="w-28 h-28 mb-4 rounded-full overflow-hidden border-4 border-white/50 shadow-lg">
                                    <img src="<?php echo htmlspecialchars($imagePath); ?>"
                                        alt="<?php echo htmlspecialchars($row['name']); ?>"
                                        class="object-cover w-full h-full">
                                </div>
                                <div class="absolute bottom-0 right-0 bg-orange-500 rounded-full p-1.5 border-2 border-white">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                            </div>
                            <h2 class="text-xl font-bold text-white mt-3"><?php echo htmlspecialchars($row['name']); ?></h2>
                            <p class="text-sm font-medium text-white/80 mt-1"><?php echo htmlspecialchars($row['department_name']); ?></p>

                            <div class="mt-8 w-full">
                                <div class="flex items-center p-3 bg-white/10 rounded-lg mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-sm text-white break-all"><?php echo htmlspecialchars($row['email'] ?? 'email@example.com'); ?></span>
                                </div>
                                <div class="flex items-center p-3 bg-white/10 rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                    <span class="text-sm text-white"><?php echo htmlspecialchars($row['phone_number'] ?? '(555) 123-4567'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="w-full lg:w-2/3 p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-5 pb-2 border-b-2 border-green-500/30 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Profile Information
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                            <div class="bg-green-50 p-4 rounded-lg shadow-sm info-box border-l-4 border-green-500">
                                <label class="text-xs font-medium text-gray-500 block mb-1">Full Name</label>
                                <p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($row['name']); ?></p>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg shadow-sm info-box border-l-4 border-orange-500">
                                <label class="text-xs font-medium text-gray-500 block mb-1">Department</label>
                                <p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($row['department_name']); ?></p>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg shadow-sm info-box border-l-4 border-orange-500">
                                <label class="text-xs font-medium text-gray-500 block mb-1">Email Address</label>
                                <p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($row['email'] ?? 'email@example.com'); ?></p>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg shadow-sm info-box border-l-4 border-green-500">
                                <label class="text-xs font-medium text-gray-500 block mb-1">Phone Number</label>
                                <p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($row['phone_number'] ?? '(555) 123-4567'); ?></p>
                            </div>
                        </div>

                        <h4 class="text-md font-bold text-gray-700 mb-3 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Additional Information
                        </h4>
                        <div class="bg-green-50 p-5 rounded-lg shadow-sm mb-6 border border-green-200">
                            <p class="text-sm text-gray-700 leading-relaxed">
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