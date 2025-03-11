<?php
// Start session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Include the database connection file
include '../dbcon.php';

// Fetch departments for the dropdown
$departments = [];
$deptQuery = "SELECT department_id, department_name FROM department";
$deptResult = mysqli_query($conn, $deptQuery);
if ($deptResult) {
    while ($row = mysqli_fetch_assoc($deptResult)) {
        $departments[] = $row;
    }
}

// Handle form submission to add a course
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_course'])) {
    $course_name = $_POST['course_name'];
    $course_description = !empty($_POST['course_description']) ? $_POST['course_description'] : null;
    $department_id = $_POST['department_id'];

    $insertQuery = "INSERT INTO courses (course_name, course_description, department_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("ssi", $course_name, $course_description, $department_id);

    if ($stmt->execute()) {
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Course Added',
                text: 'The course has been successfully added.',
                confirmButtonColor: '#4CAF50'
            }).then(() => {
                window.location.href = window.location.href; // Reload the page
            });
        </script>";
    } else {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to add the course. Please try again.',
                confirmButtonColor: '#d33'
            });
        </script>";
    }
}

// Handle course deletion
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $deleteQuery = "DELETE FROM courses WHERE id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $delete_id);

    if ($stmt->execute()) {
        echo "<script>
            Swal.fire({
                title: '<span style=\"font-size: 0.9rem;\">Course Deleted</span>',
                html: '<span style=\"font-size: 0.8rem;\">The course has been successfully deleted.</span>',
                icon: 'success',
                confirmButtonColor: '#4CAF50', // Green
                confirmButtonText: '<span style=\"font-size: 0.8rem;\">OK</span>'
            }).then(() => {
                window.location.href = 'courses.php';
            });
        </script>";
    } else {
        echo "<script>
            Swal.fire({
                title: '<span style=\"font-size: 0.9rem;\">Error</span>',
                html: '<span style=\"font-size: 0.8rem;\">Failed to delete the course. Please try again.</span>',
                icon: 'error',
                confirmButtonColor: '#d33',
                confirmButtonText: '<span style=\"font-size: 0.8rem;\">OK</span>'
            }).then(() => {
                window.location.href = 'courses.php';
            });
        </script>";
    }
}

// Fetch courses with department names
$query = "SELECT courses.id, courses.course_name, courses.course_description, 
                 courses.department_id, department.department_name 
          FROM courses 
          JOIN department ON courses.department_id = department.department_id";
$result = $conn->query($query);

$courses = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row; // Populate the $courses array
    }
}

// Function to handle saving a department
function saveDepartment($department_name)
{
    global $conn;
    $query = "INSERT INTO department (department_name) VALUES (?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $department_name);
    $stmt->execute();
    return $stmt->affected_rows > 0;
}

// Handle form submission for saving a department
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_department') {
    $department_name = $_POST['department_name'];

    $success = saveDepartment($department_name);
    echo json_encode(['success' => $success]);
    exit;
}

// Fetch all departments from the department table
$queryDepartments = "SELECT * FROM department";
$resultDepartments = mysqli_query($conn, $queryDepartments);

