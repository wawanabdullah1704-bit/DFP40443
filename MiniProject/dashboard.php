<?php
session_start();
require 'config.php';

// Security: Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$role = $_SESSION['role']; // 'admin' or 'user'
$search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';

// ADVANCED SQL: JOIN, WHERE, and ORDER BY
$sql = "SELECT students.id, students.reg_no, students.name, programs.program_code 
        FROM students 
        JOIN programs ON students.program_id = programs.id 
        WHERE students.name LIKE ? OR students.reg_no LIKE ?
        ORDER BY students.name ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $search, $search);
$stmt->execute();
$students = $stmt->get_result();

// ADVANCED SQL: GROUP BY for Dashboard Statistics
$stats_sql = "SELECT programs.program_code, COUNT(students.id) as total_students 
              FROM students 
              JOIN programs ON students.program_id = programs.id 
              GROUP BY programs.program_code";
$stats_result = $conn->query($stats_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Student GMS - <?= ucfirst($role) ?> Panel</a>
        <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
</nav>

<div class="container mt-4">
    <div class="row mb-4">
        <?php while($stat = $stats_result->fetch_assoc()): ?>
            <div class="col-md-3">
                <div class="card text-bg-info text-white shadow-sm">
                    <div class="card-body">
                        <h5><?= $stat['program_code'] ?> Students</h5>
                        <h3><?= $stat['total_students'] ?></h3>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <div class="d-flex justify-content-between mb-3">
        <form method="GET" class="d-flex">
            <input type="text" name="search" class="form-control me-2" placeholder="Search by name or Reg No...">
            <button type="submit" class="btn btn-secondary">Search</button>
        </form>
        <?php if ($role == 'admin'): ?>
            <a href="add.php" class="btn btn-success">+ Add Student</a>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Reg. No</th>
                        <th>Name</th>
                        <th>Program</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $students->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['reg_no'] ?></td>
                        <td><?= $row['name'] ?></td>
                        <td><?= $row['program_code'] ?></td>
                        <td>
                            <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                            <?php if ($role == 'admin'): ?>
                                <a href="delete.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>