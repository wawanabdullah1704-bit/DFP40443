<?php
// dashboard.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'config/db.php';

// --- Stats queries (Advanced SQL: GROUP BY, ORDER BY, JOIN) ---

// Total students
$totalStudents = 0;
$r = $conn->query("SELECT COUNT(*) AS cnt FROM students");
if ($r) $totalStudents = $r->fetch_assoc()['cnt'];

// Total courses
$totalCourses = 0;
$r = $conn->query("SELECT COUNT(*) AS cnt FROM courses");
if ($r) $totalCourses = $r->fetch_assoc()['cnt'];

// Average GPA
$avgGpa = 0;
$r = $conn->query("SELECT ROUND(AVG(gpa), 2) AS avg FROM students");
if ($r) $avgGpa = $r->fetch_assoc()['avg'] ?? 0;

// Total users
$totalUsers = 0;
$r = $conn->query("SELECT COUNT(*) AS cnt FROM users");
if ($r) $totalUsers = $r->fetch_assoc()['cnt'];

// Students per course (GROUP BY + JOIN + ORDER BY)
$courseStats = [];
$r = $conn->query("
    SELECT c.name AS course_name, c.code, COUNT(s.id) AS total,
           ROUND(AVG(s.gpa), 2) AS avg_gpa
    FROM courses c
    LEFT JOIN students s ON s.course_id = c.id
    GROUP BY c.id, c.name, c.code
    ORDER BY total DESC
");
if ($r) {
    while ($row = $r->fetch_assoc()) $courseStats[] = $row;
}

// Gender breakdown (GROUP BY)
$genderStats = [];
$r = $conn->query("SELECT gender, COUNT(*) AS cnt FROM students GROUP BY gender ORDER BY cnt DESC");
if ($r) {
    while ($row = $r->fetch_assoc()) $genderStats[] = $row;
}

// Recent 5 students (JOIN + ORDER BY)
$recentStudents = [];
$r = $conn->query("
    SELECT s.student_no, s.name, s.gpa, s.created_at, c.name AS course_name
    FROM students s
    JOIN courses c ON s.course_id = c.id
    ORDER BY s.created_at DESC
    LIMIT 5
");
if ($r) {
    while ($row = $r->fetch_assoc()) $recentStudents[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard — Student Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; }
        .stat-card { border-radius: 12px; border: none; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .stat-icon { width: 52px; height: 52px; border-radius: 12px; display:flex; align-items:center; justify-content:center; font-size:1.4rem; }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container-fluid py-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 fw-bold">Dashboard</h4>
            <small class="text-muted">Welcome back, <?= htmlspecialchars($_SESSION['username']) ?>!</small>
        </div>
        <?php if ($_SESSION['role'] === 'admin'): ?>
        <a href="students/add.php" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add Student
        </a>
        <?php endif; ?>
    </div>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card stat-card p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="fs-3 fw-bold"><?= $totalStudents ?></div>
                        <div class="text-muted small">Total Students</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-journal-bookmark-fill"></i>
                    </div>
                    <div>
                        <div class="fs-3 fw-bold"><?= $totalCourses ?></div>
                        <div class="text-muted small">Courses</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-star-fill"></i>
                    </div>
                    <div>
                        <div class="fs-3 fw-bold"><?= number_format($avgGpa, 2) ?></div>
                        <div class="text-muted small">Average GPA</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-person-gear"></i>
                    </div>
                    <div>
                        <div class="fs-3 fw-bold"><?= $totalUsers ?></div>
                        <div class="text-muted small">System Users</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Students per course -->
        <div class="col-lg-7">
            <div class="card stat-card p-3 h-100">
                <h6 class="fw-semibold mb-3"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>Students per Course</h6>
                <?php if (empty($courseStats)): ?>
                    <p class="text-muted">No data.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Course</th>
                                <th class="text-center">Students</th>
                                <th class="text-center">Avg GPA</th>
                                <th>Distribution</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($courseStats as $cs): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-primary bg-opacity-10 text-primary me-1"><?= htmlspecialchars($cs['code']) ?></span>
                                    <?= htmlspecialchars($cs['course_name']) ?>
                                </td>
                                <td class="text-center fw-bold"><?= $cs['total'] ?></td>
                                <td class="text-center">
                                    <?= $cs['avg_gpa'] ? number_format($cs['avg_gpa'],2) : '—' ?>
                                </td>
                                <td style="min-width:100px">
                                    <?php $pct = $totalStudents > 0 ? ($cs['total']/$totalStudents*100) : 0; ?>
                                    <div class="progress" style="height:8px">
                                        <div class="progress-bar" style="width:<?= $pct ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Gender + recent -->
        <div class="col-lg-5">
            <div class="card stat-card p-3 mb-4">
                <h6 class="fw-semibold mb-3"><i class="bi bi-pie-chart-fill me-2 text-success"></i>Gender Distribution</h6>
                <?php if (empty($genderStats)): ?>
                    <p class="text-muted">No data.</p>
                <?php else: ?>
                <?php foreach ($genderStats as $g): ?>
                    <?php $gpct = $totalStudents > 0 ? ($g['cnt']/$totalStudents*100) : 0; ?>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small"><?= htmlspecialchars($g['gender']) ?></span>
                        <span class="small fw-semibold"><?= $g['cnt'] ?> (<?= round($gpct) ?>%)</span>
                    </div>
                    <div class="progress mb-3" style="height:10px">
                        <div class="progress-bar <?= $g['gender']==='Female' ? 'bg-pink' : '' ?>"
                             style="width:<?= $gpct ?>%; background:<?= $g['gender']==='Female' ? '#d63384' : ($g['gender']==='Male' ? '#0d6efd' : '#6c757d') ?>">
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="card stat-card p-3">
                <h6 class="fw-semibold mb-3"><i class="bi bi-clock-history me-2 text-warning"></i>Recently Added</h6>
                <?php if (empty($recentStudents)): ?>
                    <p class="text-muted">No students yet.</p>
                <?php else: ?>
                <ul class="list-unstyled mb-0">
                <?php foreach ($recentStudents as $rs): ?>
                    <li class="d-flex align-items-center gap-2 py-2 border-bottom">
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;font-size:.8rem;flex-shrink:0">
                            <?= strtoupper(substr($rs['name'],0,1)) ?>
                        </div>
                        <div class="overflow-hidden">
                            <div class="fw-semibold text-truncate small"><?= htmlspecialchars($rs['name']) ?></div>
                            <div class="text-muted" style="font-size:.75rem">
                                <?= htmlspecialchars($rs['course_name']) ?> &mdash; GPA <?= number_format($rs['gpa'],2) ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
                </ul>
                <div class="mt-2 text-end">
                    <a href="students/index.php" class="btn btn-sm btn-outline-primary">View all</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
