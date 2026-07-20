<?php
// 1. Mulakan sesi (session) untuk menyimpan maklumat log masuk
session_start();

// 2. Panggil sambungan pangkalan data
require 'db.php';

$error_message = "";

// 3. Semak jika butang Sign In ditekan
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Ambil data dari borang
    $username = htmlspecialchars($_POST['username']);
    $password = $_POST['password'];

    // 4. Cari pengguna dalam database berdasarkan username
    $sql = "SELECT id, username, password, full_name FROM students WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // Jika username wujud
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // 5. Sahkan kata laluan (bandingkan password yang ditaip dengan hash di database)
        if (password_verify($password, $row['password'])) {
            
            // Log masuk berjaya! Simpan maklumat pelajar ke dalam session
            $_SESSION['student_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['full_name'] = $row['full_name'];
            
            // Bawa pengguna ke laman utama (contoh: home.php atau index.php)
            // Anda boleh tukar 'home.php' ini ke fail yang sepatutnya
            header("Location: dashboard.php");
            exit();
            
        } else {
            $error_message = '<div class="alert alert-danger">Ralat: Kata laluan salah!</div>';
        }
    } else {
        $error_message = '<div class="alert alert-danger">Ralat: Username tidak wujud!</div>';
    }
    
    $stmt->close();
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
        body { background-color: #f8f9fa; }
        .navbar-brand { font-weight: bold; color: #0d6efd !important; }
        .car-card img { height: 180px; object-fit: cover; }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body>
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
            <h5 class="offcanvas-title text-primary fw-bold" id="sidebarMenuLabel">Menu Utama</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="#">
                        <i class="bi bi-person-circle text-primary me-3 fs-4"></i> Profile Saya
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="#">
                        <i class="bi bi-house-door text-secondary me-3 fs-4"></i> Laman Utama
                    </a>
                </li>
            </ul>
        </div>
    </div>


    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-5"> <!-- Mengecilkan saiz kotak login supaya lebih kemas -->
                <div class="card shadow-sm border-0 p-3">
                    <div class="container mt-3">
                        <h2 class="text-center mb-4 text-primary fw-bold">Sign In</h2>
                        
                        <!-- Paparkan mesej ralat di sini jika ada -->
                        <?php echo $error_message; ?>

                        <!-- Tambah method="POST" supaya data boleh ditangkap oleh PHP -->
                        <form class="mb-3" action="" method="POST">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold" for="username">Username</label>
                                <input class="form-control" type="text" name="username" id="username" placeholder="Enter username" required>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold" for="password">Password</label>
                                <!-- Tambah name="password" di sini -->
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
    
    <!-- Script Bootstrap diperlukan untuk membolehkan Offcanvas berfungsi -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>