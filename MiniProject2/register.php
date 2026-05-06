<?php
require 'db.php';
$message = '';
$username = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = htmlspecialchars(trim($_POST['username']));
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ss", $username, $hashed_password);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "<div class='alert alert-success'>Account created! <a href='login.php'>Login here</a></div>";
            $username = ''; 
        } else {
            $message = "<div class='alert alert-danger'>Error creating account.</div>";
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Organizer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-light d-flex align-items-center min-vh-100">
    
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-5">
                
                <div class="p-4 rounded shadow-sm bg-secondary bg-opacity-10">
                    <h2 class="text-center mb-4">Register Organizer</h2>
                    
                    <?php echo $message; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <input type="text" name="username" class="form-control form-control-lg" placeholder="Username" value="<?php echo $username; ?>" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" name="password" class="form-control form-control-lg" placeholder="Password" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100">Register</button>
                    </form>
                    
                    <p class="mt-4 text-center mb-0">Already have an account? <a href="login.php" class="text-info text-decoration-none">Login</a></p>
                </div>

            </div>
        </div>
    </div>

</body>
</html>