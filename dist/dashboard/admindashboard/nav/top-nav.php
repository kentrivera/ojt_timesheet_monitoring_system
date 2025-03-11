<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OJT Timesheet Monitoring</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    :root {
      --primary-color: #1a543f;
      --primary-light: #2c7a5d;
      --primary-dark: #0f3024;
      --accent-color: #fd7e14;
      --accent-light: #ff9642;
      --text-light: #f8f9fa;
      --text-dark: #212529;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .nav-item-hover:hover {
      background-color: rgba(255, 255, 255, 0.1);
      transform: translateY(-2px);
    }

    .dropdown-menu {
      transition: all 0.3s ease;
      visibility: hidden;
      opacity: 0;
      transform: translateY(10px);
    }

    .dropdown-menu.active {
      visibility: visible;
      opacity: 1;
      transform: translateY(0);
    }

    .sidebar {
      transition: all 0.3s ease;
    }

    @media (max-width: 768px) {
      .sidebar {
        transform: translateX(-100%);
      }

      .sidebar.open {
        transform: translateX(0);
      }
    }

    .sidebar-item {
      border-left: 3px solid transparent;
      transition: all 0.2s ease;
    }

    .sidebar-item:hover,
    .sidebar-item.active {
      border-left-color: var(--accent-color);
      background-color: rgba(255, 255, 255, 0.1);
    }

    .hamburger-line {
      width: 24px;
      height: 2px;
      background-color: var(--primary-color);
      transition: all 0.3s ease;
    }

    /* Submenu animation */
    .submenu {
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease;
    }

    .submenu.active {
      max-height: 500px;
    }

    /* Mobile bottom navigation */
    .mobile-nav-bottom {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background-color: white;
      box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
      z-index: 30;
      display: none;
    }

    @media (max-width: 768px) {
      .mobile-nav-bottom {
        display: flex;
      }

      body {
        padding-bottom: 60px;
      }

      #main-content {
        padding-bottom: 70px;
      }
    }
  </style>
</head>

