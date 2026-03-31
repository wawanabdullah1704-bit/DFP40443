<?php
// reports.php - Advanced SQL queries (WHERE, JOIN, ORDER BY, GROUP BY)
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/db.php';

$role     = $_SESSION['role'];
$username = $_SESSION['username'];

// Advanced Query: GROUP BY - Count students per course
$stmt = $conn->prepare("
    SELECT c.course_name, c.course_code, COUNT(s.id) AS total_students,
           SUM(CASE WHEN s.status = 'active' THEN 1 ELSE 0 END) AS active_count
    FROM courses c
    LEFT JOIN students s ON s.course_id = c.id
    GROUP BY c.id, c.course_name, c.course_code
    ORDER BY total_students DESC
");
$stmt->execute();
$courseStats = $stmt->get_result();
$stmt->close();

// Advanced Query: All students with JOIN + ORDER BY
$stmt = $conn->prepare("
    SELECT s.student_name, s.student_no, s.email, s.status, c.course_name
    FROM students s
    LEFT JOIN courses c ON s.course_id = c.id
    WHERE s.status = 'active'
    ORDER BY s.student_name ASC
");
$stmt->execute();
$activeStudents = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS - Reports</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .navbar { background-color: #0d6efd !important; }
        .section-card { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<nav class="navbar navbar-dark px-4">
    <span class="navbar-brand">🎓 Student Management System</span>
    <div class="d-flex gap-3 align-items-center">
        <a href="dashboard.php" class="btn btn-outline-light btn-sm">Dashboard</a>
        <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
</nav>

<div class="container-fluid p-4">
    <h4 class="mb-4">📊 Reports & Analytics</h4>

    <!-- Students per course (GROUP BY) -->
    <div class="section-card mb-4">
        <h5 class="mb-3">Students Per Course <small class="text-muted fs-6">(GROUP BY + JOIN)</small></h5>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Total Students</th>
                        <th>Active</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $courseStats->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['course_code']) ?></td>
                        <td><?= htmlspecialchars($row['course_name']) ?></td>
                        <td><span class="badge bg-primary"><?= $row['total_students'] ?></span></td>
                        <td><span class="badge bg-success"><?= $row['active_count'] ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Active students list (WHERE + JOIN + ORDER BY) -->
    <div class="section-card">
        <h5 class="mb-3">Active Students <small class="text-muted fs-6">(WHERE + JOIN + ORDER BY)</small></h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-success">
                    <tr>
                        <th>#</th>
                        <th>Student No</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Course</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; while ($row = $activeStudents->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($row['student_no']) ?></td>
                        <td><?= htmlspecialchars($row['student_name']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['course_name'] ?? 'N/A') ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
