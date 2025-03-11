<?php
// Include the database connection file
include '../dbcon.php';

// Fetch departments for the dropdown
$departments = $conn->query("SELECT * FROM department ORDER BY department_name ASC");

// Handle adding a new department
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_department'])) {
    $department_name = $_POST['department_name'];

    // Insert the new department into the database
    $stmt = $conn->prepare("INSERT INTO department (department_name) VALUES (?)");
    $stmt->bind_param("s", $department_name);

    if ($stmt->execute()) {
        $message = "New department added successfully.";
        $message_type = "success";  // Success message
    } else {
        $message = "Error: " . $stmt->error;
        $message_type = "error";  // Error message
    }

    $stmt->close();
}

// Handle adding a coordinator
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['add_department'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $department_id = ($_POST['department'] == 1) ? 1 : $_POST['department']; // Default department_id is 1 for University Wide Coordinator
    $phone_number = $_POST['phone_number'];
    $password = $_POST['password'];

    // Hash the password for secure storage
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Handle image upload
    $image = $_FILES['image']['name'];
    $target_dir = "uploads/"; // Ensure this directory exists and is writable
    $target_file = $target_dir . basename($image);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if an image file was uploaded
    if (empty($image)) {
        // Use default image if no file is uploaded
        $target_file = "img/profile.jpg"; // Default image path
    } else {
        // Check if image file is an actual image
        $check = getimagesize($_FILES['image']['tmp_name']);
        if ($check !== false) {
            $uploadOk = 1;
        } else {
            $message = "File is not an image.";
            $message_type = "error";
            $uploadOk = 0;
        }

        // Check file size (5MB limit)
        if ($_FILES['image']['size'] > 5000000) {
            $message = "Sorry, your file is too large.";
            $message_type = "error";
            $uploadOk = 0;
        }

        // Allow certain file formats
        if (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
            $message = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            $message_type = "error";
            $uploadOk = 0;
        }

        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 0) {
            $message = "Sorry, your file was not uploaded.";
            $message_type = "error";
        } else {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                // File uploaded successfully, continue with the database insert
            } else {
                $message = "Sorry, there was an error uploading your file.";
                $message_type = "error";
            }
        }
    }

    // Insert coordinator into the database, including department_id, user_role, and the image path
    $user_role = 'Coordinator'; // Default role for coordinators
    $stmt = $conn->prepare("INSERT INTO coordinators (name, email, department_id, phone_number, password, image, user_role) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssissss", $name, $email, $department_id, $phone_number, $hashed_password, $target_file, $user_role);

    if ($stmt->execute()) {
        $message = "New coordinator added successfully.";
        $message_type = "success"; // Success message

        // Redirect to coordinators list
        header("Location: view_coordinators.php");
        exit;
    } else {
        $message = "Error: " . $stmt->error;
        $message_type = "error"; // Error message
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../style.css">
    <title>Add Coordinator</title>
</head>

<body class="bg-gray-200">
    <!-- Include the top navigation bar -->
    <?php include 'nav/top-nav.php'; ?>

    <div class="container px-4 py-6 mx-auto">
        <!-- Notification Section -->
        <?php if (isset($message)): ?>
            <div class="fixed top-0 right-0 z-50 p-6">
                <div class="bg-<?= ($message_type == 'success') ? 'green' : 'red' ?>-500 text-white px-4 py-2 rounded-lg shadow-md" role="alert">
                    <strong class="font-bold"><?= ucfirst($message_type) ?>!</strong>
                    <span class="block"><?= $message ?></span>
                </div>
            </div>
            <script>
                // Auto-hide notification after 5 seconds
                setTimeout(() => {
                    document.querySelector('[role="alert"]').style.display = 'none';
                }, 5000);
            </script>
        <?php endif; ?>

        <h1 class="mb-6 text-2xl font-bold text-center text-gray-800">Add New Coordinator</h1>
        <form action="add_coordinators.php" method="POST" enctype="multipart/form-data" class="max-w-lg p-6 mx-auto bg-white rounded-lg shadow-lg">
            <div class="mb-4">
                <label class="block mb-2 text-xs font-bold text-gray-700">Name</label>
                <input type="text" name="name" required class="w-full px-3 py-2 text-sm text-gray-800 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div class="mb-4">
                <label class="block mb-2 text-xs font-bold text-gray-700">Email</label>
                <input type="email" name="email" required class="w-full px-3 py-2 text-sm text-gray-800 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div class="mb-4">
                <label class="block mb-2 text-xs font-bold text-gray-700">Department</label>
                <select name="department" required class="w-full px-3 py-2 text-sm text-gray-800 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="" selected disabled>Select Department</option>
                    <?php while ($row = $departments->fetch_assoc()): ?>
                        <option value="<?= $row['department_id'] ?>"><?= htmlspecialchars($row['department_name']) ?></option>
                    <?php endwhile; ?>
                </select>
                <button type="button" class="mt-2 text-xs font-bold text-green-500 underline" data-modal-target="#addDepartmentModal">Add Department</button>
            </div>
            <div class="mb-4">
                <label class="block mb-2 text-xs font-bold text-gray-700">Phone Number</label>
                <input type="text" name="phone_number" required class="w-full px-3 py-2 text-sm text-gray-800 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div class="mb-4">
                <label class="block mb-2 text-xs font-bold text-gray-700">Password</label>
                <input type="password" name="password" required class="w-full px-3 py-2 text-sm text-gray-800 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div class="mb-4">
                <label class="block mb-2 text-xs font-bold text-gray-700">Profile Image</label>
                <input type="file" name="image" accept="image/*" class="w-full px-3 py-2 text-sm text-gray-800 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <!-- Button Container -->
            <div class="flex justify-end space-x-4">
                <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-green-500 rounded hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                    Add Coordinator
                </button>
                <a href="view_coordinators.php" class="px-4 py-2 text-xs font-bold text-white bg-orange-500 rounded hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500">
                    Cancel
                </a>
            </div>
        </form>

        <!-- Add Department Modal -->
        <div id="addDepartmentModal" class="fixed inset-0 flex items-center justify-center hidden bg-gray-900 bg-opacity-50">
            <div class="w-full max-w-sm p-6 bg-white rounded-lg shadow-lg">
                <h2 class="mb-4 text-xl font-bold">Add New Department</h2>
                <form action="add_coordinators.php" method="POST">
                    <div class="mb-4">
                        <label class="block mb-2 text-xs font-bold text-gray-700">Department Name</label>
                        <input type="text" name="department_name" required class="w-full px-3 py-2 text-sm text-gray-800 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div class="flex items-center justify-between">
                        <button type="submit" name="add_department" class="px-4 py-2 text-xs font-bold text-white bg-green-500 rounded hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                            Add Department
                        </button>
                        <button type="button" class="px-4 py-2 text-xs font-bold text-gray-500 bg-gray-200 rounded hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500" onclick="closeModal()">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Show the modal
        document.querySelector('[data-modal-target="#addDepartmentModal"]').addEventListener('click', function() {
            document.getElementById('addDepartmentModal').classList.remove('hidden');
        });

        // Close the modal
        function closeModal() {
            document.getElementById('addDepartmentModal').classList.add('hidden');
        }
    </script>
</body>

</html>