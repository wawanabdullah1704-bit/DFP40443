<?php
// students/edit.php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
// Both admin and user can edit (user = limited update per rubric)
require_once '../config/db.php';

$id = intval($_GET['id'] ?? 0);
if ($id === 0) { header("Location: index.php"); exit; }

// Fetch existing record - prepared statement
$stmt = $conn->prepare("
    SELECT s.*, c.id AS course_id
    FROM students s JOIN courses c ON s.course_id = c.id
    WHERE s.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    $_SESSION['flash'] = 'Error: Student not found.';
    header("Location: index.php");
    exit;
}

$errors = [];
$data = $student; // pre-fill form

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isAdmin = $_SESSION['role'] === 'admin';

    // Admin can change everything; user can only change GPA and phone
    $data['phone']  = trim($_POST['phone'] ?? '');
    $data['gpa']    = trim($_POST['gpa']   ?? '');

    if ($isAdmin) {
        $data['student_no'] = trim($_POST['student_no'] ?? '');
        $data['name']       = trim($_POST['name']       ?? '');
        $data['email']      = trim($_POST['email']      ?? '');
        $data['dob']        = trim($_POST['dob']        ?? '');
        $data['gender']     = trim($_POST['gender']     ?? '');
        $data['course_id']  = intval($_POST['course_id'] ?? 0);
    }

    // Validation
    if ($isAdmin) {
        if ($data['student_no'] === '') $errors[] = 'Student number is required.';
        if ($data['name'] === '')       $errors[] = 'Full name is required.';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if ($data['course_id'] === 0)   $errors[] = 'Please select a course.';
    }
    $gpa = floatval($data['gpa']);
    if ($gpa < 0 || $gpa > 4.0) $errors[] = 'GPA must be between 0.00 and 4.00.';

    if ($isAdmin && empty($errors)) {
        // Check duplicate (exclude self)
        $stmt2 = $conn->prepare("SELECT id FROM students WHERE (student_no = ? OR email = ?) AND id != ?");
        $stmt2->bind_param("ssi", $data['student_no'], $data['email'], $id);
        $stmt2->execute();
        $stmt2->store_result();
        if ($stmt2->num_rows > 0) $errors[] = 'Student number or email already in use by another record.';
        $stmt2->close();
    }

    if (empty($errors)) {
        if ($isAdmin) {
            $dob = $data['dob'] ?: null;
            $stmt3 = $conn->prepare("
                UPDATE students SET course_id=?, student_no=?, name=?, email=?, phone=?, dob=?, gender=?, gpa=?
                WHERE id=?
            ");
            $stmt3->bind_param("issssssdi",
                $data['course_id'], $data['student_no'], $data['name'],
                $data['email'], $data['phone'], $dob, $data['gender'], $gpa, $id
            );
        } else {
            // Regular user: limited update
            $stmt3 = $conn->prepare("UPDATE students SET phone=?, gpa=? WHERE id=?");
            $stmt3->bind_param("sdi", $data['phone'], $gpa, $id);
        }

        if ($stmt3->execute()) {
            $_SESSION['flash'] = 'Student updated successfully!';
            header("Location: index.php");
            exit;
        } else {
            $errors[] = 'Database error. Please try again.';
        }
        $stmt3->close();
    }
}

$courses = $conn->query("SELECT id, code, name FROM courses ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$isAdmin = $_SESSION['role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Student — SMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>body{background:#f0f4f8}</style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container py-4" style="max-width:680px">
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="index.php" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h4 class="mb-0 fw-bold">Edit Student</h4>
            <?php if (!$isAdmin): ?>
            <small class="text-muted">You can update Phone and GPA only.</small>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm" style="border-radius:12px">
        <div class="card-body p-4">
            <form method="POST" novalidate>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Student Number</label>
                        <input type="text" name="student_no" class="form-control"
                               value="<?= htmlspecialchars($data['student_no']) ?>"
                               <?= !$isAdmin ? 'readonly' : '' ?>>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Course</label>
                        <?php if ($isAdmin): ?>
                        <select name="course_id" class="form-select">
                            <?php foreach ($courses as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $data['course_id']==$c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['code'].' - '.$c['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php else: ?>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($data['course_id']) ?>" readonly>
                        <?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Full Name</label>
                        <input type="text" name="name" class="form-control"
                               value="<?= htmlspecialchars($data['name']) ?>"
                               <?= !$isAdmin ? 'readonly' : '' ?>>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= htmlspecialchars($data['email']) ?>"
                               <?= !$isAdmin ? 'readonly' : '' ?>>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Phone <?= !$isAdmin ? '<span class="badge bg-success ms-1 small">Editable</span>' : '' ?></label>
                        <input type="text" name="phone" class="form-control"
                               value="<?= htmlspecialchars($data['phone']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Date of Birth</label>
                        <input type="date" name="dob" class="form-control"
                               value="<?= htmlspecialchars($data['dob']) ?>"
                               <?= !$isAdmin ? 'readonly' : '' ?>>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Gender</label>
                        <?php if ($isAdmin): ?>
                        <select name="gender" class="form-select">
                            <?php foreach (['Male','Female','Other'] as $g): ?>
                            <option value="<?= $g ?>" <?= $data['gender']===$g ? 'selected' : '' ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php else: ?>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($data['gender']) ?>" readonly>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">GPA <?= !$isAdmin ? '<span class="badge bg-success ms-1 small">Editable</span>' : '' ?></label>
                        <input type="number" name="gpa" class="form-control" step="0.01" min="0" max="4"
                               value="<?= htmlspecialchars($data['gpa']) ?>">
                    </div>
                </div>

                <hr class="my-4">
                <div class="d-flex gap-2 justify-content-end">
                    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check-lg me-1"></i> Update Student
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
