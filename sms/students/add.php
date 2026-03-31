<?php
// students/add.php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
if ($_SESSION['role'] !== 'admin') { header("Location: index.php"); exit; }
require_once '../config/db.php';

$errors = [];
$data   = ['student_no'=>'','name'=>'','email'=>'','phone'=>'','dob'=>'','gender'=>'Male','gpa'=>'','course_id'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'student_no' => trim($_POST['student_no'] ?? ''),
        'name'       => trim($_POST['name']       ?? ''),
        'email'      => trim($_POST['email']      ?? ''),
        'phone'      => trim($_POST['phone']      ?? ''),
        'dob'        => trim($_POST['dob']        ?? ''),
        'gender'     => trim($_POST['gender']     ?? ''),
        'gpa'        => trim($_POST['gpa']        ?? ''),
        'course_id'  => intval($_POST['course_id'] ?? 0),
    ];

    // Validation
    if ($data['student_no'] === '') $errors[] = 'Student number is required.';
    if ($data['name'] === '')       $errors[] = 'Full name is required.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if ($data['course_id'] === 0)   $errors[] = 'Please select a course.';
    if (!in_array($data['gender'], ['Male','Female','Other'])) $errors[] = 'Invalid gender.';
    $gpa = floatval($data['gpa']);
    if ($gpa < 0 || $gpa > 4.0)    $errors[] = 'GPA must be between 0.00 and 4.00.';

    if (empty($errors)) {
        // Check duplicate student_no / email - prepared statement
        $stmt = $conn->prepare("SELECT id FROM students WHERE student_no = ? OR email = ?");
        $stmt->bind_param("ss", $data['student_no'], $data['email']);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = 'Student number or email already exists.';
            $stmt->close();
        } else {
            $stmt->close();
            $dob = $data['dob'] ?: null;
            $stmt2 = $conn->prepare("
                INSERT INTO students (course_id, student_no, name, email, phone, dob, gender, gpa)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt2->bind_param("issssssd",
                $data['course_id'], $data['student_no'], $data['name'],
                $data['email'], $data['phone'], $dob, $data['gender'], $gpa
            );
            if ($stmt2->execute()) {
                $_SESSION['flash'] = 'Student added successfully!';
                header("Location: index.php");
                exit;
            } else {
                $errors[] = 'Database error. Please try again.';
            }
            $stmt2->close();
        }
    }
}

$courses = $conn->query("SELECT id, code, name FROM courses ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Student — SMS</title>
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
        <h4 class="mb-0 fw-bold">Add New Student</h4>
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
                        <label class="form-label fw-semibold">Student Number <span class="text-danger">*</span></label>
                        <input type="text" name="student_no" class="form-control"
                               value="<?= htmlspecialchars($data['student_no']) ?>"
                               placeholder="e.g. STU007" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Course <span class="text-danger">*</span></label>
                        <select name="course_id" class="form-select" required>
                            <option value="">— Select course —</option>
                            <?php foreach ($courses as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $data['course_id']==$c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['code'].' - '.$c['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control"
                               value="<?= htmlspecialchars($data['name']) ?>"
                               placeholder="e.g. Ahmad bin Abdullah" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control"
                               value="<?= htmlspecialchars($data['email']) ?>"
                               placeholder="student@email.com" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Phone</label>
                        <input type="text" name="phone" class="form-control"
                               value="<?= htmlspecialchars($data['phone']) ?>"
                               placeholder="01xxxxxxxx">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Date of Birth</label>
                        <input type="date" name="dob" class="form-control"
                               value="<?= htmlspecialchars($data['dob']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select" required>
                            <?php foreach (['Male','Female','Other'] as $g): ?>
                            <option value="<?= $g ?>" <?= $data['gender']===$g ? 'selected' : '' ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">GPA (0.00 – 4.00)</label>
                        <input type="number" name="gpa" class="form-control" step="0.01" min="0" max="4"
                               value="<?= htmlspecialchars($data['gpa']) ?>"
                               placeholder="3.75">
                    </div>
                </div>

                <hr class="my-4">
                <div class="d-flex gap-2 justify-content-end">
                    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-person-plus me-1"></i> Save Student
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
