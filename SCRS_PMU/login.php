<?php
// 1. Mulakan sesi (session)
session_start();

// 2. Panggil sambungan pangkalan data
require 'db.php';

$error_message = "";

// 3. Semak jika butang Sign In ditekan
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $username = htmlspecialchars($_POST['username']);
    $password = $_POST['password'];
    $user_found = false; // Penanda jika username dijumpai

    // --- SEMAKAN 1: JADUAL ADMINS (JHEPP) ---
    $sql_admin = "SELECT id, username, password, full_name FROM admins WHERE username = ?";
    $stmt = $conn->prepare($sql_admin);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res_admin = $stmt->get_result();

    if ($res_admin->num_rows > 0) {
        $user_found = true;
        $row = $res_admin->fetch_assoc();
        
        if (password_verify($password, $row['password'])) {
            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = 'admin';
            
            header("Location: verify_account.php"); 
            exit();
        } else {
            $error_message = '<div class="alert alert-danger">Ralat: Kata laluan salah!</div>';
        }
    }
    $stmt->close();

    // --- SEMAKAN 2: JADUAL STUDENTS ---
    if (!$user_found) {
        $sql_student = "SELECT id, username, password, full_name, status FROM students WHERE username = ?";
        $stmt = $conn->prepare($sql_student);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res_student = $stmt->get_result();

        if ($res_student->num_rows > 0) {
            $user_found = true;
            $row = $res_student->fetch_assoc();
            
            if (password_verify($password, $row['password'])) {
                
                // --- PENAMBAHBAIKAN SEKATAN LOGIN PELAJAR ---
                if ($row['status'] === 'pending') {
                    // Bawa ke muka surat pending (tanpa log masuk)
                    header("Location: pending.php");
                    exit();
                } else if ($row['status'] === 'rejected') {
                    // Papar mesej ditolak (tanpa log masuk)
                    $error_message = '<div class="alert alert-danger">Maaf, pendaftaran akaun anda telah ditolak oleh JHEPP.</div>';
                } else if ($row['status'] === 'approved') {
                    // Hanya akaun approved sahaja yang boleh cipta session (log masuk berjaya)
                    $_SESSION['student_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['role'] = 'student';
                    
                    header("Location: dashboard.php");
                    exit();
                }
                
            } else {
                $error_message = '<div class="alert alert-danger">Ralat: Kata laluan salah!</div>';
            }
        }
        $stmt->close();
    }

    // --- SEMAKAN 3: JADUAL PROVIDERS ---
    if (!$user_found) {
        $sql_provider = "SELECT id, username, password, full_name, status FROM providers WHERE username = ?";
        $stmt = $conn->prepare($sql_provider);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res_provider = $stmt->get_result();

        if ($res_provider->num_rows > 0) {
            $user_found = true;
            $row = $res_provider->fetch_assoc();
            
            if (password_verify($password, $row['password'])) {
                
                // --- PENAMBAHBAIKAN SEKATAN LOGIN PROVIDER ---
                if ($row['status'] === 'pending') {
                    header("Location: pending.php");
                    exit();
                } else if ($row['status'] === 'rejected') {
                    $error_message = '<div class="alert alert-danger">Maaf, pendaftaran akaun Provider anda telah ditolak.</div>';
                } else if ($row['status'] === 'approved') {
                    $_SESSION['provider_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['role'] = 'provider';
                    
                    header("Location: provider_dashboard.php");
                    exit();
                }

            } else {
                $error_message = '<div class="alert alert-danger">Ralat: Kata laluan salah!</div>';
            }
        }
        $stmt->close();
    }

    // --- KESIMPULAN JIKA USERNAME TIADA DALAM KETIGA-TIGA JADUAL ---
    if (!$user_found) {
        $error_message = '<div class="alert alert-danger">Ralat: Username tidak wujud!</div>';
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login SCRS PMU</title>
    <style>
        /* Tetapkan body sebagai flex supaya footer boleh diletakkan di bawah */
        body { 
            background-color: #f8f9fa; 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar-brand { font-weight: bold; color: #0d6efd !important; }
        .car-card img { height: 180px; object-fit: cover; }
        
        /* Jadikan ruang tengah fleksibel supaya footer kekal di bawah */
        .main-content {
            flex-grow: 1;
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container-fluid">
            <button class="btn btn-light border-0 me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu">
                <i class="bi bi-list fs-4"></i>
            </button>

            <a class="navbar-brand me-auto" href="#">SCRS PMU</a>
        </div>
    </nav>

    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title text-primary fw-bold" id="sidebarMenuLabel">Main Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="#">
                        <i class="bi bi-person-circle text-primary me-3 fs-4"></i> My Profile
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="choose_role.php">
                        <i class="bi bi-house-door text-secondary me-3 fs-4"></i> Choose Role
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Gunakan div wrapper ini supaya halaman mengembang dengan betul -->
    <div class="main-content">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-5">
                    <div class="card shadow-sm border-0 p-3">
                        <div class="container mt-3">
                            <h2 class="text-center mb-4 text-primary fw-bold">Sign In</h2>
                            
                            <?php echo $error_message; ?>

                            <form class="mb-3" action="" method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-bold" for="username">Username</label>
                                    <input class="form-control" type="text" name="username" id="username" placeholder="Enter username" required>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-bold" for="password">Password</label>
                                    <input class="form-control" type="password" name="password" id="password" placeholder="Enter password" required>
                                </div>
                                
                                <input type="submit" value="Sign In" name="sign_in" id="sign_in" class="btn btn-primary form-control mb-3">
                                
                                <p class="text-center">Don't have an account? <a href="choose_role.php" class="text-primary text-decoration-none fw-bold">Sign Up here!</a></p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- COPYRIGHT FOOTER/WATERMARK -->
    <footer class="text-center py-3 mt-auto text-secondary">
        <small>&copy; <?php echo date("Y"); ?> SCRS PMU. Hak Cipta Terpelihara.</small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>