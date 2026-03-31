<?php
session_start();
include 'config/db.php';

$search = $_GET['search'] ?? '';
$query = "SELECT students.*, courses.course_name 
          FROM students 
          JOIN courses ON students.course_id = courses.course_id 
          WHERE students.name LIKE ? OR students.reg_no LIKE ? 
          ORDER BY students.id DESC";

$stmt = $conn->prepare($query);
$search_term = "%$search%";
$stmt->bind_param("ss", $search_term, $search_term);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Students</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Student Records</h3>
            <form class="d-flex w-50">
                <input type="text" name="search" class="form-control me-2" placeholder="Search by name or Reg No..." value="<?php echo $search; ?>">
                <button type="submit" class="btn btn-outline-primary">Search</button>
            </form>
            <?php if($_SESSION['role'] == 'Admin'): ?>
                <a href="add_student.php" class="btn btn-primary">+ Add Student</a>
            <?php endif; ?>
        </div>

        <table class="table table-bordered table-striped bg-white shadow-sm">
            <thead class="table-dark">
                <tr>
                    <th>Reg No</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Course</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['reg_no']; ?></td>
                    <td><?php echo $row['name']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td><?php echo $row['course_name']; ?></td>
                    <td>
                        <?php if($_SESSION['role'] == 'Admin'): ?>
                            <a href="edit_student.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="delete_student.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</body>
</html>