<body class="bg-gray-100">
  <!-- Top Navigation Bar -->
  <header class="fixed top-0 left-0 right-0 z-30 bg-white shadow-md">
    <div class="px-4 mx-auto">
      <div class="flex items-center justify-between h-16">
        <!-- Logo and Mobile Menu Button -->
        <div class="flex items-center">
          <!-- Mobile Menu Toggle -->
          <button id="mobile-menu-btn" class="relative flex flex-col items-center justify-center w-10 h-10 mr-2 space-y-1.5 md:hidden">
            <div class="hamburger-line"></div>
            <div class="hamburger-line"></div>
            <div class="hamburger-line"></div>
          </button>

          <!-- Logo -->
          <div class="flex items-center">
            <img src="./img/central-philippines-state-university-seeklogo 2.png" alt="CPSU Logo" class="w-10 h-10">
            <h1 class="hidden ml-2 text-lg font-bold text-green-900 md:block md:text-xl">OJT Timesheet Monitoring</h1>
            <h1 class="ml-2 text-lg font-bold text-green-900 md:hidden">OJT Timesheet Monitoring System</h1>
          </div>
        </div>

        <!-- Desktop Navigation Menu -->
        <nav class="items-center hidden md:flex">
          <div class="flex items-center space-x-1">
            <!-- Dashboard -->
            <a href="index.php" class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 transition-all duration-200 rounded-md hover:text-green-900 hover:bg-green-50">
              <i class="w-5 h-5 mr-1.5 text-orange-500 fas fa-tachometer-alt"></i>
              <span>Dashboard</span>
            </a>

            <!-- Add Admin Button -->
            <a href="register.php" class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 transition-all duration-200 rounded-md hover:text-green-900 hover:bg-green-50">
              <i class="w-5 h-5 mr-1.5 text-orange-500 fas fa-user-plus"></i>
              <span>Add Admin</span>
            </a>

            <!-- Logout Button -->
            <button id="logout-btn" class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 transition-all duration-200 rounded-md hover:text-red-600 hover:bg-red-50">
              <i class="w-5 h-5 mr-1.5 text-orange-500 fas fa-sign-out-alt"></i>
              <span>Logout</span>
            </button>
          </div>
        </nav>

        <!-- Mobile Logout Button -->
        <div class="flex items-center md:hidden">
          <button id="mobile-logout-btn" class="p-2 rounded-full hover:bg-red-50">
            <i class="text-orange-500 fas fa-sign-out-alt text-xl"></i>
          </button>
        </div>
      </div>
    </div>
  </header>

  <!-- Sidebar -->
  <aside id="sidebar" class="fixed top-0 bottom-0 left-0 z-20 w-64 pt-16 pb-4 mt-0 overflow-y-auto transition-all duration-300 ease-in-out transform bg-green-900 sidebar">
    <!-- Sidebar Admin Info -->
    <a href="admin_profile.php">
      <div class="px-4 py-4 mb-4 border-b border-green-800 cursor-pointer hover:bg-green-900 transition duration-200">
        <div class="flex items-center">
          <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center text-orange-500">
            <i class="fas fa-user"></i>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-white">Admin User</p>
            <p class="text-xs text-green-200">Administrator</p>
          </div>
        </div>
      </div>
    </a>

    <!-- Sidebar Navigation -->
    <nav class="px-3">
      <h3 class="px-4 py-2 mb-2 text-xs font-semibold tracking-wider text-green-200 uppercase">Main Navigation</h3>

      <!-- Dashboard Link -->
      <a href="index.php" class="flex items-center px-4 py-2 mb-2 text-sm font-medium text-white transition-all duration-200 rounded-md sidebar-item hover:text-white">
        <i class="w-5 h-5 mr-3 text-orange-300 fas fa-tachometer-alt"></i>
        Dashboard
      </a>

      <!-- Coordinators Section -->
      <div class="mb-2">
        <a href="view_coordinators.php" class="flex items-center px-4 py-2 text-sm font-medium text-white transition-all duration-200 rounded-md sidebar-item hover:text-white">
          <i class="w-5 h-5 mr-3 text-orange-300 fas fa-users-cog"></i>
          View Coordinators
        </a>
      </div>

      <!-- Trainees Section -->
      <div class="mb-2">
        <a href="traineelist.php" class="flex items-center px-4 py-2 text-sm font-medium text-white transition-all duration-200 rounded-md sidebar-item hover:text-white">
          <i class="w-5 h-5 mr-3 text-orange-300 fas fa-user-graduate"></i>
          View Trainees
        </a>
      </div>

      <!-- Management Section -->
      <h3 class="px-4 py-2 mt-6 mb-2 text-xs font-semibold tracking-wider text-green-200 uppercase">Management</h3>

      <!-- Agency Management -->
      <a href="manage-agency.php" class="flex items-center px-4 py-2 mb-2 text-sm font-medium text-white transition-all duration-200 rounded-md sidebar-item hover:text-white">
        <i class="w-5 h-5 mr-3 text-orange-300 fas fa-building"></i>
        Manage Agency
      </a>

      <!-- School Year Management -->
      <a href="manage-school-year.php" class="flex items-center px-4 py-2 mb-2 text-sm font-medium text-white transition-all duration-200 rounded-md sidebar-item hover:text-white">
        <i class="w-5 h-5 mr-3 text-orange-300 fas fa-calendar-alt"></i>
        School Year
      </a>

      <!-- Courses Management -->
      <a href="manage-coursesanddepartment.php" class="flex items-center px-4 py-2 mb-2 text-sm font-medium text-white transition-all duration-200 rounded-md sidebar-item hover:text-white">
        <i class="w-5 h-5 mr-3 text-orange-300 fas fa-book"></i>
        Manage Courses
      </a>

      <!-- Logout in Sidebar -->
      <div class="mt-6">
        <button id="sidebar-logout-btn" class="flex items-center w-full px-4 py-2 mb-2 text-sm font-medium text-white transition-all duration-200 rounded-md sidebar-item hover:text-white hover:bg-red-800">
          <i class="w-5 h-5 mr-3 text-red-300 fas fa-sign-out-alt"></i>
          Logout
        </button>
      </div>
    </nav>

    <!-- Sidebar Footer -->
    <div class="absolute bottom-0 left-0 right-0 px-4 py-2 mt-4 text-xs text-center text-green-200 border-t border-green-800">
      <p>OJT Timesheet Monitoring System</p>
      <p>© 2025 CPSU Admin</p>
    </div>
  </aside>

  <!-- Mobile Bottom Navigation -->
  <div class="mobile-nav-bottom">
    <div class="grid w-full grid-cols-3 h-14">
      <a href="index.php" class="flex flex-col items-center justify-center text-green-900">
        <i class="mb-1 text-lg fas fa-tachometer-alt"></i>
        <span class="text-xs">Dashboard</span>
      </a>
      <a href="view_coordinators.php" class="flex flex-col items-center justify-center text-green-900">
        <i class="mb-1 text-lg fas fa-users-cog"></i>
        <span class="text-xs">Coordinators</span>
      </a>
      <a href="traineelist.php" class="flex flex-col items-center justify-center text-green-900">
        <i class="mb-1 text-lg fas fa-user-graduate"></i>
        <span class="text-xs">Trainees</span>
      </a>
    </div>
  </div>

  <!-- Mobile More Menu -->
  <div id="mobile-more-menu" class="fixed bottom-16 right-0 z-40 hidden w-56 py-2 mb-2 mr-2 bg-white rounded-lg shadow-lg dropdown-menu">
    <a href="register.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-green-50 hover:text-green-900">
      <i class="w-5 h-5 mr-2 text-orange-500 fas fa-user-plus"></i>
      Add New Admin
    </a>
    <a href="manage-agency.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-green-50 hover:text-green-900">
      <i class="w-5 h-5 mr-2 text-orange-500 fas fa-building"></i>
      Manage Agency
    </a>
    <a href="manage-school-year.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-green-50 hover:text-green-900">
      <i class="w-5 h-5 mr-2 text-orange-500 fas fa-calendar-alt"></i>
      School Year
    </a>
    <a href="manage-coursesanddepartment.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-green-50 hover:text-green-900">
      <i class="w-5 h-5 mr-2 text-orange-500 fas fa-book"></i>
      Manage Courses
    </a>
    <div class="my-1 border-t border-gray-200"></div>
    <a href="view-profile.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-green-50 hover:text-green-900">
      <i class="w-5 h-5 mr-2 text-orange-500 fas fa-id-badge"></i>
      View Profile
    </a>
    <button id="more-logout-btn" class="flex items-center w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-red-50 hover:text-red-600">
      <i class="w-5 h-5 mr-2 text-orange-500 fas fa-sign-out-alt"></i>
      Logout
    </button>
  </div>

  <!-- Main Content -->
  <main id="main-content" class="pt-16 transition-all duration-300 ease-in-out md:ml-64">
    <div class="p-4 md:p-6">
      <!-- Content placeholder -->
      <div class="p-6 bg-white rounded-lg shadow-md">
        <h2 class="mb-4 text-xl font-semibold text-green-900">Welcome to Admin Dashboard</h2>
        <p class="text-gray-600">Manage OJT trainees, coordinators, and other system settings from this admin dashboard.</p>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Mobile sidebar toggle
      const mobileMenuBtn = document.getElementById('mobile-menu-btn');
      const sidebar = document.getElementById('sidebar');
      const mainContent = document.getElementById('main-content');
      const hamburgerLines = mobileMenuBtn.querySelectorAll('.hamburger-line');

      mobileMenuBtn.addEventListener('click', function() {
        sidebar.classList.toggle('open');

        // Toggle hamburger to X animation
        if (sidebar.classList.contains('open')) {
          hamburgerLines[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
          hamburgerLines[1].style.opacity = '0';
          hamburgerLines[2].style.transform = 'rotate(-45deg) translate(5px, -5px)';
        } else {
          hamburgerLines[0].style.transform = 'none';
          hamburgerLines[1].style.opacity = '1';
          hamburgerLines[2].style.transform = 'none';
        }
      });

      // Function to setup dropdown behavior
      function setupDropdown(btnId, menuId) {
        const btn = document.getElementById(btnId);
        const menu = document.getElementById(menuId);

        if (!btn || !menu) return;

        btn.addEventListener('click', function(e) {
          e.stopPropagation();
          menu.classList.toggle('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
          if (!btn.contains(e.target) && !menu.contains(e.target)) {
            menu.classList.remove('active');
          }
        });
      }

      // Setup mobile more dropdown
      setupDropdown('mobile-more-btn', 'mobile-more-menu');

      // Logout functionality
      function setupLogoutButton(btnId) {
        const btn = document.getElementById(btnId);
        if (!btn) return;

        btn.addEventListener('click', function() {
          Swal.fire({
            title: 'Logout',
            text: 'Are you sure you want to logout?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#1a543f',
            cancelButtonColor: '#fd7e14',
            confirmButtonText: 'Yes, logout',
            cancelButtonText: 'Cancel',
            borderRadius: '10px'
          }).then((result) => {
            if (result.isConfirmed) {
              window.location.href = '../logout.php';
            }
          });
        });
      }

      // Setup all logout buttons
      setupLogoutButton('logout-btn');
      setupLogoutButton('mobile-logout-btn');
      setupLogoutButton('sidebar-logout-btn');
      setupLogoutButton('more-logout-btn');

      // Set active menu item based on current page
      function setActiveMenuItem() {
        const currentPage = window.location.pathname.split('/').pop();
        const sidebarItems = document.querySelectorAll('.sidebar-item');
        const mobileNavItems = document.querySelectorAll('.mobile-nav-bottom a');

        sidebarItems.forEach(item => {
          const href = item.getAttribute('href');
          if (href && href === currentPage) {
            item.classList.add('active');
            item.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
            item.style.borderLeftColor = '#fd7e14';
          }
        });

        mobileNavItems.forEach(item => {
          const href = item.getAttribute('href');
          if (href === currentPage) {
            item.classList.add('text-orange-500');
            item.classList.remove('text-green-900');
          }
        });
      }

      setActiveMenuItem();

      // Handle tap/click outside to close mobile sidebar and menus
      document.addEventListener('touchstart', function(e) {
        const mobileMoreMenu = document.getElementById('mobile-more-menu');
        const mobileMoreBtn = document.getElementById('mobile-more-btn');

        if (mobileMoreMenu && mobileMoreMenu.classList.contains('active') &&
          !mobileMoreMenu.contains(e.target) && !mobileMoreBtn.contains(e.target)) {
          mobileMoreMenu.classList.remove('active');
        }

        if (sidebar.classList.contains('open') &&
          !sidebar.contains(e.target) &&
          !mobileMenuBtn.contains(e.target)) {
          sidebar.classList.remove('open');
          hamburgerLines[0].style.transform = 'none';
          hamburgerLines[1].style.opacity = '1';
          hamburgerLines[2].style.transform = 'none';
        }
      });
    });
  </script>
</body>

</html>