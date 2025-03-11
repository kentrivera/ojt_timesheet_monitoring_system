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

// Function to handle saving and updating agencies
function saveAgency($data)
{
    global $conn;

    if (!empty($data['id'])) {
        // Update existing agency
        $query = "UPDATE agencies SET 
                    agency_name = ?, 
                    agency_address = ?, 
                    contact_number = ?, 
                    person_incharge = ? 
                  WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "ssssi",
            $data['agency_name'],
            $data['agency_address'],
            $data['contact_number'],
            $data['person_incharge'],
            $data['id']
        );
    } else {
        // Insert new agency
        $query = "INSERT INTO agencies (agency_name, agency_address, contact_number, person_incharge) 
                  VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "ssss",
            $data['agency_name'],
            $data['agency_address'],
            $data['contact_number'],
            $data['person_incharge']
        );
    }

    $stmt->execute();
    return $stmt->affected_rows > 0;
}

// Function to delete an agency
function deleteAgency($id)
{
    global $conn;
    $query = "DELETE FROM agencies WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->affected_rows > 0;
}
// Handle agency deletion (add this header)
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $success = deleteAgency($delete_id);
    header('Content-Type: application/json'); // Add this line
    echo json_encode(['success' => $success]);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $data = [
        'id' => $_POST['id'],
        'agency_name' => $_POST['agency_name'],
        'agency_address' => $_POST['agency_address'],
        'contact_number' => $_POST['contact_number'],
        'person_incharge' => $_POST['person_incharge']
    ];

    $success = saveAgency($data);
    echo json_encode(['success' => $success]);
    exit;
}

// Handle deletion
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $success = deleteAgency($delete_id);
    echo json_encode(['success' => $success]);
    exit;
}

