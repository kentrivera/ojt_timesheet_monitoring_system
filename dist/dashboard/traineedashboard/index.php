<?php
session_start();
include '../dbcon.php';

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Trainee') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Retrieve logged-in user ID from the session
$user_id = $_SESSION['user_id'];

// Fetch trainee details from the database using mysqli
$query = "SELECT * FROM trainees WHERE id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    error_log("Database Error: Unable to prepare query.");
    header("Location: ../login.php?error=server_error");
    exit();
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $trainee = $result->fetch_assoc();
} else {
    header("Location: ../login.php?error=trainee_not_found");
    exit();
}

$stmt->close();

// Set the Manila time zone
date_default_timezone_set('Asia/Manila');

// Fetch the trainee's username
$query = "SELECT * FROM trainees WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user_info = $result->fetch_assoc();

if (!$user_info) {
    echo "User data not found.";
    exit();
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainee Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>

<body class="bg-gradient-to-r from-green-400 to-blue-500">

    <!-- Top Navbar -->
    <?php include 'nav/top-nav.php'; ?>

    <!-- Main Content -->
    <div class="container px-4 mx-auto mt-20">
        <div class="max-w-md p-4 sm:p-8 mx-auto text-white rounded-lg shadow-xl bg-gradient-to-br from-green-300 to-orange-400">
            <!-- Header content unchanged -->
            <div class="flex items-center justify-center mb-6">
                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-green-500 to-green-700 flex items-center justify-center shadow-lg border-2 border-green-400">
                    <div class="w-14 h-14 rounded-full bg-white/10 flex items-center justify-center backdrop-blur-sm">
                        <i class="fas fa-clock text-2xl text-orange-500 drop-shadow animate-spin"></i>
                    </div>
                </div>
            </div>

            <h1 class="text-2xl sm:text-3xl font-bold text-center mb-2">Trainee Dashboard</h1>
            <p class="text-center text-orange-600 mb-6">Track your attendance and timesheets</p>

            <div class="bg-green-200 p-4 rounded-lg mb-6 border border-green-300">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                    <span class="font-medium text-gray-800">Current Time:</span>
                    <span id="defaultTime" class="font-mono text-gray-800 bg-green-100 px-3 py-1 rounded-md w-full sm:w-auto text-center"></span>
                </div>
            </div>

            <!-- Buttons unchanged -->
            <div class="grid grid-cols-1 gap-4 mb-6">
                <button id="openTimeInModalBtn" class="button-hover bg-gradient-to-r from-green-500 to-green-600 rounded-lg py-3 px-4 font-medium shadow-lg">
                    <div class="flex items-center justify-center">
                        <i class="fas fa-sign-in-alt mr-2 text-orange-500"></i>
                        <span>Time In</span>
                    </div>
                </button>

                <button id="openTimeOutModalBtn" class="button-hover bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 rounded-lg py-3 px-4 font-medium shadow-lg text-white">
                    <div class="flex items-center justify-center">
                        <i class="fas fa-sign-out-alt mr-2 text-orange-200"></i>
                        <span>Time Out</span>
                    </div>
                </button>
            </div>

            <!-- View Timesheet Button -->
            <a href="view_timesheet.php" class="button-hover flex items-center justify-center w-full bg-green-500 hover:bg-green-600 rounded-lg py-3 px-4 font-medium shadow-lg transition-all">
                <i class="fas fa-calendar-alt mr-2 text-orange-500"></i>
                <span>View Timesheet</span>
            </a>

            <!-- Time In Modal with improved contrast -->
            <div id="timeInModal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black bg-opacity-75 transition-opacity duration-300">
                <div class="w-full max-w-sm mx-4 p-4 overflow-auto text-white rounded-lg shadow-lg bg-gradient-to-br from-green-300 to-orange-400 border-2 border-green-400 md:max-w-md lg:max-w-lg">
                    <div class="flex items-center justify-end mb-4">
                        <button id="closeTimeInModal" class="text-white hover:text-orange-300 text-2xl text-right">&times;</button>
                    </div>
                    <form id="timeInForm">
                        <div class="mb-4">
                            <label class="block mb-1 text-green-800 font-medium">Username</label>
                            <p class="w-full px-4 py-2 bg-green-100 text-gray-800 border-2 border-green-400 rounded-lg"><?= htmlspecialchars($user_info['name']) ?></p>
                        </div>
                        <div class="mb-4">
                            <label class="block mb-1 text-green-800 font-medium">Current Time</label>
                            <p class="w-full px-4 py-2 bg-green-100 text-gray-800 border-2 border-green-400 rounded-lg" id="modalDefaultTime"></p>
                        </div>
                        <div class="mb-4">
                            <!-- Note for selfie with agency manager -->
                            <p class="mt-2 text-sm text-red-700 text-center font-semibold p-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                Please take a selfie with your agency manager for verification.
                            </p>
                            <video id="camera" autoplay playsinline class="w-full border-2 border-green-400 rounded-lg"></video>
                            <input type="hidden" name="imageData" id="imageDataInput">
                            <button type="button" id="captureButton" class="w-full px-4 py-2 mt-2 text-white bg-green-600 rounded-lg hover:bg-green-500 border border-green-400 font-medium">Capture</button>

                        </div>
                    </form>
                </div>
            </div>

            <!-- Captured Image Preview Modal -->
            <div id="imagePreviewModal" class="fixed inset-0 flex items-center justify-center hidden bg-black bg-opacity-75">
                <div class="w-full max-w-sm p-4 mx-4 text-center rounded-lg shadow-lg bg-gradient-to-br from-green-300 to-orange-400 border-2 border-green-400">
                    <h2 class="mb-4 text-xl font-semibold text-green-800">Captured Image</h2>
                    <p class="mb-2 text-green-700">Time Captured: <span id="previewCapturedTime" class="text-gray-800"></span></p>
                    <img id="previewCapturedImage" src="" class="w-full border-2 border-green-400 rounded-lg">

                    <!-- Buttons in Flex Layout -->
                    <div class="flex flex-col sm:flex-row gap-2 mt-4">
                        <!-- Try Again Button -->
                        <button id="tryAgainButton" class="w-full sm:w-1/2 flex items-center justify-center gap-2 px-4 py-2 text-white bg-orange-600 rounded-lg hover:bg-orange-500 border border-orange-400 font-medium">
                            <i class="fas fa-redo"></i> Try Again
                        </button>

                        <!-- Submit Button -->
                        <form id="timeInFormPreview" action="submit_time.php" method="POST" class="w-full sm:w-1/2">
                            <input type="hidden" name="imageData" id="imageDataInputPreview">
                            <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-2 text-white bg-green-600 rounded-lg hover:bg-green-500 border border-green-400 font-medium">
                                <i class="fas fa-check-circle"></i> Submit
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Time Out Modal -->
            <div id="timeOutModal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black bg-opacity-75 transition-opacity duration-300">
                <div class="w-full max-w-sm p-4 mx-4 overflow-auto text-white rounded-lg shadow-lg bg-gradient-to-br from-green-300 to-orange-400 border-2 border-green-400 md:max-w-md lg:max-w-lg">
                    <div class="flex items-center justify-end mb-4">
                        <button id="closeTimeOutModal" class="text-white hover:text-orange-300 text-2xl text-right">&times;</button>
                    </div>
                    <form id="timeOutForm">
                        <div class="mb-4">
                            <label class="block mb-1 text-green-800 font-medium">Username</label>
                            <p class="w-full px-4 py-2 bg-green-100 text-gray-800 border-2 border-green-400 rounded-lg"><?= htmlspecialchars($user_info['name']) ?></p>
                        </div>
                        <div class="mb-4">
                            <label class="block mb-1 text-green-800 font-medium">Current Time</label>
                            <p class="w-full px-4 py-2 bg-green-100 text-gray-800 border-2 border-green-400 rounded-lg" id="modalTimeOutTime"></p>
                        </div>
                        <div class="mb-4">
                            <label class="block mb-1 text-green-800 font-medium">Capture Image</label>
                            <video id="timeOutCamera" autoplay playsinline class="w-full border-2 border-green-400 rounded-lg"></video>
                            <input type="hidden" name="timeOutImageData" id="timeOutImageDataInput">
                            <button type="button" id="timeOutCaptureButton" class="w-full px-4 py-2 mt-2 text-white bg-green-600 rounded-lg hover:bg-green-500 border border-green-400 font-medium">Capture</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Captured Image Preview Modal for Time Out -->
            <div id="timeOutPreviewModal" class="fixed inset-0 flex items-center justify-center hidden bg-black bg-opacity-75">
                <div class="w-full max-w-sm p-4 mx-4 text-center rounded-lg shadow-lg bg-gradient-to-br from-green-300 to-orange-400 border-2 border-green-400">
                    <h2 class="mb-4 text-xl font-semibold text-green-800">Captured Image</h2>
                    <p class="mb-2 text-green-700">Time Captured: <span id="previewTimeOutCapturedTime" class="text-gray-800"></span></p>
                    <img id="previewTimeOutCapturedImage" src="" class="w-full border-2 border-green-400 rounded-lg">

                    <!-- Added Activity Details Text Field -->
                    <div class="mt-4">
                        <textarea id="timeOutActivityDetails" name="activityDetails" placeholder="Activity details (optional)" class="w-full p-2 text-gray-800 border border-green-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white"></textarea>
                    </div>

                    <!-- Buttons in Flex Layout -->
                    <div class="flex flex-col sm:flex-row gap-2 mt-4">
                        <!-- Try Again Button -->
                        <button id="timeOutTryAgainButton" class="w-full sm:w-1/2 flex items-center justify-center gap-2 px-4 py-2 text-white bg-orange-600 rounded-lg hover:bg-orange-500 border border-orange-400 font-medium">
                            <i class="fas fa-redo"></i> Try Again
                        </button>

                        <!-- Submit Button -->
                        <form id="timeOutFormPreview" action="submit_timeout.php" method="POST" class="w-full sm:w-1/2">
                            <input type="hidden" name="timeOutImageData" id="timeOutImageDataInputPreview">
                            <input type="hidden" name="activityDetails" id="timeOutActivityDetailsInput">
                            <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-2 text-white bg-green-600 rounded-lg hover:bg-green-500 border border-green-400 font-medium">
                                <i class="fas fa-check-circle"></i> Submit
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Display current time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            document.getElementById('defaultTime').textContent = timeString;

            if (document.getElementById('modalDefaultTime')) {
                document.getElementById('modalDefaultTime').textContent = timeString;
            }

            if (document.getElementById('modalTimeOutTime')) {
                document.getElementById('modalTimeOutTime').textContent = timeString;
            }
        }

        updateTime();
        setInterval(updateTime, 1000);

        document.addEventListener("DOMContentLoaded", () => {
            let stream = null;

            // Function to Update Time Every Second
            function updateTime() {
                let now = new Date();
                let formattedTime = now.toLocaleTimeString("en-US", {
                    hour: "numeric",
                    minute: "numeric",
                    second: "numeric",
                    hour12: true,
                });

                document.getElementById("modalDefaultTime").textContent = formattedTime;
                document.getElementById("previewCapturedTime").textContent = formattedTime;
            }

            setInterval(updateTime, 1000);
            updateTime();

            // Function to Start Camera
            async function startCamera(videoId) {
                try {
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: true
                    });
                    document.getElementById(videoId).srcObject = stream;
                } catch (error) {
                    console.error("Camera access error: ", error);
                }
            }

            // Function to Stop Camera
            function stopCamera() {
                if (stream) {
                    stream.getTracks().forEach((track) => track.stop());
                }
            }

            // Open Time In Modal and Start Camera
            document.getElementById("openTimeInModalBtn").addEventListener("click", () => {
                document.getElementById("timeInModal").classList.remove("hidden");
                startCamera("camera");
            });

            // Close Time In Modal and Stop Camera
            document.getElementById("closeTimeInModal").addEventListener("click", () => {
                document.getElementById("timeInModal").classList.add("hidden");
                stopCamera();
            });

            // Capture Image and Open Preview Modal
            document.getElementById("captureButton").addEventListener("click", () => {
                const video = document.getElementById("camera");
                const canvas = document.createElement("canvas");
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                canvas.getContext("2d").drawImage(video, 0, 0, canvas.width, canvas.height);

                const imageData = canvas.toDataURL("image/png");
                document.getElementById("imageDataInput").value = imageData;
                document.getElementById("imageDataInputPreview").value = imageData;

                // Show Image Preview Modal with Time
                document.getElementById("previewCapturedImage").src = imageData;
                document.getElementById("previewCapturedTime").textContent = document.getElementById("modalDefaultTime").textContent;
                document.getElementById("imagePreviewModal").classList.remove("hidden");

                // Hide Time In Modal
                document.getElementById("timeInModal").classList.add("hidden");
                stopCamera();
            });

            // Try Again Button - Close Preview and Reopen Camera
            document.getElementById("tryAgainButton").addEventListener("click", () => {
                document.getElementById("imagePreviewModal").classList.add("hidden");
                document.getElementById("timeInModal").classList.remove("hidden");
                startCamera("camera");
            });

            // Close Preview Modal When Clicking Outside
            document.addEventListener("click", (event) => {
                const previewModal = document.getElementById("imagePreviewModal");
                if (previewModal && !previewModal.contains(event.target) && !event.target.closest("#captureButton")) {
                    previewModal.classList.add("hidden");
                }
            });
        });

        // time out script
        document.addEventListener("DOMContentLoaded", () => {
            let stream = null;

            // Function to Update Time Every Second
            function updateTime() {
                let now = new Date();
                let formattedTime = now.toLocaleTimeString("en-US", {
                    hour: "numeric",
                    minute: "numeric",
                    second: "numeric",
                    hour12: true,
                });

                document.getElementById("modalDefaultTime").textContent = formattedTime;
                document.getElementById("modalTimeOutTime").textContent = formattedTime;
                document.getElementById("previewCapturedTime").textContent = formattedTime;
                document.getElementById("previewTimeOutCapturedTime").textContent = formattedTime;
            }

            setInterval(updateTime, 1000);
            updateTime();

            // Function to Start Camera
            async function startCamera(videoId) {
                try {
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: true
                    });
                    document.getElementById(videoId).srcObject = stream;
                } catch (error) {
                    console.error("Camera access error: ", error);
                }
            }

            // Function to Stop Camera
            function stopCamera() {
                if (stream) {
                    stream.getTracks().forEach((track) => track.stop());
                }
            }

            // Open Time Out Modal and Start Camera
            document.getElementById("openTimeOutModalBtn").addEventListener("click", () => {
                document.getElementById("timeOutModal").classList.remove("hidden");
                startCamera("timeOutCamera");
            });

            // Close Time Out Modal and Stop Camera
            document.getElementById("closeTimeOutModal").addEventListener("click", () => {
                document.getElementById("timeOutModal").classList.add("hidden");
                stopCamera();
            });

            // Capture Image and Open Preview Modal for Time Out
            document.getElementById("timeOutCaptureButton").addEventListener("click", () => {
                const video = document.getElementById("timeOutCamera");
                const canvas = document.createElement("canvas");
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                canvas.getContext("2d").drawImage(video, 0, 0, canvas.width, canvas.height);

                const imageData = canvas.toDataURL("image/png");
                document.getElementById("timeOutImageDataInput").value = imageData;
                document.getElementById("timeOutImageDataInputPreview").value = imageData;

                // Show Image Preview Modal with Time
                document.getElementById("previewTimeOutCapturedImage").src = imageData;
                document.getElementById("previewTimeOutCapturedTime").textContent = document.getElementById("modalTimeOutTime").textContent;
                document.getElementById("timeOutPreviewModal").classList.remove("hidden");

                // Hide Time Out Modal
                document.getElementById("timeOutModal").classList.add("hidden");
                stopCamera();
            });

            // Try Again Button - Close Preview and Reopen Camera for Time Out
            document.getElementById("timeOutTryAgainButton").addEventListener("click", () => {
                document.getElementById("timeOutPreviewModal").classList.add("hidden");
                document.getElementById("timeOutModal").classList.remove("hidden");
                startCamera("timeOutCamera");
            });

            // Close Preview Modal When Clicking Outside
            document.addEventListener("click", (event) => {
                const previewModal = document.getElementById("timeOutPreviewModal");
                if (previewModal && !previewModal.contains(event.target) && !event.target.closest("#timeOutCaptureButton")) {
                    previewModal.classList.add("hidden");
                }
            });
        });
        document.getElementById('timeOutFormPreview').addEventListener('submit', function(e) {
            // Capture activity details
            const activityDetails = document.getElementById('timeOutActivityDetails').value;
            document.getElementById('timeOutActivityDetailsInput').value = activityDetails;

            // Capture image data
            const imageData = document.getElementById('timeOutImageDataInput').value;
            document.getElementById('timeOutImageDataInputPreview').value = imageData;

            // Debugging: Log activity details and image data
            console.log('Activity Details:', activityDetails);
            console.log('Image Data:', imageData);
        });
    </script>
</body>

</html>