// Handle department update request
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['id'], $data['name'])) {
    $departmentId = $data['id'];
    $departmentName = $data['name'];

    $query = "UPDATE department SET department_name = ? WHERE department_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $departmentName, $departmentId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update department.']);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_course'])) {
    // Debug: Print submitted data
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";

    $course_id = $_POST['course_id'];
    $course_name = $_POST['course_name'];
    $course_description = !empty($_POST['course_description']) ? $_POST['course_description'] : null;
    $department_id = $_POST['department_id'];

    // Debug: Print the SQL query
    $updateQuery = "UPDATE courses SET course_name = ?, course_description = ?, department_id = ? WHERE id = ?";
    echo "Query: $updateQuery<br>";
    echo "Params: $course_name, $course_description, $department_id, $course_id<br>";

    $stmt = $conn->prepare($updateQuery);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ssii", $course_name, $course_description, $department_id, $course_id);

    if ($stmt->execute()) {
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Course Updated',
                text: 'The course has been successfully updated.',
                confirmButtonColor: '#4CAF50'
            }).then(() => {
                window.location.href = window.location.href; // Reload the page
            });
        </script>";
    } else {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to update the course. Please try again.',
                confirmButtonColor: '#d33'
            });
        </script>";
    }
}
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
    <title>Courses and Departments Management</title>
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
            width: 16rem;
        }

        .main-content {
            flex: 1;
            overflow-y: auto;
            padding-top: 6%;
        }

        .text-small {
            font-size: 0.875rem;
        }

        .bg-green-custom {
            background-color: #4CAF50;
        }

        .bg-blue-custom {
            background-color: #2196F3;
        }

        .btn-small {
            font-size: 0.75rem;
            padding: 5px 10px;
            min-width: 80px;
            height: 30px;
        }

        .border-table {
            border: 1px solid #ddd;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        /* Responsive tables */
        .table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }

            .content-wrapper {
                flex-direction: column;
            }

            .side-nav {
                width: 100%;
                height: auto;
            }
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="main-layout">
        <?php include 'nav/top-nav.php'; ?>
        <div class="content-wrapper">
            <div class="text-white bg-gray-800 side-nav">
                <!-- Side navigation included from original design -->
            </div>
            <div class="main-content p-0 m-5">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- Courses Section -->
                    <div class="p-4 bg-white rounded-lg shadow-md border-table">
                        <div class="flex flex-wrap items-center justify-between mb-4">
                            <h1 class="text-2xl font-bold text-small">Courses</h1>
                            <button id="addCourseBtn" class="px-4 py-2 text-white bg-green-500 rounded hover:bg-green-600 transition-colors">
                                <i class="fas fa-plus mr-1"></i> Add Course
                            </button>
                        </div>

                        <!-- Course Table -->
                        <div class="table-wrapper">
                            <table class="w-full bg-white border border-gray-200 rounded">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Course Name</th>
                                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Department</th>
                                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase hidden md:table-cell">Description</th>
                                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($courses as $course) : ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-2 text-sm text-gray-900"><?= $course['course_name']; ?></td>
                                            <td class="px-4 py-2 text-sm text-gray-900"><?= $course['department_name']; ?></td>
                                            <td class="px-4 py-2 text-sm text-gray-900 hidden md:table-cell"><?= $course['course_description'] ?? 'N/A'; ?></td>
                                            <td class="px-4 py-2 text-center">
                                                <!-- Updated Edit Button -->
                                                <button class="text-orange-500 btn-icon edit-course-btn"
                                                    data-id="<?= $course['id']; ?>"
                                                    data-course-name="<?= $course['course_name']; ?>"
                                                    data-department-id="<?= $course['department_id']; ?>"
                                                    data-course-description="<?= $course['course_description'] ?? ''; ?>">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </button>

                                                <button class="text-red-500 btn-icon" onclick="confirmDeleteCourse(<?= $course['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Edit Course Modal -->
                    <div id="editCourseModal" class="modal">
                        <div class="modal-content">
                            <h2 class="text-xl font-bold">Edit Course</h2>
                            <form id="editCourseForm" method="POST" action="courses.php">
                                <input type="hidden" name="course_id" id="edit_course_id">
                                <div class="mt-4">
                                    <label for="edit_course_name" class="block text-sm font-medium text-gray-600">Course Name</label>
                                    <input type="text" name="course_name" id="edit_course_name" required class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                </div>
                                <div class="mt-4">
                                    <label for="edit_department_id" class="block text-sm font-medium text-gray-600">Department</label>
                                    <select name="department_id" id="edit_department_id" required class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                        <option value="" disabled selected>Select a department</option>
                                        <?php foreach ($departments as $department) : ?>
                                            <?php if ($department['department_name'] != 'University Wide') : ?> <!-- Hide by value name -->
                                                <option value="<?= $department['department_id'] ?>"><?= $department['department_name'] ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mt-4">
                                    <label for="edit_course_description" class="block text-sm font-medium text-gray-600">Course Description (Optional)</label>
                                    <textarea name="course_description" id="edit_course_description" class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"></textarea>
                                </div>
                                <div class="flex justify-end mt-6">
                                    <button type="button" id="closeEditModal" class="px-4 py-2 mr-2 text-white bg-gray-500 rounded-md hover:bg-gray-600">Cancel</button>
                                    <button type="submit" name="update_course" class="px-4 py-2 text-white bg-green-500 rounded-md hover:bg-green-600">Update</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Departments Section -->
                    <div class="p-4 bg-white rounded-lg shadow-md border-table">
                        <div class="flex flex-wrap items-center justify-between mb-4">
                            <h1 class="text-2xl font-bold text-small">Departments</h1>
                            <button id="addDepartmentBtn" class="px-4 py-2 text-white bg-green-500 rounded hover:bg-green-600 transition-colors">
                                <i class="fas fa-plus mr-1"></i> Add Department
                            </button>
                        </div>

                        <div class="table-wrapper">
                            <table class="w-full bg-white border border-gray-200 rounded">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                            Department Name
                                        </th>
                                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">
                                            Action
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($resultDepartments) > 0) : ?>
                                        <?php while ($row = mysqli_fetch_assoc($resultDepartments)) : ?>
                                            <?php if ($row['department_name'] != 'University Wide') : ?> <!-- Hide by value name -->
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-4 py-2 text-sm text-gray-900"><?php echo $row['department_name']; ?></td>
                                                    <td class="px-4 py-2 text-center">
                                                        <!-- Maintain original button styling but update colors -->
                                                        <button class="text-orange-500 btn-icon editDepartmentBtn"
                                                            data-id="<?php echo $row['department_id']; ?>"
                                                            data-department_name="<?php echo $row['department_name']; ?>">
                                                            <i class="fas fa-pencil-alt"></i>
                                                        </button>

                                                        <button class="text-red-500 btn-icon deleteDepartmentBtn"
                                                            data-id="<?php echo $row['department_id']; ?>"
                                                            onclick="confirmDeleteDepartment(<?php echo $row['department_id']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endwhile; ?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="2" class="px-4 py-2 text-center">No departments available.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Course Modal -->
    <div id="addCourseModal" class="modal">
        <div class="modal-content">
            <h2 class="text-xl font-bold">Add Course</h2>
            <form id="addCourseForm" method="POST" action="add_course.php">
                <div class="mt-4">
                    <label for="course_name" class="block text-sm font-medium text-gray-600">Course Name</label>
                    <input type="text" name="course_name" id="course_name" required class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>
                <div class="mt-4">
                    <label for="department_id" class="block text-sm font-medium text-gray-600">Department</label>
                    <select name="department_id" id="department_id" required class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="" disabled selected>Select a department</option>
                        <?php foreach ($departments as $department) : ?>
                            <?php if ($department['department_name'] != 'University Wide') : ?> <!-- Hide by value name -->
                                <option value="<?= $department['department_id'] ?>"><?= $department['department_name'] ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mt-4">
                    <label for="course_description" class="block text-sm font-medium text-gray-600">Course Description (Optional)</label>
                    <textarea name="course_description" id="course_description" class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"></textarea>
                </div>
                <div class="flex justify-end mt-6">
                    <button type="button" id="closeAddModal" class="px-4 py-2 mr-2 text-white bg-gray-500 rounded-md hover:bg-gray-600">Cancel</button>
                    <button type="submit" name="add_course" class="px-4 py-2 text-white bg-green-500 rounded-md hover:bg-green-600">Add</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Department Modal -->
    <div id="departmentModal" class="modal">
        <div class="modal-content">
            <h2 class="text-xl font-bold">Add New Department</h2>
            <form id="departmentForm">
                <div class="mt-4">
                    <label for="department_name" class="block text-sm font-medium text-gray-600">Department Name</label>
                    <input type="text" id="department_name" name="department_name" class="w-full p-2 mt-1 border border-gray-300 rounded" required>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" id="closeModalBtn" class="px-4 py-2 text-white bg-gray-500 rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-white bg-green-500 rounded">Save Department</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Utility functions for showing and hiding modals
        function showModal(modal) {
            modal.style.display = 'flex';
        }

        function closeModal(modal) {
            modal.style.display = 'none';
        }

        // Modal elements
        const modals = {
            addCourse: document.getElementById('addCourseModal'),
            addDepartment: document.getElementById('departmentModal'),
        };

        // Open and close modals
        document.getElementById('addCourseBtn').addEventListener('click', () => showModal(modals.addCourse));
        document.getElementById('addDepartmentBtn').addEventListener('click', () => showModal(modals.addDepartment));

        // Close buttons
        document.getElementById('closeAddModal').addEventListener('click', () => closeModal(modals.addCourse));
        document.getElementById('closeModalBtn').addEventListener('click', () => closeModal(modals.addDepartment));

        // Close modals on outside click
        window.addEventListener('click', (e) => {
            Object.values(modals).forEach(modal => {
                if (e.target === modal) closeModal(modal);
            });
        });

        // Handle Add Course Form submission
        document.getElementById('addCourseForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('add_course.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Course added successfully!',
                            icon: 'success',
                            confirmButtonColor: '#4CAF50'
                        }).then(() => {
                            location.reload(); // Reload the page to reflect the added course
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Failed to add course.',
                            icon: 'error',
                            confirmButtonColor: '#f44336'
                        });
                    }
                });
        });

        // Delete confirmation using SweetAlert
        function confirmDeleteCourse(courseId) {
            Swal.fire({
                title: '<span style="font-size: 0.9rem;">Are you sure?</span>',
                html: '<span style="font-size: 0.8rem;">You won\'t be able to revert this!</span>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#4CAF50', // Green for delete
                cancelButtonColor: '#FF9800', // Orange for cancel
                confirmButtonText: '<span style="font-size: 0.8rem;">Yes, delete it!</span>',
                cancelButtonText: '<span style="font-size: 0.8rem;">Cancel</span>'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `?delete_id=${courseId}`;
                }
            });
        }


        // Handle form submission for adding a department
        document.getElementById('departmentForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const departmentName = document.getElementById('department_name').value;

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=save_department&department_name=${departmentName}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Department added successfully!',
                            icon: 'success',
                            confirmButtonColor: '#4CAF50'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Failed to add department.',
                            icon: 'error',
                            confirmButtonColor: '#f44336'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error!',
                        text: error.message,
                        confirmButtonColor: '#f44336'
                    });
                });
        });

        // Edit Department
        document.querySelectorAll('.editDepartmentBtn').forEach(button => {
            button.addEventListener('click', function() {
                const departmentId = this.dataset.id;
                const departmentName = this.dataset.department_name;

                Swal.fire({
                    title: '<span style="font-size: 0.9rem;">Edit Department</span>',
                    html: `
                    <input type="text" id="newDepartmentName" class="swal2-input" value="${departmentName}" style="font-size: 0.8rem;">
                `,
                    showCancelButton: true,
                    confirmButtonColor: '#FF9800', // Orange for edit
                    cancelButtonColor: '#6C757D',
                    confirmButtonText: '<span style="font-size: 0.8rem;">Save Changes</span>',
                    cancelButtonText: '<span style="font-size: 0.8rem;">Cancel</span>',
                    preConfirm: () => {
                        const newName = document.getElementById('newDepartmentName').value;
                        if (!newName) {
                            Swal.showValidationMessage('Department name cannot be empty.');
                        }
                        return newName;
                    }
                }).then(result => {
                    if (result.isConfirmed) {
                        fetch('update_department.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    id: departmentId,
                                    name: result.value
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: '<span style="font-size: 0.9rem;">Department Updated</span>',
                                        confirmButtonColor: '#4CAF50'
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: '<span style="font-size: 0.9rem;">Error</span>',
                                        text: data.message,
                                        confirmButtonColor: '#f44336'
                                    });
                                }
                            })
                            .catch(error => {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Network Error!',
                                    text: error.message,
                                    confirmButtonColor: '#f44336'
                                });
                            });
                    }
                });
            });
        });

        // Delete Department
        function confirmDeleteDepartment(departmentId) {
            Swal.fire({
                title: '<span style="font-size: 0.9rem;">Are you sure?</span>',
                html: '<span style="font-size: 0.8rem;">This action cannot be undone.</span>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#4CAF50', // Green for delete
                cancelButtonColor: '#FF9800', // Orange for cancel
                confirmButtonText: '<span style="font-size: 0.8rem;">Yes, delete it!</span>',
                cancelButtonText: '<span style="font-size: 0.8rem;">Cancel</span>'
            }).then(result => {
                if (result.isConfirmed) {
                    window.location.href = `delete_department.php?id=${departmentId}`;
                }
            });
        }


        // Open Edit Course Modal and Populate Data
        document.querySelectorAll('.edit-course-btn').forEach(button => {
            button.addEventListener('click', function() {
                const courseId = this.dataset.id;
                const courseName = this.dataset.courseName;
                const departmentId = this.dataset.departmentId;
                const courseDescription = this.dataset.courseDescription;

                // Populate the edit modal fields
                document.getElementById('edit_course_id').value = courseId;
                document.getElementById('edit_course_name').value = courseName;
                document.getElementById('edit_department_id').value = departmentId;
                document.getElementById('edit_course_description').value = courseDescription;

                // Show the edit modal
                showModal(document.getElementById('editCourseModal'));
            });
        });

        // Close Edit Course Modal when clicking on the close button
        document.getElementById('closeEditModal').addEventListener('click', () => closeModal(document.getElementById('editCourseModal')));

        // Close modal when clicking outside of it
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('editCourseModal');
            if (modal && modal.classList.contains('show')) {
                const modalContent = modal.querySelector('.modal-content'); // Adjust this selector if needed
                if (!modalContent.contains(event.target)) {
                    closeModal(modal);
                }
            }
        });

        // Handle Edit Course Form submission
        document.getElementById('editCourseForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('update_course.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Course updated successfully!',
                            icon: 'success',
                            confirmButtonColor: '#4CAF50'
                        }).then(() => {
                            location.reload(); // Reload the page to reflect the updated course
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Failed to update course.',
                            icon: 'error',
                            confirmButtonColor: '#f44336'
                        });
                    }
                });
        });
    </script>
</body>

</html>