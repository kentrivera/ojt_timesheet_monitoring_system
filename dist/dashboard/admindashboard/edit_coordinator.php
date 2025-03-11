<?php
// Include the database connection file
include '../dbcon.php';

// Fetch coordinator details for editing
$id = $_GET['id'];
$sql = "SELECT * FROM coordinators WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$coordinator = $result->fetch_assoc();

// Fetch departments from the department table
$department_sql = "SELECT * FROM department";
$department_result = $conn->query($department_sql);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $name = $_POST['name'];
    $email = $_POST['email'];
    $department_id = $_POST['department_id']; // Updated to use department_id
    $phone_number = $_POST['phone_number'];
    $password = $_POST['password']; // New password field

    // Handle image upload
    if ($_FILES['image']['name']) {
        $image = $_FILES['image']['name'];
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($image);
        move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
    } else {
        $target_file = $coordinator['image']; // Keep the old image if not updated
    }

    // Handle password update (if a new password is provided)
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Hash the new password
    } else {
        $hashed_password = $coordinator['password']; // Keep the existing password if no new one is provided
    }

    // Prepare SQL query to update coordinator
    $sql = "UPDATE coordinators SET name = ?, email = ?, department_id = ?, phone_number = ?, image = ?, password = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssisssi", $name, $email, $department_id, $phone_number, $target_file, $hashed_password, $id);

    // Execute the query
    if ($stmt->execute()) {
        // SweetAlert success message
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Coordinator updated successfully.',
                        confirmButtonColor: '#4CAF50', // Green color
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = 'view_coordinators.php';
                    });
                });
              </script>";
    } else {
        echo "<script>alert('Error updating coordinator.');</script>";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.tailwindcss.com">
    <link rel="stylesheet" href="../../style.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <title>Edit Coordinator</title>
</head>

<body class="bg-gray-100">
    <!-- Include the top navigation bar -->
    <?php include 'nav/top-nav.php'; ?>

    <div class="container mx-auto px-4 py-6">
        <h1 class="text-2xl font-bold mb-6 text-center">Edit Coordinator</h1>
        <form action="edit_coordinator.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data" class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow-lg">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Name</label>
                <input type="text" name="name" value="<?php echo $coordinator['name']; ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                <input type="email" name="email" value="<?php echo $coordinator['email']; ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Department</label>
                <select name="department_id" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="" disabled>Select Department</option>
                    <?php while ($row = $department_result->fetch_assoc()): ?>
                        <option value="<?php echo $row['department_id']; ?>" <?php echo ($row['department_id'] == $coordinator['department_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($row['department_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Phone Number</label>
                <input type="text" name="phone_number" value="<?php echo $coordinator['phone_number']; ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Image</label>
                <input type="file" name="image" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500">
                <?php if ($coordinator['image']): ?>
                    <div class="mt-2">
                        <img src="<?php echo $coordinator['image']; ?>" alt="Current Image" class="w-20 h-20 rounded-lg">
                    </div>
                <?php endif; ?>
            </div>
            <!-- Password Field -->
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Change Password</label>
                <input type="password" name="password" placeholder="Leave blank to keep current password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-500 mt-1">Only fill this field if you want to change the password.</p>
            </div>
            <!-- Button Container -->
            <div class="flex items-center justify-end space-x-4">
                <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-green-500">
                    Update Coordinator
                </button>
                <a href="view_coordinators.php" class="bg-orange-500 hover:bg-orange-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-orange-500">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>

</html>