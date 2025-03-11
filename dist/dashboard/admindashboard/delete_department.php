<?php
require '../dbcon.php';

if (isset($_GET['id'])) {
    $departmentId = $_GET['id'];

    $query = "DELETE FROM department WHERE department_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $departmentId);

    if ($stmt->execute()) {
        header('Location: manage-coursesanddepartment.php');
    } else {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to delete the department.',
                confirmButtonColor: '#d33'
            });
        </script>";
    }
}
