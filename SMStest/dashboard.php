<?php
// dashboard.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/db.php';

$role     = $_SESSION['role'];
$username = $_SESSION['username'];

// Search/filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Stats (GROUP BY for rubric)
$totalStudents = $conn->query("SELECT COUNT(*) AS total FROM students")->fetch_assoc()['total'];
$totalCourses  = $conn->query("SELECT COUNT(*) AS total FROM courses")->fetch_assoc()['total'];
$activeStudents = $conn->query("SELECT COUNT(*) AS total FROM students WHERE status='active'")->fetch_assoc()['total'];

// Get students with JOIN (students + courses) and WHERE for search
$sql = "SELECT s.id, s.student_name, s.student_no, s.email, s.phone, s.status, c.course_name
        FROM students s
        LEFT JOIN courses c ON s.course_id = c.id";

if ($search !== '') {
    $sql .= " WHERE s.student_name LIKE ? OR s.student_no LIKE ? OR s.email LIKE ?";
}
$sql .= " ORDER BY s.id DESC";

$stmt = $conn->prepare($sql);

if ($search !== '') {
    $like = "%$search%";
    $stmt->bind_param("sss", $like, $like, $like);
}

$stmt->execute();
$students = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS - Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .navbar { background-color: #0d6efd !important; }
        .stat-card { border-radius: 10px; border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .table-container { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-dark px-4">
    <span class="navbar-brand">🎓 Student Management System
        <span class="badge bg-warning text-dark ms-2"><?= ucfirst($role) ?></span>
    </span>
    <div class="d-flex align-items-center gap-3">
        <span class="text-white">Hello, <?= htmlspecialchars($username) ?></span>
        <a href="reports.php" class="btn btn-outline-light btn-sm">📊 Reports</a>
        <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
</nav>

<div class="container-fluid p-4">

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card stat-card text-white bg-primary">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Total Students</h6>
                        <h2 class="mb-0"><?= $totalStudents ?></h2>
                    </div>
                    <i class="bi bi-people-fill fs-1"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card text-white bg-success">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Active Students</h6>
                        <h2 class="mb-0"><?= $activeStudents ?></h2>
                    </div>
                    <i class="bi bi-person-check-fill fs-1"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card text-white bg-info">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Total Courses</h6>
                        <h2 class="mb-0"><?= $totalCourses ?></h2>
                    </div>
                    <i class="bi bi-book-fill fs-1"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Table -->
    <div class="table-container">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="mb-0">Student Records</h5>
            <div class="d-flex gap-2">
                <!-- Search Form -->
                <form method="GET" action="dashboard.php" class="d-flex gap-2">
                    <input type="text" name="search" class="form-control" placeholder="Search name, ID, email..."
                           value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <?php if ($search): ?>
                        <a href="dashboard.php" class="btn btn-outline-secondary">Clear</a>
                    <?php endif; ?>
                </form>
                <?php if ($role === 'admin'): ?>
                    <a href="students/add.php" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Add Student
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($search): ?>
            <div class="alert alert-info py-2">Showing results for: <strong><?= htmlspecialchars($search) ?></strong></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Student No</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Course</th>
                        <th>Status</th>
                        <?php if ($role === 'admin'): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($students->num_rows === 0): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No students found.</td></tr>
                    <?php endif; ?>
                    <?php $i = 1; while ($row = $students->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($row['student_no']) ?></td>
                        <td><?= htmlspecialchars($row['student_name']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['phone']) ?></td>
                        <td><?= htmlspecialchars($row['course_name'] ?? 'N/A') ?></td>
                        <td>
                            <?php if ($row['status'] === 'active'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($role === 'admin'): ?>
                        <td>
                            <a href="students/edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <a href="students/delete.php?id=<?= $row['id'] ?>"
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Delete this student?')">
                                <i class="bi bi-trash"></i> Delete
                            </a>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</body>
</html>
