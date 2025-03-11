<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Trainees</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex items-center justify-center min-h-screen bg-gray-50">
    <div class="w-full max-w-5xl p-6 bg-white border border-gray-300 rounded-lg shadow-lg">
        <h1 class="mb-4 text-xl font-bold text-gray-700">Search Results</h1>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <?php
            // Include the database connection file
            include '../dbcon.php';

            // Get the search query from the GET request
            $searchQuery = isset($_GET['search_trainee']) ? $_GET['search_trainee'] : '';

            // Define the SQL query to search for trainees based on the query
            $sql = "
                SELECT 
                    trainees.id, 
                    trainees.student_id,  
                    trainees.name, 
                    trainees.email, 
                    trainees.image, 
                    courses.course_name, 
                    agencies.agency_name, 
                    school_years.school_year 
                FROM 
                    trainees
                LEFT JOIN courses ON trainees.course_id = courses.id
                LEFT JOIN agencies ON trainees.agency_id = agencies.id
                LEFT JOIN school_years ON trainees.school_year_id = school_years.id
                WHERE trainees.name LIKE '%" . $conn->real_escape_string($searchQuery) . "%' 
                OR trainees.student_id LIKE '%" . $conn->real_escape_string($searchQuery) . "%'
            ";

            // Execute the query and fetch results
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    ?>
                    <div class="p-4 transition-transform duration-200 bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-lg hover:scale-105">
                        <div class="flex items-center space-x-4">
                            <a href="view_details.php?id=<?php echo $row['id']; ?>">
                                <img src="<?php echo htmlspecialchars($row['image']); ?>" 
                                     alt="Trainee Image" 
                                     class="border border-gray-300 rounded-full w-14 h-14 hover:border-green-500">
                            </a>
                            <div>
                                <h2 class="text-sm font-semibold text-gray-700"><?php echo htmlspecialchars($row['name']); ?></h2>
                                <p class="text-xs text-gray-600">Course: <?php echo htmlspecialchars($row['course_name']); ?></p>
                                <p class="text-xs text-gray-600">Agency: <?php echo htmlspecialchars($row['agency_name']); ?></p>
                                <p class="text-xs text-gray-600">School Year: <?php echo htmlspecialchars($row['school_year']); ?></p>
                            </div>
                        </div>
                        <a href="coordinator_view_details.php?id=<?php echo $row['id']; ?>" class="block mt-2 text-xs text-green-600 hover:text-green-700">
                            View Details
                        </a>
                    </div>
                    <?php
                }
            } else {
                echo '<p class="text-gray-600">No trainees found.</p>';
            }
            ?>
        </div>
    </div>
</body>
</html>
