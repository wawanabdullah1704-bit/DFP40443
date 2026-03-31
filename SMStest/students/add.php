<?php
// students/add.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/db.php';

$error   = '';
$success = '';

// Get courses for dropdown
$courses = $conn->query("SELECT id, course_name, course_code FROM courses ORDER BY course_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name      = trim($_POST['student_name']);
    $no        = trim($_POST['student_no']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone']);
    $course_id = intval($_POST['course_id']);
    $status    = $_POST['status'];

    if (empty($name) || empty($no) || empty($email)) {
        $error = "Name, Student No, and Email are required.";
    } else {
        // Prepared statement (rubric requirement)
        $stmt = $conn->prepare("INSERT INTO students (student_name, student_no, email, phone, course_id, status)
                                VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssds", $name, $no, $email, $phone, $course_id, $status);

        if ($stmt->execute()) {
            header("Location: ../dashboard.php?msg=added");
            exit();
        } else {
            $error = "Failed to add student. Student No may already exist.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS - Add Student</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .form-card { max-width: 600px; margin: 40px auto; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .card-header { background-color: #0d6efd; color: white; }
    </style>
</head>
<body>
<div class="container">
    <div class="card form-card">
        <div class="card-header py-3">
            <h5 class="mb-0">➕ Add New Student</h5>
        </div>
        <div class="card-body p-4">

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="add.php">
                <div class="mb-3">
                    <label class="form-label">Student Name <span class="text-danger">*</span></label>
                    <input type="text" name="student_name" class="form-control" required placeholder="Full name">
                </div>
                <div class="mb-3">
                    <label class="form-label">Student No <span class="text-danger">*</span></label>
                    <input type="text" name="student_no" class="form-control" required placeholder="e.g. STU2026004">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required placeholder="student@email.com">
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" placeholder="01xxxxxxxx">
                </div>
                <div class="mb-3">
                    <label class="form-label">Course</label>
                    <select name="course_id" class="form-select">
                        <option value="">-- Select Course --</option>
                        <?php while ($course = $courses->fetch_assoc()): ?>
                            <option value="<?= $course['id'] ?>">
                                <?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Save Student</button>
                    <a href="../dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
