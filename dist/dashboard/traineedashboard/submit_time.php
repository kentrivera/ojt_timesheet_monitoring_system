<?php
include '../dbcon.php';
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
if (!isset($_POST['imageData']) || empty($_POST['imageData'])) {
    sendSweetAlert('warning', 'Image Required', 'Please capture an image before submitting.', 'index.php');
}

// Decode and save image
$image_data = $_POST['imageData'];
list(, $image_data) = explode(',', $image_data);
$image_data = base64_decode($image_data);
$image_name = '../admindashboard/uploads/trainee_uploads/' . uniqid() . '.png';
file_put_contents($image_name, $image_data);

// Check if the trainee exists
$stmt = $conn->prepare("SELECT id FROM trainees WHERE id = ?");
$stmt->bind_param("i", $trainee_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    sendSweetAlert('error', 'Invalid User', 'Trainee not found!', 'index.php');
}

// Check if already timed in
$stmt = $conn->prepare("SELECT * FROM studentloggeddata WHERE trainee_id = ? AND date = ?");
$stmt->bind_param("is", $trainee_id, $date);
$stmt->execute();
$result = $stmt->get_result();
$existingRecord = $result->fetch_assoc();

// Determine AM or PM
$currentPeriod = date('A');

if ($currentPeriod === 'AM') {
    if (!$existingRecord) {
        // First time-in (morning)
        $stmt = $conn->prepare("INSERT INTO studentloggeddata (trainee_id, first_time_in, date, first_image) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $trainee_id, $currentTime, $date, $image_name);
    } else {
        sendSweetAlert('warning', 'Already Timed In', 'You have already timed in this morning.', 'index.php');
    }
} else {
    if (!$existingRecord) {
        // Afternoon time-in but no morning record
        $stmt = $conn->prepare("INSERT INTO studentloggeddata (trainee_id, first_time_in, first_timeout, second_time_in, date, second_image) VALUES (?, 'Absent', 'Absent', ?, ?, ?)");
        $stmt->bind_param("ssss", $trainee_id, $currentTime, $date, $image_name);
    } elseif ($existingRecord['second_time_in']) {
        sendSweetAlert('warning', 'Already Timed In', 'You have already timed in this afternoon.', 'index.php');
    } else {
        // Second time-in (afternoon)
        $stmt = $conn->prepare("UPDATE studentloggeddata SET second_time_in = ?, second_image = ? WHERE trainee_id = ? AND date = ?");
        $stmt->bind_param("ssis", $currentTime, $image_name, $trainee_id, $date);
    }
}

// Execute query and check for errors
if (!$stmt->execute()) {
    sendSweetAlert('error', 'Database Error', 'Failed to record your time-in.', 'index.php');
}

// Successful submission
sendSweetAlert('success', 'Time-In Successful', 'Your attendance has been recorded.', 'index.php');
