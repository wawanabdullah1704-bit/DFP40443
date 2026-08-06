<?php
session_start();
require 'db.php';

// Semak jika pengguna telah log masuk dan merupakan seorang pelajar
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_name = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Papan Pemuka Utama - SCRS PMU</title>
    <style>
        body { 
            background-color: #f8f9fa; 
            min-height: 100vh; 
            display: flex; 
            flex-direction: column; 
        }
        .navbar-brand { font-weight: bold; color: #0d6efd !important; }
        .main-content { flex-grow: 1; }
        
        /* Gaya untuk Slaid Gambar (Carousel) */
        .carousel-item {
            height: 400px;
        }
        .carousel-item img {
            object-fit: cover;
            height: 100%;
            width: 100%;
            filter: brightness(0.5); /* Gelapkan sikit gambar supaya teks jelas */
        }
        .carousel-caption {
            bottom: 30%;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.8);
        }

        /* Gaya untuk Kad Menu (Hover Effect) */
        .hover-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            border-radius: 15px;
            border: none;
        }
        .hover-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
        }
        .card-icon {
            font-size: 3.5rem;
            margin-bottom: 15px;
        }
    </style>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container-fluid">
            <button class="btn btn-light border-0 me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
                <i class="bi bi-list fs-4"></i>
            </button>
            <a class="navbar-brand me-auto" href="#">SCRS PMU</a>
            
            <span class="navbar-text d-none d-md-block fw-bold text-primary">
                Hai, <?php echo htmlspecialchars($student_name); ?>
            </span>
        </div>
    </nav>

    <!-- OFFCANVAS MENU (SIDEBAR) -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarMenu">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title text-primary fw-bold">Menu Pelajar</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center bg-light rounded" href="dashboard.php">
                        <i class="bi bi-house-door text-primary me-3 fs-4"></i> Papan Pemuka
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="booking.php">
                        <i class="bi bi-search text-success me-3 fs-4"></i> Cari & Tempah Kereta
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="my_bookings.php">
                        <i class="bi bi-card-checklist text-warning me-3 fs-4"></i> Status Tempahan
                    </a>
                </li>
                <li class="nav-item mb-2 mt-auto">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="login.php">
                        <i class="bi bi-box-arrow-left text-danger me-3 fs-4"></i> Log Keluar
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- KANDUNGAN UTAMA -->
    <div class="main-content">
        
        <!-- CAROUSEL (SLAID GAMBAR) & TEKS IKLAN -->
        <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
            </div>
            <div class="carousel-inner">
                <!-- Wallpaper 1 -->
                <div class="carousel-item active">
                    <!-- Nota: Gantikan link gambar ini dengan gambar pilihan anda di folder 'uploads' atau guna link web yang sesuai -->
                    <img src="https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&q=80&w=2070" class="d-block w-100" alt="Kereta 1">
                    <div class="carousel-caption d-flex flex-column align-items-center justify-content-center h-100">
                        <h1 class="fw-bold display-4 text-white">Selamat Datang, <?php echo htmlspecialchars($student_name); ?>!</h1>
                        <h3 class="text-light fw-light mb-4">Apa rancangan anda hari ini?</h3>
                        <p class="fs-5 text-white w-75 d-none d-md-block">Sistem Sewaan Kereta Pelajar (SCRS PMU) menghubungkan anda dengan kereta pilihan pelajar lain. Lebih mudah, lebih cepat, dan pada harga yang berpatutan!</p>
                    </div>
                </div>
                <!-- Wallpaper 2 -->
                <div class="carousel-item">
                    <img src="https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?auto=format&fit=crop&q=80&w=2070" class="d-block w-100" alt="Kereta 2">
                    <div class="carousel-caption d-flex flex-column align-items-center justify-content-center h-100">
                        <h1 class="fw-bold display-4 text-white">Perjalanan Tanpa Had</h1>
                        <h3 class="text-light fw-light mb-4">Urusan luar atau tugasan kampus?</h3>
                        <p class="fs-5 text-white w-75 d-none d-md-block">Jelajahi kebebasan memandu tanpa risau. Pilih mengikut bajet anda - sama ada sewaan mengikut jam atau sewaan harian. Segalanya di hujung jari anda.</p>
                    </div>
                </div>
                <!-- Wallpaper 3 -->
                <div class="carousel-item">
                    <img src="https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&fit=crop&q=80&w=2066" class="d-block w-100" alt="Kereta 3">
                    <div class="carousel-caption d-flex flex-column align-items-center justify-content-center h-100">
                        <h1 class="fw-bold display-4 text-white">Dipercayai & Selamat</h1>
                        <h3 class="text-light fw-light mb-4">Kami mengutamakan keselamatan anda</h3>
                        <p class="fs-5 text-white w-75 d-none d-md-block">Setiap penyedia kereta dan pelajar telah melepasi proses pengesahan oleh pihak JHEPP. Nikmati perjalanan anda dengan tenang dan selamat.</p>
                    </div>
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Sebelum</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Seterusnya</span>
            </button>
        </div>

        <!-- 3 KAD MENU PANTAS -->
        <div class="container mt-5 mb-5">
            <div class="text-center mb-5">
                <h3 class="fw-bold text-dark">Menu Pantas Anda</h3>
                <p class="text-secondary">Pilih tindakan di bawah untuk memulakan urusan anda.</p>
            </div>

            <div class="row g-4 px-2 px-md-5">
                
                <!-- KAD 1: Mula Tempah -->
                <div class="col-md-4">
                    <div class="card hover-card shadow-sm h-100 bg-white text-center p-4">
                        <div class="card-body">
                            <i class="bi bi-car-front-fill text-primary card-icon"></i>
                            <h4 class="fw-bold">Mula Tempah</h4>
                            <p class="text-muted mt-2">Cari kereta yang tersedia dan tempah mengikut kesesuaian waktu anda. Kadar harian dan jam tersedia.</p>
                            <!-- Stretched link menyebabkan seluruh kotak kad boleh diklik -->
                            <a href="booking.php" class="stretched-link text-decoration-none"></a>
                        </div>
                    </div>
                </div>

                <!-- KAD 2: Status/Senarai Tempahan -->
                <div class="col-md-4">
                    <div class="card hover-card shadow-sm h-100 bg-white text-center p-4">
                        <div class="card-body">
                            <i class="bi bi-clipboard-check-fill text-success card-icon"></i>
                            <h4 class="fw-bold">Status Tempahan</h4>
                            <p class="text-muted mt-2">Semak sama ada tempahan terbaharu anda telah diluluskan, ditolak, atau masih dalam proses menunggu pengesahan penyedia.</p>
                            <a href="my_bookings.php" class="stretched-link text-decoration-none"></a>
                        </div>
                    </div>
                </div>

                <!-- KAD 3: Sejarah Tempahan -->
                <div class="col-md-4">
                    <div class="card hover-card shadow-sm h-100 bg-white text-center p-4">
                        <div class="card-body">
                            <i class="bi bi-clock-history text-warning card-icon"></i>
                            <h4 class="fw-bold">Sejarah Tempahan</h4>
                            <p class="text-muted mt-2">Lihat kembali rekod tempahan lama anda yang telah selesai atau dibatalkan untuk rujukan masa hadapan.</p>
                            <a href="booking_history.php" class="stretched-link text-decoration-none"></a>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- FOOTER -->
    <footer class="text-center py-4 mt-auto border-top bg-white text-secondary">
        <small>&copy; <?php echo date("Y"); ?> SCRS PMU. Hak Cipta Terpelihara.</small>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>