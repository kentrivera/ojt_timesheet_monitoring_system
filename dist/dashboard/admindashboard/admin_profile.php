<?php
include '../dbcon.php';
session_start(); // Start the session to access user data

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirect to login if not logged in
    exit();
}

$errorMessage = ''; // Variable to store error messages
$successMessage = ''; // Variable to store success messages

// Fetch user data from the database
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc(); // Fetch user data
} else {
    $errorMessage = 'User not found.';
    $user = []; // Empty array to avoid errors
}

// Handle form submission for updating profile
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Basic validation
    if (empty($name) || empty($username) || empty($email)) {
        $errorMessage = 'Name, username, and email are required fields.';
    }
    // Username validation - alphanumeric and underscore only
    elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errorMessage = 'Username can only contain letters, numbers, and underscores.';
    }
    // Email validation
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Invalid email format.';
    }
    // Password validation - only if password field is not empty
    elseif (!empty($password) && strlen($password) < 8) {
        $errorMessage = 'Password must be at least 8 characters long.';
    } else {
        // Prepare data using prepared statements (no need for manual escaping)

        // Hash the password if provided
        $hashed_password = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : $user['password'];

        // Check if the username or email already exists (excluding the current user)
        $checkQuery = "SELECT * FROM users WHERE (username = ? OR email = ?) AND user_id != ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("ssi", $username, $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($row['username'] === $username) {
                $errorMessage = 'Username is already taken.';
            } else {
                $errorMessage = 'Email is already registered.';
            }
        } else {
            // Update user data in the database
            $updateQuery = "UPDATE users SET name = ?, username = ?, email = ?, password = ?, updated_at = NOW() WHERE user_id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("ssssi", $name, $username, $email, $hashed_password, $user_id);

            if ($stmt->execute()) {
                $successMessage = 'Profile updated successfully!';

                // Update session data if needed
                $_SESSION['username'] = $username;

                // Refresh user data
                $query = "SELECT * FROM users WHERE user_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
            } else {
                $errorMessage = 'An error occurred while updating the profile: ' . $stmt->error;
            }
        }

        // Close the statement
        $stmt->close();
    }
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <title>Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .container-custom {
            width: 100%;
            padding-right: 1rem;
            padding-left: 1rem;
            margin-right: auto;
            margin-left: auto;
        }

        @media (min-width: 640px) {
            .container-custom {
                max-width: 640px;
            }
        }

        @media (min-width: 768px) {
            .container-custom {
                max-width: 768px;
            }
        }

        @media (min-width: 1024px) {
            .container-custom {
                max-width: 896px;
            }
        }

        .form-input:focus {
            transform: translateY(-2px);
            transition: transform 0.3s ease;
        }

        .main-content {
            padding-top: 5rem;
        }

        /* Modal animation */
        .modal-fade-in {
            animation: fadeIn 0.3s ease-out;
        }

        .modal-fade-out {
            animation: fadeOut 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: scale(1);
            }

            to {
                opacity: 0;
                transform: scale(0.95);
            }
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <!-- Top Navigation -->
    <?php include 'nav/top-nav.php'; ?>

    <!-- Main Content -->
    <div class="main-content w-full flex items-center justify-center px-4 py-6 sm:py-12">
        <div class="container-custom max-w-lg w-full md:max-w-2xl lg:max-w-3xl">
            <div class="w-full bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Header -->
                <div class="bg-green-800 p-5 sm:p-7">
                    <h2 class="text-xl font-semibold text-center text-white">Admin Profile</h2>
                </div>

                <!-- Profile Details -->
                <div class="p-6 sm:p-8 md:p-10">
                    <!-- Error message display -->
                    <?php if ($errorMessage): ?>
                        <div class="p-3 mb-5 text-xs text-red-600 bg-red-100 rounded-md">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <?php echo $errorMessage; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Profile Information -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Name</label>
                            <p class="mt-1 text-sm text-gray-900 font-semibold"><?php echo htmlspecialchars($user['name'] ?? ''); ?></p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700">Username</label>
                            <p class="mt-1 text-sm text-gray-900 font-semibold"><?php echo htmlspecialchars($user['username'] ?? ''); ?></p>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-700">Email</label>
                            <p class="mt-1 text-sm text-gray-900 font-semibold"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                        </div>

                        <?php if (isset($user['created_at'])): ?>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Member Since</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    <i class="far fa-calendar-alt mr-1 text-green-600"></i>
                                    <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($user['last_login'])): ?>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Last Login</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    <i class="far fa-clock mr-1 text-green-600"></i>
                                    <?php echo date('M d, Y H:i', strtotime($user['last_login'])); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Edit Button -->
                    <div class="mt-8 flex justify-center sm:justify-start">
                        <button onclick="openEditModal()"
                            class="w-full sm:w-auto py-2 px-6 text-xs text-white bg-green-600 rounded-md hover:bg-green-700 transition-colors duration-300 focus:ring-2 focus:ring-green-500 focus:outline-none shadow-md">
                            <i class="mr-2 fa-solid fa-pen"></i> Edit Profile
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black bg-opacity-50 p-4">
        <div id="modalContent" class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6 sm:p-8 md:p-10 modal-fade-in">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-800">Edit Profile</h2>
                <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>

            <!-- Edit Form -->
            <form method="POST" id="profileForm" class="space-y-6">
                <div>
                    <label for="name" class="block text-xs font-medium text-gray-700">Name</label>
                    <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>"
                        class="form-input w-full p-3 mt-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                </div>

                <div>
                    <label for="username" class="block text-xs font-medium text-gray-700">Username</label>
                    <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>"
                        class="form-input w-full p-3 mt-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required
                        pattern="^[a-zA-Z0-9_]+$" title="Username can only contain letters, numbers, and underscores">
                    <p class="mt-1 text-xs text-gray-500">Only letters, numbers, and underscores allowed</p>
                </div>

                <div>
                    <label for="email" class="block text-xs font-medium text-gray-700">Email</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                        class="form-input w-full p-3 mt-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                </div>

                <div>
                    <label for="password" class="block text-xs font-medium text-gray-700">New Password (leave blank to keep current)</label>
                    <input type="password" name="password" id="password" minlength="8"
                        class="form-input w-full p-3 mt-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                    <p class="mt-1 text-xs text-gray-500">Minimum 8 characters</p>
                </div>

                <!-- Buttons -->
                <div class="flex flex-col sm:flex-row items-center justify-end gap-3 sm:gap-4 mt-8">
                    <button type="button" onclick="closeEditModal()"
                        class="flex items-center justify-center w-full sm:w-32 py-2 text-xs text-white bg-gray-500 rounded-md hover:bg-gray-600 transition-colors duration-300 focus:ring-2 focus:ring-gray-400 focus:outline-none">
                        <i class="mr-2 fa-solid fa-ban"></i> Cancel
                    </button>

                    <button type="submit"
                        class="flex items-center justify-center w-full sm:w-32 py-2 text-xs text-white bg-green-600 rounded-md hover:bg-green-700 transition-colors duration-300 focus:ring-2 focus:ring-green-500 focus:outline-none">
                        <i class="mr-2 fa-solid fa-save"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript for Modal Handling -->
    <script>
        // Form validation
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const nameField = document.getElementById('name');
            const usernameField = document.getElementById('username');
            const emailField = document.getElementById('email');
            const passwordField = document.getElementById('password');

            // Reset invalid styles
            [nameField, usernameField, emailField, passwordField].forEach(field => {
                field.classList.remove('border-red-500');
            });

            let isValid = true;

            // Check for empty required fields
            if (!nameField.value.trim()) {
                nameField.classList.add('border-red-500');
                isValid = false;
            }

            if (!usernameField.value.trim()) {
                usernameField.classList.add('border-red-500');
                isValid = false;
            }

            if (!emailField.value.trim()) {
                emailField.classList.add('border-red-500');
                isValid = false;
            }

            // Validate username format
            if (usernameField.value.trim() && !/^[a-zA-Z0-9_]+$/.test(usernameField.value.trim())) {
                usernameField.classList.add('border-red-500');
                isValid = false;
            }

            // Basic email validation
            if (emailField.value.trim() && !/\S+@\S+\.\S+/.test(emailField.value.trim())) {
                emailField.classList.add('border-red-500');
                isValid = false;
            }

            // Password length check (only if password field is not empty)
            if (passwordField.value.trim() && passwordField.value.length < 8) {
                passwordField.classList.add('border-red-500');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
                Swal.fire({
                    title: 'Validation Error',
                    text: 'Please check all fields and try again',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#047857'
                });
            }
        });

        function openEditModal() {
            const modal = document.getElementById('editModal');
            const modalContent = document.getElementById('modalContent');

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modalContent.classList.remove('modal-fade-out');
            modalContent.classList.add('modal-fade-in');

            // Set focus on first input for better accessibility
            setTimeout(() => {
                document.getElementById('name').focus();
            }, 300);

            // Close modal if clicking outside content
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeEditModal();
                }
            });

            // Add escape key listener
            document.addEventListener('keydown', handleEscKey);
        }

        function closeEditModal() {
            const modal = document.getElementById('editModal');
            const modalContent = document.getElementById('modalContent');

            modalContent.classList.remove('modal-fade-in');
            modalContent.classList.add('modal-fade-out');

            // Wait for animation to complete before hiding
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 280);

            // Remove escape key listener
            document.removeEventListener('keydown', handleEscKey);
        }

        function handleEscKey(e) {
            if (e.key === 'Escape') {
                closeEditModal();
            }
        }

        // If error message exists after form submission, show the modal again
        <?php if ($_SERVER['REQUEST_METHOD'] == 'POST' && $errorMessage): ?>
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(() => {
                    openEditModal();
                }, 500);
            });
        <?php endif; ?>

        // Show success message with SweetAlert if present
        <?php if ($successMessage): ?>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Success!',
                    text: '<?php echo addslashes($successMessage); ?>',
                    icon: 'success',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#047857',
                    timer: 3000,
                    timerProgressBar: true
                });
            });
        <?php endif; ?>
    </script>
</body>

</html>