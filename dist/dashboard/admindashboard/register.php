<?php
include '../dbcon.php';
$errorMessage = ''; // Variable to store the error message

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $name = $_POST['name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Sanitize inputs to prevent SQL Injection
    $name = $conn->real_escape_string($name);
    $username = $conn->real_escape_string($username);
    $email = $conn->real_escape_string($email);
    $password = $conn->real_escape_string($password);

    // Hash the password for secure storage
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Set the default role as Admin
    $role = 'Admin';

    // Check if the username or email already exists
    $checkQuery = "SELECT * FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // If there are results, it means the username or email is taken
        $errorMessage = 'Error: Username or Email is already taken.';
    } else {
        // Insert into users table if username and email are unique
        $insertQuery = "INSERT INTO users (name, username, email, password, user_role) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("sssss", $name, $username, $email, $hashed_password, $role);

        if ($stmt->execute()) {
            // Registration successful
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
            echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'User registered successfully!',
                            confirmButtonColor: '#28A745',
                        }).then(() => {
                            window.location.href = 'register.php'; // Redirect to login page
                        });
                    });
                  </script>";
        } else {
            // Error occurred
            $errorMessage = 'An error occurred while registering the user.';
        }
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <title>Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Custom styles for responsive behavior */
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
                /* Wider container for larger screens */
            }
        }

        /* Animation for form interactions */
        .form-input:focus {
            transform: translateY(-2px);
            transition: transform 0.3s ease;
        }

        /* Ensuring content doesn't overlap with navbar */
        .main-content {
            padding-top: 5rem;
            /* Adjust based on your navbar height */
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
                    <h2 class="text-xl font-semibold text-center text-white">New Admin Registration</h2>
                </div>

                <!-- Form Container -->
                <div class="p-6 sm:p-8 md:p-10">
                    <!-- Error message display -->
                    <?php if ($errorMessage): ?>
                        <div class="p-3 mb-5 text-xs text-red-600 bg-red-100 rounded-md"><?php echo $errorMessage; ?></div>
                    <?php endif; ?>

                    <form action="register.php" method="POST" class="space-y-6 sm:space-y-8">
                        <div>
                            <label for="name" class="block text-xs font-medium text-gray-700">Name</label>
                            <input type="text" name="name" id="name"
                                class="form-input w-full p-4 mt-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                        </div>

                        <div>
                            <label for="username" class="block text-xs font-medium text-gray-700">Username</label>
                            <input type="text" name="username" id="username"
                                class="form-input w-full p-4 mt-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                        </div>

                        <div>
                            <label for="email" class="block text-xs font-medium text-gray-700">Email</label>
                            <input type="email" name="email" id="email"
                                class="form-input w-full p-4 mt-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                        </div>

                        <div>
                            <label for="password" class="block text-xs font-medium text-gray-700">Password</label>
                            <input type="password" name="password" id="password"
                                class="form-input w-full p-4 mt-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                        </div>

                        <!-- Buttons - Smaller and with switched position -->
                        <div class="flex flex-col sm:flex-row items-center justify-end gap-4 sm:gap-6 mt-8">
                            <!-- Cancel Button (now first) -->
                            <a href="index.php"
                                class="flex items-center justify-center w-full sm:w-32 py-2 text-xs text-white bg-orange-600 rounded-md hover:bg-orange-700 transition-colors duration-300">
                                <i class="mr-2 fa-solid fa-ban"></i> Cancel
                            </a>

                            <!-- Register Button (now second) -->
                            <button type="submit"
                                class="flex items-center justify-center w-full sm:w-32 py-2 text-xs text-white bg-green-600 rounded-md hover:bg-green-700 transition-colors duration-300">
                                <i class="mr-2 fa-solid fa-user-plus"></i> Register
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>