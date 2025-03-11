<?php
include '../dbcon.php'; // Include database connection
session_start();

date_default_timezone_set('Asia/Manila'); // Set timezone to Manila

// Function to send a SweetAlert response with Green Gradient Background + Glassmorphism
function sendSweetAlert($icon, $title, $text, $redirectUrl = null)
{
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: '" . $icon . "',
                    title: '" . addslashes($title) . "',
                    text: '" . addslashes($text) . "',
                    background: 'rgba(255, 255, 255, 0.2)', /* Glass effect */
                    color: '#ffffff',
                    backdrop: `linear-gradient(135deg, #4ade80, #3b82f6)`, /* Green Gradient */
                    customClass: {
                        popup: 'glass-alert',
                        title: 'glass-title',
                        content: 'glass-content'
                    },
                    confirmButtonColor: '#E67E22',
                    confirmButtonText: 'OK',
                    showClass: { popup: 'animate__animated animate__fadeInDown' },
                    hideClass: { popup: 'animate__animated animate__fadeOutUp' }
                })" . ($redirectUrl ? ".then(() => { window.location.href = '$redirectUrl'; })" : "") . "
            });
          </script>";
    exit();
}

// Ensure user is logged in
if (!isset($_SESSION['id'])) {
    sendSweetAlert('error', 'Unauthorized', 'You must be logged in to continue!', '../login.php');
}

$trainee_id = $_SESSION['id'];
$date = date('Y-m-d');
$currentTime = date('h:i A'); // 12-hour format with AM/PM

// Validate image
if (!isset($_POST['timeOutImageData']) || empty($_POST['timeOutImageData'])) {
    sendSweetAlert('warning', 'Image Required', 'Please capture an image before submitting.', 'index.php');
}

// Decode and save image
$image_data = $_POST['timeOutImageData'];
list(, $image_data) = explode(',', $image_data);
$image_data = base64_decode($image_data);
$image_name = '../admindashboard/uploads/trainee_uploads/' . uniqid() . '.png';
file_put_contents($image_name, $image_data);

// Optional activity details
$activityDetails = isset($_POST['activityDetails']) ? trim($_POST['activityDetails']) : null;

// Check if the trainee exists
$stmt = $conn->prepare("SELECT id FROM trainees WHERE id = ?");
$stmt->bind_param("i", $trainee_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    sendSweetAlert('error', 'Invalid User', 'Trainee not found!', 'index.php');
}

// Check if the trainee has already timed in today
$stmt = $conn->prepare("SELECT * FROM studentloggeddata WHERE trainee_id = ? AND date = ?");
$stmt->bind_param("is", $trainee_id, $date);
$stmt->execute();
$result = $stmt->get_result();
$existingRecord = $result->fetch_assoc();

if (!$existingRecord) {
    // No time-in record found for today
    sendSweetAlert('warning', 'No Time-In Record', 'You must time in before timing out.', 'index.php');
}

// Determine if it's AM or PM
$currentPeriod = date('A');

if ($currentPeriod === 'AM') {
    // Update first timeout (morning)
    if ($existingRecord['first_timeout']) {
        sendSweetAlert('warning', 'Already Timed Out', 'You have already timed out this morning.', 'index.php');
    } else {
        $stmt = $conn->prepare("UPDATE studentloggeddata SET first_timeout = ?, first_activity_details = ?, first_timeout_image = ? WHERE trainee_id = ? AND date = ?");
        $stmt->bind_param("sssis", $currentTime, $activityDetails, $image_name, $trainee_id, $date);
    }
} else {
    // Update second timeout (afternoon)
    if ($existingRecord['second_timeout']) {
        sendSweetAlert('warning', 'Already Timed Out', 'You have already timed out this afternoon.', 'index.php');
    } elseif (!$existingRecord['second_time_in']) {
        sendSweetAlert('warning', 'No Second Time-In', 'You must time in for the second period before timing out.', 'index.php');
    } else {
        $stmt = $conn->prepare("UPDATE studentloggeddata SET second_timeout = ?, second_activity_details = ?, second_timeout_image = ? WHERE trainee_id = ? AND date = ?");
        $stmt->bind_param("sssis", $currentTime, $activityDetails, $image_name, $trainee_id, $date);
    }
}

// Execute query and check for errors
if (!$stmt->execute()) {
    sendSweetAlert('error', 'Database Error', 'Failed to record your time-out.', 'index.php');
}

// Successful submission
sendSweetAlert('success', 'Time-Out Successful', 'Your attendance has been recorded.', 'index.php');
