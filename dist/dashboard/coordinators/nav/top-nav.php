<?php
// Include the database connection file
include '../dbcon.php';
/**
 * Fetches the coordinator's ID from the database.
 *
 * @param int $userId The ID of the logged-in user.
 * @return int|null Returns the coordinator's ID or null if not found.
 */
function fetchCoordinatorId($userId)
{
  global $conn;

  // Validate user ID
  if (!is_numeric($userId) || $userId <= 0) {
    return null;
  }

  // Fetch coordinator's ID
  $sql = "SELECT id FROM coordinators WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 0) {
    return null; // Coordinator not found
  }

  $row = $result->fetch_assoc();
  return $row['id'];
}

// Example usage
$userId = $_SESSION['user_id'] ?? 0; // Get the logged-in user's ID from the session
$coordinatorId = fetchCoordinatorId($userId);

if ($coordinatorId === null) {
  echo "Coordinator not found.";
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OJT Timesheet Monitoring</title>
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
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

    /* Glassmorphism SweetAlert2 */
    .swal2-popup.glass-popup {
      background: rgba(255, 255, 255, 0.8);
      backdrop-filter: blur(3px);
      border-radius: 15px;
      border: 1px solid rgba(255, 255, 255, 0.2);
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .swal2-title.glass-title {
      color: #1a365d;
      /* Dark blue for contrast */
    }

    .swal2-confirm.glass-confirm {
      background: rgba(34, 197, 94, 0.8);
      /* Green with transparency */
      backdrop-filter: blur(5px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: white;
      border-radius: 8px;
    }

    .swal2-cancel.glass-cancel {
      background: rgba(251, 146, 60, 0.8);
      /* Orange with transparency */
      backdrop-filter: blur(5px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: white;
      border-radius: 8px;
    }
  </style>
</head>

<body>
  <!-- Top Navigation Bar -->
  <nav id="topNav" class="fixed top-0 left-0 right-0 z-10 glass-nav">
    <div class="container flex items-center justify-between px-4 py-3 mx-auto">
      <div class="flex items-center space-x-2">
        <img src="../admindashboard/img/central-philippines-state-university-seeklogo 2.png" alt="Logo" class="w-10 h-10">
        <h1 class="text-lg font-bold text-green-900">OJT Timesheet Monitoring System</h1>
      </div>

      <div class="hidden space-x-6 md:flex">
        <a href="../coordinators/index.php" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-200 transition-all">
          <i class="mr-2 text-green-600 fas fa-tachometer-alt"></i> View Dashboard
        </a>
        <a href="../admindashboard/coordinator_traineelist.php" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-200 transition-all">
          <i class="mr-2 text-green-600 fas fa-users"></i> View Trainee
        </a>
        <div class="relative">
          <button id="profile-btn" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-200 transition-all">
            <i class="mr-2 text-green-600 fas fa-user-circle"></i> Profile
            <i class="w-4 h-4 ml-1 fas fa-chevron-down"></i>
          </button>
          <div id="profile-menu" class="absolute right-0 z-20 hidden w-48 mt-2 bg-white border rounded-md shadow-lg">
            <a href="coordinators_profile.php?id=<?php echo $coordinatorId; ?>" class="block px-4 py-2 text-gray-700 hover:bg-green-100">
              <i class="mr-2 fas fa-id-badge"></i> View Profile
            </a>
            <button id="logout-btn" class="block w-full px-4 py-2 text-left text-gray-700 hover:bg-green-100">
              <i class="mr-2 fas fa-sign-out-alt"></i> Logout
            </button>
          </div>
        </div>
      </div>

      <button id="menuToggle" class="mobile-menu-toggle md:hidden">
        <i class="text-xl text-gray-800 fas fa-bars"></i>
      </button>
    </div>
  </nav>

  <!-- Mobile Menu -->
  <div id="mobileMenu" class="glass-mobile-menu z-20">
    <a href="../coordinators/index.php" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-200 transition-all">
      <i class="mr-2 text-green-600 fas fa-tachometer-alt"></i> View Dashboard
    </a>
    <a href="../admindashboard/coordinator_traineelist.php" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-200 transition-all">
      <i class="mr-2 text-green-600 fas fa-users"></i> View Trainee
    </a>
    <a href="coordinators_profile.php?id=<?php echo $coordinatorId; ?>" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-200 transition-all">
      <i class="mr-2 text-green-600 fas fa-id-badge"></i> View Profile
    </a>
    <button id="mobile-logout-btn" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-200 transition-all">
      <i class="mr-2 text-green-600 fas fa-sign-out-alt"></i> Logout
    </button>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const profileMenu = document.getElementById('profile-menu');
      const profileBtn = document.getElementById('profile-btn');
      const logoutBtn = document.getElementById('logout-btn');
      const mobileLogoutBtn = document.getElementById('mobile-logout-btn');
      const menuToggle = document.getElementById('menuToggle');
      const mobileMenu = document.getElementById('mobileMenu');

      if (profileBtn && profileMenu) {
        profileBtn.addEventListener('click', (event) => {
          event.stopPropagation();
          profileMenu.classList.toggle('hidden');
        });

        document.addEventListener('click', (event) => {
          if (!profileMenu.contains(event.target) && !profileBtn.contains(event.target)) {
            profileMenu.classList.add('hidden');
          }
        });
      }

      function confirmLogout() {
        Swal.fire({
          title: "Are you sure?",
          text: "You will be logged out!",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#22C55E",
          cancelButtonColor: "#FB923C",
          confirmButtonText: "Yes, logout",
          cancelButtonText: "Cancel",
          customClass: {
            popup: 'glass-popup',
            title: 'glass-title',
            confirmButton: 'glass-confirm',
            cancelButton: 'glass-cancel'
          },
          backdrop: `
        rgba(255, 255, 255, 0.2)
        url("../admindashboard/img/central-philippines-state-university-seeklogo 2.png")
        center
        no-repeat
      `
        }).then((result) => {
          if (result.isConfirmed) {
            window.location.href = "../logout.php";
          }
        });
      }

      if (logoutBtn) logoutBtn.addEventListener("click", confirmLogout);
      if (mobileLogoutBtn) mobileLogoutBtn.addEventListener("click", confirmLogout);

      if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', (event) => {
          event.stopPropagation();
          mobileMenu.classList.toggle('active');
        });

        document.addEventListener('click', (event) => {
          if (!mobileMenu.contains(event.target) && !menuToggle.contains(event.target)) {
            mobileMenu.classList.remove('active');
          }
        });
      }
    });
  </script>
</body>

</html>