<?php
// Only start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Redirect to login if not authenticated
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}
$user_id = $_SESSION['id'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Glassmorphism Navbar */
        .glass-nav {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out;
        }

        .glass-nav.hidden {
            transform: translateY(-100%);
            opacity: 0;
        }

        /* Slide-out Mobile Menu */
        .glass-mobile-menu {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(12px);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            transform: translateX(-100%);
            transition: transform 0.3s ease-in-out;
            height: 100vh;
            width: 280px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            padding-top: 20px;
            position: fixed;
        }

        .glass-mobile-menu.active {
            transform: translateX(0);
        }

        /* Mobile Menu Button */
        .mobile-menu-toggle {
            display: none;
        }

        .mobile-menu-toggle.hidden {
            display: none !important;
        }

        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: flex;
                position: fixed;
                top: 10px;
                right: 5px;
                width: 45px;
                height: 45px;
                align-items: center;
                justify-content: center;
                transition: background 0.3s ease-in-out;
                z-index: 60;
            }

            .mobile-menu-toggle:hover {
                background: rgba(255, 255, 255, 0.3);
            }
        }

        /* Smooth transitions for dropdown */
        .dropdown-content {
            transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
            opacity: 0;
            transform: translateY(-10px);
        }

        .dropdown-content.show {
            opacity: 1;
            transform: translateY(0);
        }

        .glass-alert {
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(8px);
        }

        .glass-title {
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .glass-content {
            margin-top: 10px;
        }

        .glass-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }

        .glass-confirm {
            background: rgba(40, 167, 69, 0.8);
            /* Changed to green */
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .glass-cancel {
            background: rgba(255, 126, 0, 0.8);
            /* Changed to orange */
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .glass-confirm:hover,
        .glass-cancel:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <!-- Top Navigation Bar -->
    <nav id="topNav" class="fixed top-0 left-0 right-0 z-50 glass-nav">
        <div class="container flex items-center justify-between px-4 py-3 mx-auto">
            <!-- Left: Logo and Title -->
            <div class="flex items-center space-x-2">
                <img src="../admindashboard/img/central-philippines-state-university-seeklogo 2.png" alt="Logo" class="w-10 h-10">
                <h1 class="text-lg font-bold text-green-900">OJT Timesheet Monitoring System</h1>
            </div>

            <!-- Right: Desktop Menu -->
            <div class="hidden space-x-6 md:flex items-center">
                <a href="index.php" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-200 transition-all">
                    <i class="mr-2 text-green-600 fas fa-home"></i> Dashboard
                </a>
                <!-- Right: Profile Dropdown -->
                <div class="relative">
                    <button id="profileDropdown" class="flex items-center space-x-2 px-3 py-2 rounded-lg hover:bg-green-100">
                        <i class="fas fa-user-circle text-green-600"></i> <!-- Profile Icon -->
                        <span>Profile</span>
                        <i class="fas fa-caret-down text-green-600"></i> <!-- Dropdown Icon -->
                    </button>


                    <!-- Dropdown Menu -->
                    <div id="dropdownContent" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 dropdown-content hidden">

                        <!-- Header -->
                        <div class="px-4 py-3 border-b border-gray-200">
                            <p class="text-sm font-medium text-gray-600">Go to</p>
                        </div>

                        <!-- Menu Items -->
                        <a href="view_profile.php?id=<?php echo $user_id; ?>" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100 transition-all">
                            <i class="mr-3 fas fa-user text-blue-600"></i> View Profile
                        </a>

                        <a href="view_timesheet.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-green-100 transition-all">
                            <i class="mr-3 text-green-600 fas fa-calendar-alt"></i> View Timesheet
                        </a>

                        <a href="#" id="logoutBtn" class="flex items-center px-4 py-3 text-red-600 hover:bg-red-100 transition-all">
                            <i class="mr-3 fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </nav>

    <!-- Slide-out Mobile Menu -->
    <aside id="mobileMenu" class="fixed inset-y-0 left-0 z-40 glass-mobile-menu shadow-lg md:hidden">
        <!-- Mobile Menu Items -->
        <div class="mt-4 space-y-1 text-gray-800 px-2">
            <a href="index.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-green-200 transition-all">
                <i class="mr-3 text-green-600 fas fa-home w-5 text-center"></i> Dashboard
            </a>
            <!-- Menu Items -->
            <a href="view_profile.php?id=<?php echo $user_id; ?>" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100 transition-all">
                <i class="mr-3 fas fa-user text-blue-500"></i> View Profile
            </a>
            <a href="view_timesheet.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-green-200 transition-all">
                <i class="mr-3 text-green-600 fas fa-calendar-alt w-5 text-center"></i> View Timesheet
            </a>

            <div class="pt-4 mt-4 border-t border-gray-200">
                <a href="#" id="mobileLogoutBtn" class="flex items-center px-4 py-3 text-red-600 rounded-lg hover:bg-red-100 transition-all">
                    <i class="mr-3 fas fa-sign-out-alt w-5 text-center"></i> Logout
                </a>
            </div>
        </div>
    </aside>

    <!-- Mobile Menu Button (Hides on Click) -->
    <button id="menuToggle" class="mobile-menu-toggle md:hidden">
        <i class="text-xl text-gray-800 fas fa-bars"></i>
    </button>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const menuToggle = document.getElementById('menuToggle');
            const mobileMenu = document.getElementById('mobileMenu');
            const topNav = document.getElementById('topNav');
            const logoutBtn = document.getElementById('logoutBtn');
            const mobileLogoutBtn = document.getElementById('mobileLogoutBtn');
            const profileDropdown = document.getElementById('profileDropdown');
            const dropdownContent = document.getElementById('dropdownContent');

            // Open mobile menu: Hide nav and hamburger button
            menuToggle.addEventListener('click', (event) => {
                event.stopPropagation(); // Prevents immediate closing
                mobileMenu.classList.add('active');
                topNav.classList.add('hidden');
                menuToggle.classList.add('hidden'); // Hide hamburger button
            });

            // Close mobile menu when clicking outside
            document.addEventListener('click', (event) => {
                if (!mobileMenu.contains(event.target) && !menuToggle.contains(event.target)) {
                    mobileMenu.classList.remove('active');
                    topNav.classList.remove('hidden');
                    menuToggle.classList.remove('hidden');
                }
            });

            // Prevent closing menu when clicking inside it
            mobileMenu.addEventListener('click', (event) => {
                event.stopPropagation();
            });

            // Toggle profile dropdown with smooth transition
            profileDropdown.addEventListener('click', (event) => {
                event.stopPropagation();
                dropdownContent.classList.toggle('hidden');
                dropdownContent.classList.toggle('show');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', (event) => {
                if (!profileDropdown.contains(event.target)) {
                    dropdownContent.classList.add('hidden');
                    dropdownContent.classList.remove('show');
                }
            });

            // Logout Confirmation with Glassmorphism & Proper Button Layout
            function confirmLogout() {
                Swal.fire({
                    title: "Are you sure?",
                    text: "You will be logged out!",
                    icon: "warning",
                    background: "rgba(255, 255, 255, 0.2)",
                    backdropFilter: "blur(10px)", // Added for true glassmorphism effect
                    color: "#ffffff",
                    backdrop: `
            linear-gradient(135deg, #4ade80, #3b82f6)
            backdrop-filter: blur(10px)
        `,
                    showCancelButton: true,
                    customClass: {
                        popup: "glass-alert",
                        title: "glass-title",
                        content: "glass-content",
                        actions: "glass-actions",
                        confirmButton: "glass-confirm",
                        cancelButton: "glass-cancel"
                    },
                    buttonsStyling: false,
                    showClass: {
                        popup: "animate__animated animate__fadeInDown"
                    },
                    hideClass: {
                        popup: "animate__animated animate__fadeOutUp"
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = "../logout.php";
                    }
                });
            }

            // Attach logout event to both buttons
            logoutBtn.addEventListener('click', confirmLogout);
            mobileLogoutBtn.addEventListener('click', confirmLogout);
        });
    </script>
</body>

</html>