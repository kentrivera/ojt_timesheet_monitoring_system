<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>DTR Mode - Trainee Timesheet</title>
    <style>
        .dtr-table th,
        .dtr-table td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
        }

        .dtr-table th {
            background-color: #f3f4f6;
            font-weight: bold;
        }

        .action-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 1rem;
        }

        .image-thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 0.5rem;
        }
    </style>
</head>

<body class="bg-gray-100">
    <!-- Main Layout -->
    <div class="pt-5 mt-10 main-layout">
        <!-- Top Navigation -->
        <?php include 'nav/top-nav.php'; ?>

        <!-- Content Wrapper -->
        <div class="flex flex-1">
            <!-- Side Navigation -->
            <div class="w-64 min-h-screen text-white bg-gray-800">
                <?php include 'nav/side-nav.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="flex-1 p-6 bg-gray-100">
                <h1 class="mb-4 text-xl font-bold text-green-700">Trainee DTR Mode</h1>

                <!-- Action Buttons (Top Right) -->
                <div class="action-buttons">
                    <button class="flex items-center px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                        <i class="fas fa-calendar-check mr-2"></i> View DTR Mode
                    </button>
                    <button id="printBtn" class="flex items-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                        <i class="fas fa-print mr-2"></i> Print
                    </button>
                    <button id="downloadBtn" class="flex items-center px-4 py-2 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600">
                        <i class="fas fa-download mr-2"></i> Download
                    </button>
                </div>

                <!-- DTR Mode Table -->
                <?php if ($result->num_rows > 0): ?>
                    <table class="dtr-table w-full mt-6">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time In (AM)</th>
                                <th>Time Out (AM)</th>
                                <th>Time In (PM)</th>
                                <th>Time Out (PM)</th>
                                <th>Images</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="text-gray-600"><?php echo $row['date']; ?></td>
                                    <td class="text-gray-600"><?php echo $row['first_time_in']; ?></td>
                                    <td class="text-gray-600"><?php echo $row['first_timeout']; ?></td>
                                    <td class="text-gray-600"><?php echo $row['second_time_in']; ?></td>
                                    <td class="text-gray-600"><?php echo $row['second_timeout']; ?></td>
                                    <td class="text-center">
                                        <div class="flex justify-center gap-4">
                                            <img class="image-thumbnail" src="<?php echo $row['first_image']; ?>" alt="First Time In Image">
                                            <img class="image-thumbnail" src="<?php echo $row['second_image']; ?>" alt="Second Time In Image">
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-center text-gray-600">No timesheet data available for this trainee in DTR mode.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Event listeners for buttons
        document.getElementById('printBtn').addEventListener('click', function() {
            window.print(); // Print the page
        });

        document.getElementById('downloadBtn').addEventListener('click', function() {
            alert("Download functionality to be implemented.");
        });
    </script>
</body>

</html>
