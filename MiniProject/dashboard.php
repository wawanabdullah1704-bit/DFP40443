<?php
session_start();
if (!isset($_SESSION['username'])) header("Location: index.php");
include 'config/db.php';

$total_students = $conn->query("SELECT COUNT(*) as total FROM students")->fetch_assoc()['total'];
$total_courses = $conn->query("SELECT COUNT(*) as total FROM courses")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Student MS</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="manage_students.php">Manage Students</a>
                <a class="nav-link btn btn-danger btn-sm text-white ms-2" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Welcome, <?php echo $_SESSION['username']; ?> (<?php echo $_SESSION['role']; ?>)</h2>
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card bg-info text-white p-4">
                    <h3>Total Students: <?php echo $total_students; ?></h3>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-success text-white p-4">
                    <h3>Total Courses: <?php echo $total_courses; ?></h3>
                </div>
            </div>
        </div>
    </div>
</body>
</html>