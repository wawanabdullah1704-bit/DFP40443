<?php
// students/index.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
require_once '../config/db.php';

// --- Search / filter params ---
$search  = trim($_GET['search'] ?? '');
$courseF = intval($_GET['course'] ?? 0);
$genderF = trim($_GET['gender'] ?? '');
$orderF  = in_array($_GET['order'] ?? '', ['name','student_no','gpa','created_at']) ? $_GET['order'] : 'created_at';
$dirF    = ($_GET['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

// Build dynamic WHERE conditions (still use prepared statements)
$conditions = ["1=1"];
$params     = [];
$types      = "";

if ($search !== '') {
    $conditions[] = "(s.name LIKE ? OR s.student_no LIKE ? OR s.email LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= "sss";
}
if ($courseF > 0) {
    $conditions[] = "s.course_id = ?";
    $params[] = $courseF;
    $types .= "i";
}
if ($genderF !== '') {
    $conditions[] = "s.gender = ?";
    $params[] = $genderF;
    $types .= "s";
}

$where = implode(" AND ", $conditions);

// Main query: JOIN courses + WHERE + ORDER BY
$sql = "
    SELECT s.id, s.student_no, s.name, s.email, s.phone, s.gender, s.gpa, s.created_at,
           c.name AS course_name, c.code AS course_code
    FROM students s
    JOIN courses c ON s.course_id = c.id
    WHERE {$where}
    ORDER BY s.{$orderF} {$dirF}
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Courses dropdown
$courses = $conn->query("SELECT id, code, name FROM courses ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Flash message
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

function sortLink($col, $label, $currentOrder, $currentDir) {
    $params = $_GET;
    $params['order'] = $col;
    $params['dir']   = ($currentOrder === $col && $currentDir === 'ASC') ? 'DESC' : 'ASC';
    $icon = $currentOrder === $col ? ($currentDir === 'ASC' ? '↑' : '↓') : '↕';
    return '<a href="?' . http_build_query($params) . '" class="text-decoration-none text-dark">'
         . htmlspecialchars($label) . ' <small>' . $icon . '</small></a>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Students — Student Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; }
        .gpa-badge { font-size:.75rem; padding:.25em .6em; }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container-fluid py-4 px-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h4 class="mb-0 fw-bold">Students</h4>
            <small class="text-muted"><?= count($students) ?> record(s) found</small>
        </div>
        <?php if ($_SESSION['role'] === 'admin'): ?>
        <a href="add.php" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i> Add Student
        </a>
        <?php endif; ?>
    </div>

    <?php if ($flash): ?>
    <div class="alert alert-<?= str_starts_with($flash,'Error') ? 'danger' : 'success' ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Search / Filter Form -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius:12px">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold mb-1">Keyword Search</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control"
                               placeholder="Name, student no, email..."
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Course</label>
                    <select name="course" class="form-select">
                        <option value="">All Courses</option>
                        <?php foreach ($courses as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $courseF == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['code'].' - '.$c['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="">All</option>
                        <option value="Male"   <?= $genderF==='Male'   ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= $genderF==='Female' ? 'selected' : '' ?>>Female</option>
                        <option value="Other"  <?= $genderF==='Other'  ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <input type="hidden" name="order" value="<?= htmlspecialchars($orderF) ?>">
                <input type="hidden" name="dir"   value="<?= htmlspecialchars($dirF) ?>">
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary flex-fill">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm" style="border-radius:12px">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3"><?= sortLink('student_no','Student No',$orderF,$dirF) ?></th>
                        <th><?= sortLink('name','Name',$orderF,$dirF) ?></th>
                        <th>Course</th>
                        <th>Gender</th>
                        <th><?= sortLink('gpa','GPA',$orderF,$dirF) ?></th>
                        <th>Contact</th>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                        <th class="text-center pe-3">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($students)): ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                        No students found. <?php if($search||$courseF||$genderF): ?>
                            <a href="index.php">Clear filters</a>
                        <?php elseif($_SESSION['role']==='admin'): ?>
                            <a href="add.php">Add the first student</a>
                        <?php endif; ?>
                    </td></tr>
                <?php else: ?>
                <?php foreach ($students as $s): ?>
                    <tr>
                        <td class="ps-3">
                            <span class="badge bg-secondary bg-opacity-10 text-dark">
                                <?= htmlspecialchars($s['student_no']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($s['name']) ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($s['email']) ?></div>
                        </td>
                        <td>
                            <span class="badge bg-primary bg-opacity-10 text-primary">
                                <?= htmlspecialchars($s['course_code']) ?>
                            </span>
                            <div class="small text-muted"><?= htmlspecialchars($s['course_name']) ?></div>
                        </td>
                        <td>
                            <span class="badge <?= $s['gender']==='Female' ? 'bg-pink text-white' : 'bg-info text-dark' ?>"
                                  style="<?= $s['gender']==='Female' ? 'background:#d63384!important' : '' ?>">
                                <?= htmlspecialchars($s['gender']) ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $gpa = floatval($s['gpa']);
                            $gpaCls = $gpa >= 3.5 ? 'bg-success' : ($gpa >= 2.5 ? 'bg-primary' : 'bg-warning text-dark');
                            ?>
                            <span class="badge <?= $gpaCls ?> gpa-badge"><?= number_format($gpa,2) ?></span>
                        </td>
                        <td class="small text-muted"><?= htmlspecialchars($s['phone'] ?: '—') ?></td>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                        <td class="text-center pe-3">
                            <a href="edit.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="delete.php?id=<?= $s['id'] ?>"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Delete <?= htmlspecialchars(addslashes($s['name'])) ?>? This cannot be undone.')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
