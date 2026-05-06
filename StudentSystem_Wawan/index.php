<?php
// 1. Integration
require_once 'db.php';

// 2. Logic: Fetch all records
$sql = "SELECT * FROM students";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Record System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 60%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        .nav-link { font-weight: bold; text-decoration: none; color: blue; }
    </style>
</head>
<body>
    <h2>Student Directory</h2>
    
    <a href="add.php" class="nav-link">Add New Student</a>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Student Name</th>
                <th>Course</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Check if there are results and display them
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    // Using htmlspecialchars for basic output escaping
                    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['course']) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='3'>No student records found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>
<?php 
// Close connection
$conn->close(); 
?>