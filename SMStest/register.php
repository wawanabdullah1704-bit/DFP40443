<?php
// register.php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm_password']);

    if (empty($username) || empty($password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Username already taken.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'user')");
            $stmt->bind_param("ss", $username, $hashed);

            if ($stmt->execute()) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Registration failed. Please try again.";
            }
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
    <title>SMS - Register</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f0f4f8; }
        .register-card {
            max-width: 400px;
            margin: 80px auto;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .card-header { background-color: #198754; color: white; border-radius: 10px 10px 0 0 !important; }
    </style>
</head>
<body>
<div class="container">
    <div class="card register-card">
        <div class="card-header text-center py-4">
            <h4 class="mb-0">🎓 Student Management System</h4>
            <small>Create New Account</small>
        </div>
        <div class="card-body p-4">
            <h5 class="mb-3 text-center">Register</h5>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required placeholder="Choose a username">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="Min. 6 characters">
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required placeholder="Repeat password">
                </div>
                <button type="submit" class="btn btn-success w-100">Register</button>
            </form>

            <div class="mt-3 text-center">
                <small class="text-muted">Already have an account? <a href="login.php">Login here</a></small>
            </div>
        </div>
    </div>
</div>
</body>
</html>
