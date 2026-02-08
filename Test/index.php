<?php
session_start();
if (isset($_POST['start'])) {
    $_SESSION['username'] = htmlspecialchars($_POST['username']); // Sanitize input 
    $_SESSION['score'] = 0;
    $_SESSION['current_index'] = 0;
    $_SESSION['mistakes'] = []; // Track incorrect answers 
    header("Location: quiz.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Login - PHP Quiz</title>
</head>
<body class="container mt-5">
    <h2>PHP Knowledge Questions</h2>
    <form method="post">
        <div class="mb-3">
            <label>Enter Name:</label>
            <input type="text" name="username" class="form-control" required>
        </div>
        <button type="submit" name="start" class="btn btn-primary">Start Quiz</button>
    </form>
</body>
</html>