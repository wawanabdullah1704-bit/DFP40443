<?php
session_start();
include 'config/db.php';

// Security: Only Admin can access this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: manage_students.php");
    exit();
}

// Fetch existing student data
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $student = $result->fetch_assoc();
    } else {
        header("Location: manage_students.php?error=StudentNotFound");
        exit();
    }
} else {
    header("Location: manage_students.php");
    exit();
}

// Handle form submission for updating
if (isset($_POST['update'])) {
    $reg = $_POST['reg_no'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $course = $_POST['course_id'];
    $student_id = $_POST['id'];

    $update_stmt = $conn->prepare("UPDATE students SET reg_no=?, name=?, email=?, course_id=? WHERE id=?");
    $update_stmt->bind_param("sssii", $reg, $name, $email, $course, $student_id);
    
    if ($update_stmt->execute()) {
        header("Location: manage_students.php?success=StudentUpdated");
        exit();
    } else {
        $error = "Error updating record: " . $conn->error;
    }
}

// Fetch courses for the dropdown
$courses = $conn->query("SELECT * FROM courses");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-5">
    <div class="container col-md-6 border p-4 bg-white shadow rounded">
        <h3 class="mb-4">Edit Student Record</h3>
        
        <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
            
            <div class="mb-3">
                <label class="form-label">Registration No</label>
                <input type="text" name="reg_no" class="form-control" value="<?php echo htmlspecialchars($student['reg_no']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($student['name']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($student['email']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Course</label>
                <select name="course_id" class="form-select" required>
                    <?php while($c = $courses->fetch_assoc()): ?>
                        <option value="<?php echo $c['course_id']; ?>" <?php if($student['course_id'] == $c['course_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($c['course_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <button type="submit" name="update" class="btn btn-warning text-dark fw-bold">Update Student</button>
            <a href="manage_students.php" class="btn btn-secondary ms-2">Cancel</a>
        </form>
    </div>
</body>
</html>