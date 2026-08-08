<?php
session_start();
require 'db.php';

// Semak jika pengguna telah log masuk dan merupakan seorang pelajar
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['username'];

// --- KIRAAN STATISTIK PAPAN PEMUKA ---
$sql_cars = "SELECT COUNT(*) as total FROM cars WHERE status = 'Available'";
$res_cars = $conn->query($sql_cars);
$total_cars = $res_cars->fetch_assoc()['total'] ?? 0;

$sql_users = "SELECT COUNT(*) as total FROM students WHERE status = 'approved'";
$res_users = $conn->query($sql_users);
$total_users = $res_users->fetch_assoc()['total'] ?? 0;

$sql_my_books = "SELECT COUNT(*) as total FROM bookings WHERE student_id = ?";
$stmt_my = $conn->prepare($sql_my_books);
$stmt_my->bind_param("i", $student_id);
$stmt_my->execute();
$total_my_bookings = $stmt_my->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_my->close();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Papan Pemuka Utama - SCRS PMU</title>
    
    <!-- Ikon Bootstrap (Hanya ikon, bukan framework CSS Bootstrap) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700;900&display=swap" rel="stylesheet">

    <!-- PURE CSS - NEO BRUTALISM -->
    <style>
        :root {
            --black: #000000;
            --white: #ffffff;
            --yellow: #ffde59;
            --green: #00e676;
            --blue: #00e5ff;
            --pink: #ff66c4;
            --bg-color: #f4f4f0;
            --border-thick: 4px solid var(--black);
            --shadow-solid: 6px 6px 0px var(--black);
            --shadow-hover: 4px 4px 0px var(--black);
            --shadow-active: 0px 0px 0px var(--black);
            --transition: all 0.15s ease-in-out;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Space Grotesk', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            background-image: radial-gradient(#ccc 1.5px, transparent 1.5px);
            background-size: 20px 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        a { text-decoration: none; color: inherit; }
        ul { list-style: none; }
        button { border: none; background: none; cursor: pointer; font-family: inherit; }

        /* --- NAVBAR --- */
        .neo-navbar {
            background-color: var(--white);
            border-bottom: var(--border-thick);
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .neo-nav-left { display: flex; align-items: center; gap: 15px; }
        
        .menu-toggle-btn {
            font-size: 2rem;
            color: var(--black);
            transition: var(--transition);
        }
        .menu-toggle-btn:hover { transform: scale(1.1); }

        .neo-brand {
            font-size: 1.5rem;
            font-weight: 900;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        /* --- DROPDOWN PROFIL --- */
        .profile-container { position: relative; }
        
        .profile-btn {
            background-color: var(--yellow);
            border: 3px solid var(--black);
            box-shadow: 4px 4px 0px var(--black);
            padding: 8px 15px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }
        .profile-btn:hover { transform: translate(-2px, -2px); box-shadow: var(--shadow-solid); }
        .profile-btn:active { transform: translate(4px, 4px); box-shadow: var(--shadow-active); }

        .dropdown-menu {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            background-color: var(--white);
            border: 3px solid var(--black);
            box-shadow: 6px 6px 0px var(--black);
            width: 200px;
            display: none;
            flex-direction: column;
            z-index: 1050;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .dropdown-menu.show { display: flex; }
        .dropdown-menu li { width: 100%; margin: 0; padding: 0; }
        
        .dropdown-item {
            display: flex;
            align-items: center;
            width: 100%;
            padding: 12px 15px;
            font-weight: 800;
            color: var(--black);
            border-bottom: 2px solid var(--black);
            transition: background 0.1s;
            text-decoration: none;
        }
        .dropdown-item:last-child { border-bottom: none; background-color: var(--pink); }
        .dropdown-item:hover { background-color: var(--yellow); }
        .dropdown-item:last-child:hover { background-color: #ff33aa; }

        /* --- SIDEBAR (OFFCANVAS) --- */
        .sidebar-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1005;
            display: none;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .sidebar-overlay.show { display: block; opacity: 1; }

        .sidebar {
            position: fixed;
            top: 0; left: -300px;
            width: 280px; height: 100%;
            background-color: var(--bg-color);
            border-right: var(--border-thick);
            z-index: 1010;
            transition: left 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .sidebar.open { left: 0; }
        
        .sidebar-header {
            padding: 20px;
            background-color: var(--yellow);
            border-bottom: var(--border-thick);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .sidebar-header h2 { font-weight: 900; text-transform: uppercase; font-size: 1.2rem; }
        .close-btn {
            border: 3px solid var(--black);
            background: var(--white);
            padding: 5px 10px;
            font-weight: 900;
            box-shadow: 2px 2px 0px var(--black);
        }
        .close-btn:active { transform: translate(2px, 2px); box-shadow: 0px 0px 0px var(--black); }

        .sidebar-nav { padding: 20px; display: flex; flex-direction: column; gap: 10px; }
        .sidebar-link {
            padding: 12px 15px;
            border: 3px solid transparent;
            font-weight: 800;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: var(--transition);
        }
        .sidebar-link.active { border: 3px solid var(--black); background: var(--white); box-shadow: 4px 4px 0px var(--black); }
        .sidebar-link:hover { border: 3px solid var(--black); background: var(--white); transform: translate(-2px, -2px); box-shadow: 4px 4px 0px var(--black); }

        /* --- KANDUNGAN UTAMA --- */
        .main-content { flex: 1; }

        /* --- CAROUSEL (ASLI/VANILLA) --- */
        .carousel-container {
            width: 100%;
            height: 400px;
            position: relative;
            overflow: hidden;
            border-bottom: var(--border-thick);
            background-color: var(--black);
        }
        .carousel-track {
            display: flex;
            height: 100%;
            transition: transform 0.5s ease-in-out;
        }
        .carousel-slide {
            min-width: 100%;
            height: 100%;
            position: relative;
        }
        .carousel-slide img {
            width: 100%; height: 100%; object-fit: cover;
            filter: grayscale(10%) contrast(110%) brightness(0.65);
        }
        .carousel-caption {
            position: absolute;
            bottom: 15%;
            left: 50%;
            transform: translateX(-50%);
            background-color: var(--white);
            border: var(--border-thick);
            padding: 1.5rem;
            box-shadow: 8px 8px 0px var(--yellow);
            text-align: center;
            width: 90%;
            max-width: 600px;
        }
        .carousel-caption h1 { font-size: 2rem; font-weight: 900; text-transform: uppercase; margin-bottom: 5px; }
        .carousel-caption p { font-weight: 700; font-size: 1rem; color: var(--black); }

        .carousel-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: var(--white);
            border: 3px solid var(--black);
            padding: 10px 15px;
            font-size: 1.5rem;
            box-shadow: 4px 4px 0px var(--black);
            z-index: 10;
        }
        .carousel-btn:active { transform: translateY(-50%) translate(2px, 2px); box-shadow: 0px 0px 0px var(--black); }
        .btn-prev { left: 20px; }
        .btn-next { right: 20px; }

        /* --- SEKSYEN GRID (STATISTIK & MENU) --- */
        .section-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 20px;
        }
        
        .section-title {
            display: inline-block;
            background: var(--black);
            color: var(--white);
            font-weight: 900;
            text-transform: uppercase;
            padding: 10px 20px;
            box-shadow: 4px 4px 0px var(--yellow);
            margin-bottom: 20px;
        }

        .neo-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        /* Kad Neo Brutalism */
        .neo-card {
            border: var(--border-thick);
            box-shadow: var(--shadow-solid);
            padding: 20px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: var(--transition);
            position: relative;
        }
        .clickable-card { cursor: pointer; }
        .clickable-card:hover { transform: translate(-4px, -4px); box-shadow: var(--shadow-hover); }
        .clickable-card:active { transform: translate(4px, 4px); box-shadow: var(--shadow-active); }

        .neo-card h2 { font-size: 3rem; font-weight: 900; line-height: 1; margin-bottom: 5px; }
        .neo-card h4 { font-size: 1.2rem; font-weight: 900; text-transform: uppercase; margin: 10px 0 5px 0; }
        .neo-card p { font-weight: 700; font-size: 0.9rem; text-transform: uppercase; }
        .neo-card .desc { text-transform: none; font-size: 0.95rem; font-weight: 600; margin-top: 5px; }
        .neo-icon { font-size: 3rem; margin-top: 10px; }

        /* Warna Latar */
        .bg-y { background-color: var(--yellow); }
        .bg-g { background-color: var(--green); }
        .bg-b { background-color: var(--blue); }
        .bg-p { background-color: var(--pink); }
        .bg-w { background-color: var(--white); }

        /* --- FOOTER --- */
        footer {
            background-color: var(--yellow);
            border-top: var(--border-thick);
            padding: 20px;
            text-align: center;
            font-weight: 900;
            text-transform: uppercase;
            margin-top: auto;
        }

        /* --- RESPONSIVE MOBILE --- */
        @media (max-width: 768px) {
            .neo-brand { font-size: 1.2rem; }
            .profile-btn { padding: 6px 10px; font-size: 0.85rem; }
            
            .carousel-container { height: 280px; }
            .carousel-caption { bottom: 8%; padding: 10px 15px; box-shadow: 4px 4px 0px var(--yellow); }
            .carousel-caption h1 { font-size: 1.2rem; }
            .carousel-caption p { font-size: 0.85rem; }
            .carousel-btn { padding: 5px 10px; font-size: 1.2rem; }

            .section-container { padding: 1.5rem 10px; }
            
            .neo-grid { gap: 12px; grid-template-columns: repeat(3, 1fr); }
            @media (max-width: 480px) {
                .neo-grid { grid-template-columns: repeat(3, 1fr); gap: 8px; }
            }
            .neo-card { padding: 12px 8px; box-shadow: 4px 4px 0px var(--black); }
            .clickable-card:hover { transform: none; box-shadow: 4px 4px 0px var(--black); }
            .clickable-card:active { transform: translate(2px, 2px); box-shadow: 2px 2px 0px var(--black); }

            .neo-card h2 { font-size: 1.6rem; }
            .neo-card p { font-size: 0.75rem; margin-top: 3px; font-weight: 800; }
            .neo-icon { font-size: 1.5rem; margin-top: 5px; }

            .neo-card h4 { font-size: 0.9rem; margin-top: 5px; }
            .neo-card .desc { font-size: 0.75rem; margin-top: 4px; display: block; }
            
            .section-title { font-size: 1rem; padding: 8px 15px; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <header class="neo-navbar">
        <div class="neo-nav-left">
            <button class="menu-toggle-btn" id="open-sidebar">
                <i class="bi bi-list"></i>
            </button>
            <div class="neo-brand">SCRS PMU</div>
        </div>

        <div class="profile-container">
            <button class="profile-btn" id="profile-toggle">
                <i class="bi bi-person-fill fs-5"></i>
                <span><?php echo htmlspecialchars($student_name); ?></span>
            </button>
            <ul class="dropdown-menu" id="profile-menu">
                <li><a href="edit_profile.php" class="dropdown-item"><i class="bi bi-gear-fill me-2"></i> Edit Profil</a></li>
                <li><a href="login.php" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i> Log Keluar</a></li>
            </ul>
        </div>
    </header>

    <!-- SIDEBAR -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Menu Utama</h2>
            <button class="close-btn" id="close-sidebar"><i class="bi bi-x-lg"></i></button>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="sidebar-link active"><i class="bi bi-house-door-fill"></i> Papan Pemuka</a>
            <a href="booking.php" class="sidebar-link"><i class="bi bi-car-front-fill"></i> Cari & Tempah</a>
            <a href="my_bookings.php" class="sidebar-link"><i class="bi bi-clipboard-check-fill"></i> Status Tempahan</a>
            <a href="booking_history.php" class="sidebar-link"><i class="bi bi-clock-history"></i> Sejarah Rekod</a>
        </nav>
    </aside>

    <!-- KANDUNGAN UTAMA -->
    <main class="main-content">
        
        <!-- CAROUSEL -->
        <div class="carousel-container">
            <div class="carousel-track" id="carousel-track">
                <div class="carousel-slide">
                    <img src="https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&w=1000&q=80" alt="Kereta 1">
                    <div class="carousel-caption">
                        <h1>Eksplorasi Bermula</h1>
                        <p>Apa rancangan perjalanan anda hari ini?</p>
                    </div>
                </div>
                <div class="carousel-item carousel-slide">
                    <img src="https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?auto=format&fit=crop&w=1000&q=80" alt="Kereta 2">
                    <div class="carousel-caption" style="box-shadow: 8px 8px 0px var(--green);">
                        <h1>Urusan Dipermudah</h1>
                        <p>Sewa ikut jam atau harian mengikut bajet anda.</p>
                    </div>
                </div>
                <div class="carousel-item carousel-slide">
                    <img src="https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&fit=crop&w=1000&q=80" alt="Kereta 3">
                    <div class="carousel-caption" style="box-shadow: 8px 8px 0px var(--blue);">
                        <h1>Kekal Selamat</h1>
                        <p>Setiap kenderaan dan penyedia telah disahkan.</p>
                    </div>
                </div>
            </div>
            <button class="carousel-btn btn-prev" id="btn-prev"><i class="bi bi-chevron-left"></i></button>
            <button class="carousel-btn btn-next" id="btn-next"><i class="bi bi-chevron-right"></i></button>
        </div>

        <div class="section-container">
            
            <!-- STATISTIK -->
            <div class="section-title">Imbasan Sistem</div>
            <div class="neo-grid">
                <div class="neo-card bg-p">
                    <h2><?php echo $total_cars; ?></h2>
                    <p>Kereta</p>
                    <i class="bi bi-car-front-fill neo-icon"></i>
                </div>
                <div class="neo-card bg-b">
                    <h2><?php echo $total_users; ?></h2>
                    <p>Pelajar</p>
                    <i class="bi bi-people-fill neo-icon"></i>
                </div>
                <div class="neo-card bg-w">
                    <h2><?php echo $total_my_bookings; ?></h2>
                    <p>Tempahan</p>
                    <i class="bi bi-journal-check neo-icon"></i>
                </div>
            </div>

            <!-- MENU UTAMA PINTAS -->
            <div class="section-title" style="margin-top: 10px;">Akses Pintas</div>
            <div class="neo-grid">
                <div class="neo-card clickable-card bg-y" onclick="window.location.href='booking.php'">
                    <i class="bi bi-key-fill neo-icon"></i>
                    <h4>Tempah</h4>
                    <p class="desc">Cari kenderaan mengikut kesesuaian waktu anda.</p>
                </div>
                <div class="neo-card clickable-card bg-g" onclick="window.location.href='my_bookings.php'">
                    <i class="bi bi-clipboard-check-fill neo-icon"></i>
                    <h4>Status</h4>
                    <p class="desc">Semak kelulusan tempahan & hubungi penyedia.</p>
                </div>
                <div class="neo-card clickable-card bg-w" onclick="window.location.href='booking_history.php'">
                    <i class="bi bi-clock-history neo-icon"></i>
                    <h4>Sejarah</h4>
                    <p class="desc">Lihat rekod penggunaan dan tempahan lepas.</p>
                </div>
            </div>

        </div>
    </main>

    <!-- FOOTER -->
    <footer>
        &copy; <?php echo date("Y"); ?> SCRS PMU. SISTEM SEWAAN KERETA.
    </footer>

    <!-- JAVASCRIPT ASLI (VANILLA JS) -->
    <script>
        // 1. DROPDOWN PROFIL
        const profileToggle = document.getElementById('profile-toggle');
        const profileMenu = document.getElementById('profile-menu');
        
        profileToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            profileMenu.classList.toggle('show');
        });

        document.addEventListener('click', function(e) {
            if (!profileToggle.contains(e.target) && !profileMenu.contains(e.target)) {
                profileMenu.classList.remove('show');
            }
        });

        // 2. SIDEBAR OFFCANVAS
        const openSidebarBtn = document.getElementById('open-sidebar');
        const closeSidebarBtn = document.getElementById('close-sidebar');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');

        function openSidebar() {
            sidebar.classList.add('open');
            sidebarOverlay.classList.add('show');
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('show');
        }

        openSidebarBtn.addEventListener('click', openSidebar);
        closeSidebarBtn.addEventListener('click', closeSidebar);
        sidebarOverlay.addEventListener('click', closeSidebar);

        // 3. CAROUSEL
        const track = document.getElementById('carousel-track');
        const slides = Array.from(track.children);
        const btnNext = document.getElementById('btn-next');
        const btnPrev = document.getElementById('btn-prev');
        let currentIndex = 0;
        let slideInterval;

        function updateCarousel() {
            track.style.transform = `translateX(-${currentIndex * 100}%)`;
        }

        function nextSlide() {
            currentIndex = (currentIndex === slides.length - 1) ? 0 : currentIndex + 1;
            updateCarousel();
        }

        function prevSlide() {
            currentIndex = (currentIndex === 0) ? slides.length - 1 : currentIndex - 1;
            updateCarousel();
        }

        btnNext.addEventListener('click', () => { nextSlide(); resetInterval(); });
        btnPrev.addEventListener('click', () => { prevSlide(); resetInterval(); });

        function startInterval() {
            slideInterval = setInterval(nextSlide, 4000); // Tukar gambar setiap 4 saat
        }

        function resetInterval() {
            clearInterval(slideInterval);
            startInterval();
        }

        startInterval(); // Mulakan auto-slide
    </script>
</body>
</html>