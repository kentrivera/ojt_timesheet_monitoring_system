<?php
session_start();

// Check if user is logged in and has 'Admin' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
  session_destroy(); // Destroy session if unauthorized
  header("Location: ../login.php?error=unauthorized_access");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../style.css">
</head>

<body class="bg-gray-100">

  <!-- Top Navigation -->
  <?php include 'nav/top-nav.php'; ?>

  <!-- Main Content -->
  <div class="flex">
    <!-- Sidebar Spacer -->
    <div class="hidden w-64 md:block"></div>

    <!-- Content -->
    <main class="flex-1 p-4">
      <!-- Cards Section -->
      <div class="grid grid-cols-1 gap-4 mt-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
        <?php
        // Include the database connection
        include '../dbcon.php';

        // Get the number of coordinators
        $coordinator_query = "SELECT COUNT(*) as total FROM coordinators";
        $coordinator_result = $conn->query($coordinator_query);
        $coordinator_count = $coordinator_result->fetch_assoc()['total'];

        // Get the number of trainees
        $trainee_query = "SELECT COUNT(*) as total FROM trainees";
        $trainee_result = $conn->query($trainee_query);
        $trainee_count = $trainee_result->fetch_assoc()['total'];

        // Get the number of agencies
        $agency_query = "SELECT COUNT(*) as total FROM agencies";
        $agency_result = $conn->query($agency_query);
        $agency_count = $agency_result->fetch_assoc()['total'];

        // Get the number of courses
        $course_query = "SELECT COUNT(*) as total FROM courses";
        $course_result = $conn->query($course_query);
        $course_count = $course_result->fetch_assoc()['total'];

        // Get the number of departments
        $department_query = "SELECT COUNT(*) as total FROM department";
        $department_result = $conn->query($department_query);
        $department_count = $department_result->fetch_assoc()['total'];

        // Get the number of school years
        $school_year_query = "SELECT COUNT(*) as total FROM school_years";
        $school_year_result = $conn->query($school_year_query);
        $school_year_count = $school_year_result->fetch_assoc()['total'];
        ?>

        <!-- Coordinators Card -->
        <div class="p-4 transition-transform duration-200 ease-out bg-white rounded shadow hover:scale-105">
          <a href="view_coordinators.php" class="block">
            <div class="flex items-center">
              <div class="flex-shrink-0 p-2 bg-blue-500 rounded">
                <i class="text-sm text-white fas fa-user-tie"></i>
              </div>
              <div class="ml-2">
                <h2 class="text-sm font-medium text-gray-700">Coordinators</h2>
                <p class="text-lg font-semibold text-gray-600"><?php echo $coordinator_count; ?></p>
              </div>
            </div>
          </a>
          <div class="mt-2 text-right">
            <a href="view_coordinators.php" class="text-xs font-medium text-blue-500 hover:underline">
              View All
            </a>
          </div>
        </div>

        <!-- Trainees Card -->
        <div class="p-4 transition-transform duration-200 ease-out bg-white rounded shadow hover:scale-105">
          <a href="traineelist.php" class="block">
            <div class="flex items-center">
              <div class="flex-shrink-0 p-2 bg-green-500 rounded">
                <i class="text-sm text-white fas fa-users"></i>
              </div>
              <div class="ml-2">
                <h2 class="text-sm font-medium text-gray-700">Trainees</h2>
                <p class="text-lg font-semibold text-gray-600"><?php echo $trainee_count; ?></p>
              </div>
            </div>
          </a>
          <div class="mt-2 text-right">
            <a href="traineelist.php" class="text-xs font-medium text-green-500 hover:underline">
              View All
            </a>
          </div>
        </div>

        <!-- Agencies Card -->
        <div class="p-4 transition-transform duration-200 ease-out bg-white rounded shadow hover:scale-105">
          <a href="manage-agency.php" class="block">
            <div class="flex items-center">
              <div class="flex-shrink-0 p-2 bg-purple-500 rounded">
                <i class="text-sm text-white fas fa-building"></i>
              </div>
              <div class="ml-2">
                <h2 class="text-sm font-medium text-gray-700">Agencies</h2>
                <p class="text-lg font-semibold text-gray-600"><?php echo $agency_count; ?></p>
              </div>
            </div>
          </a>
          <div class="mt-2 text-right">
            <a href="manage-agency.php" class="text-xs font-medium text-purple-500 hover:underline">
              View All
            </a>
          </div>
        </div>

        <!-- Courses Card -->
        <div class="p-4 transition-transform duration-200 ease-out bg-white rounded shadow hover:scale-105">
          <a href="manage-coursesanddepartment.php" class="block">
            <div class="flex items-center">
              <div class="flex-shrink-0 p-2 bg-yellow-500 rounded">
                <i class="text-sm text-white fas fa-graduation-cap"></i>
              </div>
              <div class="ml-2">
                <h2 class="text-sm font-medium text-gray-700">Courses</h2>
                <p class="text-lg font-semibold text-gray-600"><?php echo $course_count; ?></p>
              </div>
            </div>
          </a>
          <div class="mt-2 text-right">
            <a href="manage-coursesanddepartment.php" class="text-xs font-medium text-yellow-500 hover:underline">
              View All
            </a>
          </div>
        </div>

        <!-- Departments Card -->
        <div class="p-4 transition-transform duration-200 ease-out bg-white rounded shadow hover:scale-105">
          <a href="manage-coursesanddepartment.php" class="block">
            <div class="flex items-center">
              <div class="flex-shrink-0 p-2 bg-red-500 rounded">
                <i class="text-sm text-white fas fa-university"></i>
              </div>
              <div class="ml-2">
                <h2 class="text-sm font-medium text-gray-700">Departments</h2>
                <p class="text-lg font-semibold text-gray-600"><?php echo $department_count; ?></p>
              </div>
            </div>
          </a>
          <div class="mt-2 text-right">
            <a href="manage-coursesanddepartment.php" class="text-xs font-medium text-red-500 hover:underline">
              View All
            </a>
          </div>
        </div>

        <!-- School Years Card -->
        <div class="p-4 transition-transform duration-200 ease-out bg-white rounded shadow hover:scale-105">
          <a href="manage-school-year.php" class="block">
            <div class="flex items-center">
              <div class="flex-shrink-0 p-2 bg-teal-500 rounded">
                <i class="text-sm text-white fas fa-calendar-alt"></i>
              </div>
              <div class="ml-2">
                <h2 class="text-sm font-medium text-gray-700">School Years</h2>
                <p class="text-lg font-semibold text-gray-600"><?php echo $school_year_count; ?></p>
              </div>
            </div>
          </a>
          <div class="mt-2 text-right">
            <a href="manage-school-year.php" class="text-xs font-medium text-teal-500 hover:underline">
              View All
            </a>
          </div>
        </div>
      </div>
    </main>
  </div>

  <!-- JavaScript -->
  <script>
    // Sidebar toggle button functionality
    const menuToggle = document.querySelector('#menu-toggle');
    const sidebar = document.querySelector('#sidebar');

    if (menuToggle) {
      menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('-translate-x-full');
      });
    }
  </script>
</body>

</html>