<?php
// login.php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Prepared statement for security
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Invalid username or password.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS - Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f0f4f8; }
        .login-card {
            max-width: 400px;
            margin: 100px auto;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .card-header { background-color: #0d6efd; color: white; border-radius: 10px 10px 0 0 !important; }
    </style>
</head>
<body>
<div class="container">
    <div class="card login-card">
        <div class="card-header text-center py-4">
            <h4 class="mb-0">🎓 Student Management System</h4>
            <small>Politeknik Mukah</small>
        </div>
        <div class="card-body p-4">
            <h5 class="mb-3 text-center">Login</h5>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required placeholder="Enter username">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="Enter password">
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>

            <div class="mt-3 text-center">
                <small class="text-muted">Don't have an account? <a href="register.php">Register</a></small>
            </div>
            <div class="mt-2 text-center">
                <small class="text-muted">Default: admin / password</small>
            </div>
        </div>
    </div>
</div>
</body>
</html>
