<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Role - SCRS PMU</title>
    <style>
        body { background-color: #f8f9fa; }
        .navbar-brand { font-weight: bold; color: #0d6efd !important; }
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
            <h5 class="offcanvas-title text-primary fw-bold" id="sidebarMenuLabel">Menu Utama</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="login.php">
                        <i class="bi bi-box-arrow-in-right text-primary me-3 fs-4"></i> Log In
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="index.php">
                        <i class="bi bi-house-door text-secondary me-3 fs-4"></i> Laman Utama
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- MAIN CONTENT - CHOOSE ROLE -->
    <div class="container mt-5 pt-4">
        <div class="row justify-content-center">
            <div class="col-md-5 col-sm-8 text-center">
                
                <h1 class="fw-bold mb-5 text-dark">Choose your<br>role</h1>
                
                <a href="register_student.php" class="btn btn-primary w-100 mb-4 py-3 rounded-4 shadow-sm d-flex flex-column align-items-center">
                    <span class="fs-6">Sign up as</span>
                    <span class="fs-5 fw-bold">Student</span>
                </a>
                
                <a href="register_provider.php" class="btn btn-primary w-100 mb-4 py-3 rounded-4 shadow-sm d-flex flex-column align-items-center">
                    <span class="fs-6">Sign up as</span>
                    <span class="fs-5 fw-bold">Car Provider</span>
                </a>

                <p class="mt-4">Already have an account? <a href="login.php" class="text-primary text-decoration-none fw-bold">Sign In here</a></p>

            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>