<?php
// Start session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page if not logged in
    header("Location: ../login.php");
    exit();
}

// Include the database connection file
include '../dbcon.php';

// Function to handle saving and updating school years
function saveSchoolYear($data)
{
    global $conn;
    if (!empty($data['id'])) {
        // Update existing school year
        $query = "UPDATE school_years SET 
                    school_year = ?, 
                    description = ? 
                  WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssi", $data['school_year'], $data['description'], $data['id']);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    } else {
        // Insert new school year
        $query = "INSERT INTO school_years (school_year, description) 
                  VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $data['school_year'], $data['description']);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }
}

// Function to delete a school year
function deleteSchoolYear($id)
{
    global $conn;
    $query = "DELETE FROM school_years WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->affected_rows > 0;
}

// Handle form submission for saving or updating school years
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $data = [
        'id' => $_POST['id'],
        'school_year' => $_POST['school_year'],
        'description' => $_POST['description']
    ];

    $success = saveSchoolYear($data);
    echo json_encode(['success' => $success]);
    exit;
}

// Handle school year deletion
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $success = deleteSchoolYear($delete_id);
    echo json_encode(['success' => $success]);
    exit;
}

// Fetch all records from the school_years table in ascending order
$query = "SELECT * FROM school_years ORDER BY school_year ASC";
$result = mysqli_query($conn, $query);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../style.css">
    <title>School Year Management</title>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: '#4CAF50',
                            light: '#81C784',
                            dark: '#388E3C',
                            orange: '#FF9800',
                            
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .main-layout {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }

        .content-wrapper {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        .side-nav {
            width: 0;
            transition: width 0.3s ease;
        }

        .side-nav.open {
            width: 16rem;
        }

        .main-content {
            flex: 1;
            overflow-y: auto;
            transition: margin-left 0.3s ease;
        }

        @media (min-width: 768px) {
            .side-nav {
                width: 16rem;
            }
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="main-layout">
        <?php include 'nav/top-nav.php'; ?>
        <div class="content-wrapper">
            <div class="side-nav bg-primary-dark text-white shadow-lg">
                <!-- Sidebar content goes here -->
            </div>

            <div class="main-content p-4 md:p-6">
                <div class="bg-white rounded-lg shadow-md p-4 md:p-6">
                    <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-6">
                        <div class="mb-4 md:mb-0">
                            <h1 class="text-2xl md:text-3xl font-bold text-gray-800">School Years</h1>
                            <p class="text-gray-600 mt-1">Manage academic year records</p>
                        </div>
                        <button class="flex items-center px-4 py-2 text-white rounded transition-colors bg-primary hover:bg-primary-dark shadow-md" id="addSchoolYearBtn">
                            <i class="fas fa-plus"></i>
                            <span class="ml-2">Add School Year</span>
                        </button>
                    </div>

                    <div class="bg-white rounded-lg overflow-hidden shadow">
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm divide-y divide-gray-200">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="px-4 py-3 text-left font-medium text-gray-700">School Year</th>
                                        <th class="px-4 py-3 text-left font-medium text-gray-700">Description</th>
                                        <th class="px-4 py-3 text-center font-medium text-gray-700">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3"><?php echo $row['school_year']; ?></td>
                                            <td class="px-4 py-3"><?php echo $row['description']; ?></td>
                                            <td class="px-4 py-3">
                                                <div class="flex justify-center space-x-3">
                                                    <button class="text-primary-orange hover:text-primary-orange transition-colors editBtn"
                                                        data-id="<?php echo $row['id']; ?>"
                                                        data-school_year="<?php echo $row['school_year']; ?>"
                                                        data-description="<?php echo $row['description']; ?>">
                                                        <i class="fas fa-pencil-alt"></i>
                                                    </button>
                                                    <button class="text-red-500 hover:text-red-400 transition-colors deleteBtn" data-id="<?php echo $row['id']; ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="schoolYearModal" class="fixed inset-0 flex items-center justify-center hidden bg-black bg-opacity-50 z-50">
        <div class="w-full max-w-md p-6 bg-white rounded-lg shadow-xl mx-4">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-800" id="modalTitle">Add School Year</h2>
                <button type="button" class="text-gray-400 hover:text-gray-600" id="closeModalBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="schoolYearForm">
                <input type="hidden" id="schoolYearId" name="id">
                <div class="mb-4">
                    <label for="school_year" class="block text-sm font-medium text-gray-700 mb-1">School Year</label>
                    <input type="text" id="school_year" name="school_year" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" required>
                </div>
                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description (Optional)</label>
                    <input type="text" id="description" name="description" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 transition-colors" id="cancelBtn">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark transition-colors">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const addSchoolYearBtn = document.getElementById('addSchoolYearBtn');
        const schoolYearModal = document.getElementById('schoolYearModal');
        const cancelBtn = document.getElementById('cancelBtn');
        const closeModalBtn = document.getElementById('closeModalBtn');
        const schoolYearForm = document.getElementById('schoolYearForm');
        const modalTitle = document.getElementById('modalTitle');
        const schoolYearId = document.getElementById('schoolYearId');
        const schoolYearInput = document.getElementById('school_year');
        const descriptionInput = document.getElementById('description');
        const editBtns = document.querySelectorAll('.editBtn');
        const deleteBtns = document.querySelectorAll('.deleteBtn');

        addSchoolYearBtn.addEventListener('click', () => {
            modalTitle.textContent = 'Add School Year';
            schoolYearForm.reset();
            schoolYearId.value = '';
            schoolYearModal.classList.remove('hidden');
        });

        cancelBtn.addEventListener('click', () => {
            schoolYearModal.classList.add('hidden');
        });

        closeModalBtn.addEventListener('click', () => {
            schoolYearModal.classList.add('hidden');
        });

        editBtns.forEach(button => {
            button.addEventListener('click', () => {
                const id = button.dataset.id;
                const schoolYear = button.dataset.school_year;
                const description = button.dataset.description;

                modalTitle.textContent = 'Edit School Year';
                schoolYearId.value = id;
                schoolYearInput.value = schoolYear;
                descriptionInput.value = description;

                schoolYearModal.classList.remove('hidden');
            });
        });

        deleteBtns.forEach(button => {
            button.addEventListener('click', () => {
                const id = button.dataset.id;
                Swal.fire({
                    title: 'Delete School Year?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#4CAF50',
                    cancelButtonColor: '#FFC300', // Dark green instead of red
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`?delete_id=${id}`, {
                                method: 'GET'
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        title: 'Deleted!',
                                        text: 'The school year has been deleted.',
                                        icon: 'success',
                                        confirmButtonColor: '#4CAF50'
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: 'Failed to delete the school year.',
                                        icon: 'error',
                                        confirmButtonColor: '#4CAF50'
                                    });
                                }
                            })
                            .catch(error => {
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'An unexpected error occurred.',
                                    icon: 'error',
                                    confirmButtonColor: '#4CAF50'
                                });
                            });
                    }
                });
            });
        });

        schoolYearForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(schoolYearForm);
            formData.append('action', 'save');

            fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'School year has been saved successfully.',
                            icon: 'success',
                            confirmButtonColor: '#4CAF50'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Failed to save school year.',
                            icon: 'error',
                            confirmButtonColor: '#4CAF50'
                        });
                    }
                })
                .catch(() => {
                    Swal.fire({
                        title: 'Error!',
                        text: 'An unexpected error occurred.',
                        icon: 'error',
                        confirmButtonColor: '#4CAF50'
                    });
                });
        });

        // Toggle mobile menu (if needed)
        function toggleSidebar() {
            const sideNav = document.querySelector('.side-nav');
            sideNav.classList.toggle('open');
        }
    </script>
</body>

</html>