// Fetch agencies
$query = "SELECT * FROM agencies";
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
    <title>Agencies Management</title>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: '#4CAF50',
                            light: '#81C784',
                            dark: '#388E3C'
                        },
                        secondary: {
                            DEFAULT: '#FF9800',
                            light: '#FFB74D',
                            dark: '#F57C00'
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
                            <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Agencies</h1>
                            <p class="text-gray-600 mt-1">Manage your agency partnerships</p>
                        </div>
                        <button class="flex items-center px-4 py-2 text-white rounded transition-colors bg-primary hover:bg-primary-dark shadow-md" id="addAgencyBtn">
                            <i class="fas fa-plus"></i>
                            <span class="ml-2">Add Agency</span>
                        </button>
                    </div>

                    <div class="bg-white rounded-lg overflow-hidden shadow">
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm divide-y divide-gray-200">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="px-4 py-3 text-left font-medium text-gray-700">Agency Name</th>
                                        <th class="px-4 py-3 text-left font-medium text-gray-700 hidden md:table-cell">Address</th>
                                        <th class="px-4 py-3 text-left font-medium text-gray-700 hidden sm:table-cell">Contact Number</th>
                                        <th class="px-4 py-3 text-left font-medium text-gray-700 hidden lg:table-cell">Person In Charge</th>
                                        <th class="px-4 py-3 text-center font-medium text-gray-700">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3"><?= $row['agency_name'] ?></td>
                                            <td class="px-4 py-3 hidden md:table-cell"><?= $row['agency_address'] ?></td>
                                            <td class="px-4 py-3 hidden sm:table-cell"><?= $row['contact_number'] ?></td>
                                            <td class="px-4 py-3 hidden lg:table-cell"><?= $row['person_incharge'] ?></td>
                                            <td class="px-4 py-3">
                                                <div class="flex justify-center space-x-3">
                                                    <button class="text-secondary-dark hover:text-secondary transition-colors editBtn"
                                                        data-id="<?= $row['id'] ?>"
                                                        data-agency_name="<?= $row['agency_name'] ?>"
                                                        data-agency_address="<?= $row['agency_address'] ?>"
                                                        data-contact_number="<?= $row['contact_number'] ?>"
                                                        data-person_incharge="<?= $row['person_incharge'] ?>">
                                                        <i class="fas fa-pencil-alt"></i>
                                                    </button>
                                                    <button class="text-red-500 hover:text-red-700 transition-colors deleteBtn" data-id="<?= $row['id'] ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <button class="text-primary hover:text-primary-dark transition-colors viewDetailsBtn sm:hidden"
                                                        data-agency_name="<?= $row['agency_name'] ?>"
                                                        data-agency_address="<?= $row['agency_address'] ?>"
                                                        data-contact_number="<?= $row['contact_number'] ?>"
                                                        data-person_incharge="<?= $row['person_incharge'] ?>">
                                                        <i class="fas fa-eye"></i>
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

    <!-- Agency Modal -->
    <div id="agencyModal" class="fixed inset-0 flex items-center justify-center hidden bg-black bg-opacity-50 z-50">
        <div class="w-full max-w-md p-6 bg-white rounded-lg shadow-xl mx-4">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-800" id="modalTitle">Add Agency</h2>
                <button type="button" class="text-gray-400 hover:text-gray-600" id="closeModalBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="agencyForm">
                <input type="hidden" id="agencyId" name="id">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Agency Name</label>
                    <input type="text" name="agency_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <input type="text" name="agency_address" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                    <input type="text" name="contact_number" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Person In Charge</label>
                    <input type="text" name="person_incharge" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" required>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 transition-colors" id="cancelBtn">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark transition-colors">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Details Modal (for mobile) -->
    <div id="detailsModal" class="fixed inset-0 flex items-center justify-center hidden bg-black bg-opacity-50 z-50">
        <div class="w-full max-w-md p-6 bg-white rounded-lg shadow-xl mx-4">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-800" id="detailsTitle">Agency Details</h2>
                <button type="button" class="text-gray-400 hover:text-gray-600" id="closeDetailsBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="space-y-3">
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Agency Name</h3>
                    <p class="text-gray-800" id="detailsName"></p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Address</h3>
                    <p class="text-gray-800" id="detailsAddress"></p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Contact Number</h3>
                    <p class="text-gray-800" id="detailsContact"></p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Person In Charge</h3>
                    <p class="text-gray-800" id="detailsPerson"></p>
                </div>
            </div>
            <div class="flex justify-end mt-6">
                <button type="button" class="px-4 py-2 bg-secondary text-white rounded-md hover:bg-secondary-dark transition-colors" id="closeDetailsBtnBottom">Close</button>
            </div>
        </div>
    </div>

    <script>
        // DOM Elements
        const agencyModal = document.getElementById('agencyModal');
        const detailsModal = document.getElementById('detailsModal');
        const agencyForm = document.getElementById('agencyForm');
        const editBtns = document.querySelectorAll('.editBtn');
        const viewDetailsBtns = document.querySelectorAll('.viewDetailsBtn');

        // Show Agency Modal
        document.getElementById('addAgencyBtn').addEventListener('click', () => {
            agencyForm.reset();
            agencyModal.classList.remove('hidden');
            document.getElementById('modalTitle').textContent = 'Add Agency';
        });

        // Close Modals
        document.getElementById('cancelBtn').addEventListener('click', () => {
            agencyModal.classList.add('hidden');
        });

        document.getElementById('closeModalBtn').addEventListener('click', () => {
            agencyModal.classList.add('hidden');
        });

        document.getElementById('closeDetailsBtn').addEventListener('click', () => {
            detailsModal.classList.add('hidden');
        });

        document.getElementById('closeDetailsBtnBottom').addEventListener('click', () => {
            detailsModal.classList.add('hidden');
        });

        // Edit Agency
        editBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const form = agencyForm;
                form.id.value = btn.dataset.id;
                form.agency_name.value = btn.dataset.agency_name;
                form.agency_address.value = btn.dataset.agency_address;
                form.contact_number.value = btn.dataset.contact_number;
                form.person_incharge.value = btn.dataset.person_incharge;
                document.getElementById('modalTitle').textContent = 'Edit Agency';
                agencyModal.classList.remove('hidden');
            });
        });

        // View Details (for mobile)
        viewDetailsBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('detailsName').textContent = btn.dataset.agency_name;
                document.getElementById('detailsAddress').textContent = btn.dataset.agency_address;
                document.getElementById('detailsContact').textContent = btn.dataset.contact_number;
                document.getElementById('detailsPerson').textContent = btn.dataset.person_incharge;
                detailsModal.classList.remove('hidden');
            });
        });

        // Delete Agency
        document.querySelectorAll('.deleteBtn').forEach(btn => {
            btn.addEventListener('click', () => {
                Swal.fire({
                    title: 'Delete Agency?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#4CAF50',
                    cancelButtonColor: '#FFC300', 
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`?delete_id=${btn.dataset.id}`, {
                                method: 'GET'
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        title: 'Deleted!',
                                        text: 'The agency has been deleted.',
                                        icon: 'success',
                                        confirmButtonColor: '#4CAF50'
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: 'Failed to delete agency.',
                                        icon: 'error',
                                        confirmButtonColor: '#4CAF50'
                                    });
                                }
                            })
                            .catch(error => {
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'Failed to process request.',
                                    icon: 'error',
                                    confirmButtonColor: '#4CAF50'
                                });
                            });
                    }
                });
            });
        });

        // Form Submission
        agencyForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(agencyForm);
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
                            text: 'Agency saved successfully',
                            icon: 'success',
                            confirmButtonColor: '#4CAF50'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Failed to save agency',
                            icon: 'error',
                            confirmButtonColor: '#4CAF50'
                        });
                    }
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