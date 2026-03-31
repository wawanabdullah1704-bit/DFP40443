<?php
session_start();
include 'config/db.php';
if($_SESSION['role'] != 'Admin') header("Location: manage_students.php");

if (isset($_POST['add'])) {
    $reg = $_POST['reg_no'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $course = $_POST['course_id'];

    $stmt = $conn->prepare("INSERT INTO students (reg_no, name, email, course_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $reg, $name, $email, $course);
    
    if ($stmt->execute()) {
        header("Location: manage_students.php?success=1");
    }
}
$courses = $conn->query("SELECT * FROM courses");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Student</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5">
    <div class="container col-md-6 border p-4 bg-white shadow">
        <h3>Add New Student</h3>
        <form method="POST">
            <div class="mb-3"><label>Registration No</label><input type="text" name="reg_no" class="form-control" required></div>
            <div class="mb-3"><label>Full Name</label><input type="text" name="name" class="form-control" required></div>
            <div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control" required></div>
            <div class="mb-3">
                <label>Course</label>
                <select name="course_id" class="form-select">
                    <?php while($c = $courses->fetch_assoc()): ?>
                        <option value="<?php echo $c['course_id']; ?>"><?php echo $c['course_name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" name="add" class="btn btn-success">Save Student</button>
            <a href="manage_students.php" class="btn btn-link">Cancel</a>
        </form>
    </div>
</body>
</html>