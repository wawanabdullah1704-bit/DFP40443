<?php
require_once 'db.php';

// 2. Back-end Logic
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capture the POST data
    $name = trim($_POST['name']);
    $course = trim($_POST['course']);

    // Prepare an INSERT INTO statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO students (name, course) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $course);

    if ($stmt->execute()) {
        // Automatically redirect back to index.php upon successful insertion
        header("Location: index.php");
        exit();
    } else {
        echo "<p style='color:red;'>Error saving record: " . $stmt->error . "</p>";
    }
    
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Student</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"] { width: 300px; padding: 8px; }
        input[type="submit"] { padding: 8px 15px; cursor: pointer; }
    </style>
</head>
<body>
    <h2>Add New Student</h2>
    
    <form action="add.php" method="POST">
        <div class="form-group">
            <label for="name">Student Name</label>
            <input type="text" id="name" name="name" required>
        </div>
        
        <div class="form-group">
            <label for="course">Course</label>
            <input type="text" id="course" name="course" required>
        </div>
        
        <div class="form-group">
            <input type="submit" value="Submit">
        </div>
    </form>

    <br>
    <a href="index.php">Back to Student Directory</a>
</body>
</html>