
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - OJT Timesheet Monitoring</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css"> <!-- Keep the external CSS file reference -->
    <link rel="stylesheet" href="../style.css"> <!-- Keep the external CSS file reference -->
    <style>
        body {
            /* Light green to blue gradient */
            background: linear-gradient(135deg, #84cc16, #0ea5e9);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .glass {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .input-container {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-container input {
            width: 100%;
            padding: 0.75rem;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .input-container input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 8px rgba(74, 222, 128, 0.4);
        }

        .input-container label {
            position: absolute;
            left: 0.75rem;
            top: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
            pointer-events: none;
            transition: all 0.3s ease;
        }

        .input-container input:focus~label,
        .input-container input:not(:placeholder-shown)~label {
            transform: translateY(-1.4rem);
            font-size: 0.7rem;
            color: #4ade80;
        }

        /* Change icons to green-700 color */
        .input-icon {
            position: absolute;
            right: 0.75rem;
            top: 0.75rem;
            color: #15803d;
            /* Green-700 color */
            transition: opacity 0.3s ease;
        }

        /* Hide icons when input is focused or has content */
        .input-container input:focus~.input-icon,
        .input-container input:not(:placeholder-shown)~.input-icon {
            opacity: 0;
        }

        .logo-container {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .logo {
            width: 80px;
            height: auto;
        }

        .tab-container {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .tab {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            margin: 0.25rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Change tab icons to green-700 as well */
        .tab i {
            color: #15803d;
        }

        .tab.active {
            background: linear-gradient(90deg, #84cc16, #0ea5e9);
            color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* Keep tab icons white when tab is active */
        .tab.active i {
            color: white;
        }

        .login-btn {
            background: linear-gradient(90deg, #84cc16, #0ea5e9);
            border: none;
            transition: all 0.3s ease;
            border-radius: 8px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .error-message {
            background-color: rgba(255, 87, 51, 0.2);
            border-left: 4px solid #ff5733;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-radius: 4px;
            font-size: 0.85rem;
            color: #fff;
        }

        /* Improved mobile responsiveness */
        @media (max-width: 480px) {
            .glass {
                padding: 1rem;
                width: 100%;
                margin: 0.5rem;
            }

            h2 {
                font-size: 1.5rem;
            }

            .logo {
                width: 60px;
            }

            .tab {
                padding: 0.4rem 0.8rem;
                font-size: 0.8rem;
            }

            .input-container {
                margin-bottom: 1.2rem;
            }

            .input-container input {
                padding: 0.6rem;
                font-size: 0.85rem;
            }

            .input-container label {
                font-size: 0.85rem;
            }

            .login-btn {
                padding: 0.6rem 0;
            }
        }
    </style>
</head>

<body class="flex items-center justify-center min-h-screen p-2">
    <div class="w-full max-w-sm sm:max-w-md p-4 sm:p-5 glass text-center">
        <!-- Logo Section -->
        <div class="logo-container">
            <img src="admindashboard/img/central-philippines-state-university-seeklogo 2.png" class="logo" alt="CPSU Logo">
        </div>
        <h2 class="text-2xl sm:text-3xl font-semibold text-white mb-2">OJT Timesheet Monitoring System</h2>

        <!-- Role Selection Tabs -->
        <div class="tab-container">
            <div class="tab active" data-role="trainee">
                <i class="fas fa-user-graduate mr-1 sm:mr-2"></i>Trainee
            </div>
            <div class="tab" data-role="coordinator">
                <i class="fas fa-user-tie mr-1 sm:mr-2"></i>Coordinator
            </div>
            <div class="tab" data-role="Admin">
                <i class="fas fa-user-shield mr-1 sm:mr-2"></i>Admin
            </div>
        </div>

        <!-- Error Message Display -->
        <?php
        if (isset($_GET['error'])) {
            $errorMessage = match ($_GET['error']) {
                'invalid_password' => 'Invalid password, please try again.',
                'user_not_found' => 'User not found, please check your username.',
                'invalid_role' => 'Invalid role selected, please choose again.',
                'invalid_request' => 'Invalid request method, please try again later.',
                default => 'An unknown error occurred.',
            };
            echo '<div class="error-message"><i class="fas fa-exclamation-circle mr-2"></i>' . htmlspecialchars($errorMessage) . '</div>';
        }
        ?>

        <!-- Login Form -->
        <form action="loginAuth.php" method="POST" class="space-y-3 sm:space-y-4 m-3 sm:m-5">
            <input type="hidden" name="role" id="selected-role" value="trainee">

            <div class="input-container">
                <input type="text" name="username" id="username" placeholder=" " required>
                <label for="username">Username (Email or Student ID)</label>
                <i class="fas fa-user input-icon"></i>
            </div>

            <div class="input-container">
                <input type="password" name="password" id="password" placeholder=" " required>
                <label for="password">Password</label>
                <i class="fas fa-lock input-icon"></i>
            </div>

            <button type="submit" class="w-full py-2 sm:py-3 text-white login-btn font-semibold">
                <i class="fas fa-sign-in-alt mr-2"></i>Login
            </button>
        </form>

        <script>
            // Handle role selection with tabs
            const tabs = document.querySelectorAll('.tab');
            const roleInput = document.getElementById('selected-role');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    // Remove active class from all tabs
                    tabs.forEach(t => t.classList.remove('active'));

                    // Add active class to clicked tab
                    tab.classList.add('active');

                    // Set the role value
                    roleInput.value = tab.dataset.role;
                });
            });
        </script>
    </div>
</body>

</html>