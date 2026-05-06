<?php
session_start();
require 'db.php';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = mysqli_prepare($conn, "SELECT id, password FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['admin_name'] = $username;
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "<div class='alert alert-danger'>Incorrect password.</div>";
        }
    } else {
        $error = "<div class='alert alert-danger'>Username not found.</div>";
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hub Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-light d-flex align-items-center min-vh-100">
    
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-5">
                
                <div class="p-4 rounded shadow-sm bg-secondary bg-opacity-10">
                    <h2 class="text-center mb-4">Tournament Hub Login</h2>
                    
                    <?php echo $error; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <input type="text" name="username" class="form-control form-control-lg" placeholder="Username" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" name="password" class="form-control form-control-lg" placeholder="Password" required>
                        </div>
                        <button type="submit" class="btn btn-success btn-lg w-100">Login</button>
                    </form>
                    
                    <p class="mt-4 text-center mb-0">Need an account? <a href="register.php" class="text-info text-decoration-none">Register</a></p>
                </div>

            </div>
        </div>
    </div>

</body>
</html>