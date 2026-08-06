<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tunggu Pengesahan - SCRS PMU</title>
    <style>
        /* Tetapkan body sebagai flex supaya footer kekal berada di bahagian bawah */
        body { 
            background-color: #f8f9fa; 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar-brand { font-weight: bold; color: #0d6efd !important; }
        .main-content { flex-grow: 1; }
        .verify-text { color: #5a6268; line-height: 1.4; }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    
    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container-fluid">
            <button class="btn btn-light border-0 me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu">
                <i class="bi bi-list fs-4"></i>
            </button>

            <a class="navbar-brand me-auto" href="#">SCRS PMU</a>
        </div>
    </nav>

    <!-- OFFCANVAS MENU -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title text-primary fw-bold" id="sidebarMenuLabel">Menu Utama</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="login.php">
                        <i class="bi bi-box-arrow-in-right text-primary me-3 fs-4"></i> Log Masuk
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

    <!-- KANDUNGAN UTAMA (Menunggu Pengesahan) -->
    <div class="main-content d-flex justify-content-center align-items-center py-5">
        <div class="text-center px-4">
            <i class="bi bi-hourglass-split text-primary display-1 mb-4 d-block"></i>
            <h2 class="fw-bold verify-text mb-4">
                Sila tunggu pihak JHEPP membuat<br>pengesahan akaun anda....
            </h2>
            <p class="text-muted mb-4">Proses semakan dokumen mungkin mengambil sedikit masa. Anda boleh cuba log masuk semula kemudian.</p>
            <a href="login.php" class="btn btn-primary px-4 py-2 rounded-3 fw-bold">Kembali ke Log Masuk</a>
        </div>
    </div>
    
    <!-- COPYRIGHT FOOTER/WATERMARK -->
    <footer class="text-center py-3 mt-auto text-secondary">
        <small>&copy; <?php echo date("Y"); ?> SCRS PMU. Hak Cipta Terpelihara.</small>
    </footer>

    <!-- Script Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>