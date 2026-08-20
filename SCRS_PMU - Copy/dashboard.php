<?php
session_start();
require 'db.php';

// Semak jika pengguna telah log masuk dan merupakan seorang pelajar
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['username'];

// --- KIRAAN STATISTIK KHUSUS PELAJAR ---
// 1. Kereta Tersedia
$sql_cars = "SELECT COUNT(*) as total FROM cars WHERE status = 'Available'";
$res_cars = $conn->query($sql_cars);
$total_cars = $res_cars->fetch_assoc()['total'] ?? 0;

// 2. Tempahan Aktif / Semasa Pelajar (Pending & Approved)
$sql_active = "SELECT COUNT(*) as total FROM bookings WHERE student_id = ? AND status IN ('Pending', 'Approved')";
$stmt_act = $conn->prepare($sql_active);
$stmt_act->bind_param("i", $student_id);
$stmt_act->execute();
$total_active_bookings = $stmt_act->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_act->close();

// 3. Jumlah Tempahan Selesai
$sql_comp = "SELECT COUNT(*) as total FROM bookings WHERE student_id = ? AND status = 'Completed'";
$stmt_cp = $conn->prepare($sql_comp);
$stmt_cp->bind_param("i", $student_id);
$stmt_cp->execute();
$total_completed = $stmt_cp->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_cp->close();

// 4. Jumlah Keseluruhan Rekod Tempahan
$sql_my_books = "SELECT COUNT(*) as total FROM bookings WHERE student_id = ?";
$stmt_my = $conn->prepare($sql_my_books);
$stmt_my->bind_param("i", $student_id);
$stmt_my->execute();
$total_my_bookings = $stmt_my->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_my->close();

// --- DAPATKAN TEMPAHAN TERBAHARU PELAJAR & MAKLUMAT PENYEDIA ---
$sql_latest_booking = "SELECT b.*, c.car_model, c.car_plate, c.car_image, p.username AS provider_username, p.email AS provider_email, p.phone_no AS provider_phone, p.roadtax_file AS provider_roadtax, p.insurance_file AS provider_insurance 
                       FROM bookings b 
                       JOIN cars c ON b.car_id = c.id
                       JOIN providers p ON c.provider_id = p.id
                       WHERE b.student_id = ? 
                       ORDER BY b.created_at DESC LIMIT 1";
$stmt_latest = $conn->prepare($sql_latest_booking);
$stmt_latest->bind_param("i", $student_id);
$stmt_latest->execute();
$res_latest = $stmt_latest->get_result();
$latest_booking = $res_latest->fetch_assoc();
$stmt_latest->close();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Papan Pemuka Pelajar - SCRS PMU</title>
    
    <!-- Ikon Bootstrap -->
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

        /* --- CAROUSEL (SLEEK HERO BANNER) --- */
        .carousel-container {
            width: 100%;
            height: 360px;
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
            filter: contrast(105%) brightness(0.65);
        }
        
        /* Modern Hero Caption Overlay (Left-Aligned & Mobile Friendly) */
        .carousel-caption-wrapper {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(0,0,0,0.15) 0%, rgba(0,0,0,0.85) 100%);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 30px 40px;
            text-align: left;
        }
        .carousel-badge {
            background: var(--yellow);
            color: var(--black);
            font-weight: 900;
            font-size: 0.8rem;
            text-transform: uppercase;
            padding: 4px 10px;
            border: 2px solid var(--black);
            display: inline-block;
            margin-bottom: 8px;
            width: fit-content;
            box-shadow: 3px 3px 0px var(--black);
        }
        .carousel-caption-wrapper h1 {
            font-size: 2rem;
            font-weight: 900;
            text-transform: uppercase;
            color: var(--white);
            margin-bottom: 6px;
            text-shadow: 2px 2px 0px var(--black);
            line-height: 1.2;
        }
        .carousel-caption-wrapper p {
            font-weight: 700;
            font-size: 1rem;
            color: #f0f0f0;
            margin: 0;
            max-width: 600px;
        }

        .carousel-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: var(--white);
            border: 3px solid var(--black);
            padding: 8px 14px;
            font-size: 1.3rem;
            box-shadow: 3px 3px 0px var(--black);
            z-index: 10;
            cursor: pointer;
            transition: var(--transition);
        }
        .carousel-btn:hover { background: var(--yellow); }
        .carousel-btn:active { transform: translateY(-50%) translate(2px, 2px); box-shadow: 0px 0px 0px var(--black); }
        .btn-prev { left: 15px; }
        .btn-next { right: 15px; }

        /* --- SEKSYEN GRID (STATISTIK & MENU) --- */
        .section-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 20px;
            text-align: left;
        }
        
        /* --- TYPOGRAPHY & SECTION HEADINGS --- */
        .section-title {
            font-size: 1.2rem;
            font-weight: 900;
            text-transform: uppercase;
            color: var(--black);
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
            border-bottom: 3px solid var(--black);
            padding-bottom: 6px;
            text-align: left;
        }

        .neo-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        /* STATISTIK IMBASAN (PAPAN METRIK MAKLUMAT - BUKAN BUTTON) */
        .stat-widget {
            background-color: var(--white);
            border: 2px solid #ccc;
            border-top: 4px solid var(--black);
            box-shadow: none !important;
            padding: 16px 18px;
            text-align: left;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            cursor: default;
            user-select: none;
            border-radius: 4px;
        }
        .stat-widget.border-g { border-top: 4px solid #00c853; }
        .stat-widget.border-y { border-top: 4px solid #ffb300; }
        .stat-widget.border-b { border-top: 4px solid #00b0ff; }
        .stat-widget.border-p { border-top: 4px solid #e91e63; }
        
        .stat-widget .stat-info { display: flex; flex-direction: column; }
        .stat-widget h2 { font-size: 2.2rem; font-weight: 900; line-height: 1; margin-bottom: 4px; color: var(--black); }
        .stat-widget p { font-weight: 800; font-size: 0.8rem; text-transform: uppercase; color: #666; margin: 0; }
        .stat-widget .stat-icon { font-size: 2.2rem; }
        .icon-g { color: #00c853; }
        .icon-y { color: #ffb300; }
        .icon-b { color: #00b0ff; }
        .icon-p { color: #e91e63; }

        /* KAD MENU TINDAKAN (AKSES PINTAS - BOLEH DITEKAN) */
        .action-card {
            border: var(--border-thick);
            box-shadow: var(--shadow-solid);
            padding: 16px 14px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
        }
        .action-card:hover { 
            transform: translate(-3px, -3px); 
            box-shadow: 8px 8px 0px var(--black); 
        }
        .action-card:active { 
            transform: translate(3px, 3px); 
            box-shadow: 0px 0px 0px var(--black); 
        }
        .action-card h4 { font-size: 1rem; font-weight: 900; text-transform: uppercase; margin: 8px 0 0 0; line-height: 1.2; }
        .action-card .action-icon { font-size: 2.2rem; }

        /* Warna Latar */
        .bg-y { background-color: var(--yellow); }
        .bg-g { background-color: var(--green); }
        .bg-b { background-color: var(--blue); }
        .bg-p { background-color: var(--pink); }
        .bg-w { background-color: var(--white); }

        .neo-btn {
            background-color: var(--yellow);
            border: 3px solid var(--black);
            box-shadow: 4px 4px 0px var(--black);
            font-weight: 900;
            text-transform: uppercase;
            padding: 10px 18px;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
            text-decoration: none;
            color: var(--black);
        }
        .neo-btn:hover { transform: translate(-2px, -2px); box-shadow: 6px 6px 0px var(--black); }
        .neo-btn:active { transform: translate(2px, 2px); box-shadow: var(--shadow-active); }
        .btn-green { background-color: var(--green); }
        .btn-blue { background-color: var(--blue); }
        .btn-pink { background-color: var(--pink); }

        /* Guide Step Cards */
        .guide-steps-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }
        .guide-step-card {
            background: var(--white);
            border: 3px solid var(--black);
            box-shadow: 4px 4px 0px var(--black);
            padding: 16px;
            text-align: left;
        }
        .step-num {
            background: var(--black);
            color: var(--white);
            font-weight: 900;
            font-size: 0.75rem;
            padding: 2px 8px;
            display: inline-block;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        /* Modal Panduan */
        .neo-modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6); z-index: 2000; display: none; align-items: center; justify-content: center; padding: 15px;
        }
        .neo-modal-overlay.show { display: flex; }
        .neo-modal {
            background: var(--white); border: var(--border-thick); box-shadow: 10px 10px 0px var(--black);
            width: 100%; max-width: 520px; max-height: 90vh; overflow-y: auto; padding: 25px; position: relative;
            text-align: left;
        }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid var(--black); padding-bottom: 12px; margin-bottom: 15px; }
        .modal-title { font-weight: 900; text-transform: uppercase; font-size: 1.2rem; margin: 0; }

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

        /* --- RESPONSIVE MOBILE (TIADA SCROLL, COMPACT HORIZONTAL 3-COLUMNS) --- */
        @media (max-width: 768px) {
            .neo-brand { font-size: 1.2rem; }
            .profile-btn { padding: 6px 10px; font-size: 0.85rem; }
            
            .carousel-container { height: 210px; }
            .carousel-caption-wrapper { padding: 12px 16px; }
            .carousel-caption-wrapper h1 { font-size: 1.1rem; margin-bottom: 3px; }
            .carousel-caption-wrapper p { font-size: 0.8rem; }
            .carousel-btn { padding: 3px 6px; font-size: 1rem; }

            .section-container { padding: 1rem 10px; }
            
            /* SUSUNAN 3 RUANGAN MENDATAR (TIADA SCROLL) */
            .neo-grid-3 {
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
                margin-bottom: 18px;
            }
            
            .stat-widget {
                padding: 10px 4px;
                box-shadow: none !important;
                flex-direction: column;
                text-align: center;
                align-items: center;
                gap: 2px;
                border-radius: 4px;
                background: #ffffff;
            }
            .stat-widget h2 { font-size: 1.5rem; margin-bottom: 2px; }
            .stat-widget p { font-size: 0.65rem; font-weight: 700; color: #666; }
            .stat-widget .stat-icon { font-size: 1.3rem; order: -1; margin-bottom: 2px; }

            /* KAD AKSES PINTAS COMPACT PADA MOBILE (3-COLUMNS) */
            .action-card {
                padding: 12px 4px;
                box-shadow: 3px 3px 0px var(--black);
            }
            .action-card:hover { transform: none; box-shadow: 3px 3px 0px var(--black); }
            .action-card:active { transform: translate(2px, 2px); box-shadow: 1px 1px 0px var(--black); }
            .action-card .action-icon { font-size: 1.6rem; }
            .action-card h4 { font-size: 0.72rem; margin-top: 4px; }
        }

        /* --- BOOKING STATUS CARD --- */
        .booking-status-card {
            transition: var(--transition);
            text-align: left;
        }
        .booking-status-card:hover {
            transform: translate(-3px, -3px);
            box-shadow: 8px 8px 0px var(--black) !important;
        }
        .status-badge {
            border: 2px solid var(--black);
            padding: 3px 8px;
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 900;
            border-radius: 3px;
        }
        .status-badge.pending { background-color: var(--yellow); }
        .status-badge.approved { background-color: var(--blue); }
        .status-badge.completed { background-color: var(--green); }
        .status-badge.rejected { background-color: var(--pink); }

    </style>
</head>
<body>

    <!-- NAVBAR -->
    <header class="neo-navbar">
        <div class="neo-nav-left">
            <button class="menu-toggle-btn" id="open-sidebar">
                <i class="bi bi-list"></i>
            </button>
            <a href="dashboard.php" class="neo-brand">SCRS PMU</a>
        </div>

        <div class="profile-container">
            <button class="profile-btn" id="profile-toggle">
                <i class="bi bi-person-fill fs-5"></i>
                <span><?php echo htmlspecialchars($student_name); ?></span>
            </button>
            <ul class="dropdown-menu" id="profile-menu">
                <li><a href="edit_profile.php" class="dropdown-item"><i class="bi bi-gear-fill me-2"></i> Edit Profil</a></li>
                <li><a href="logout.php" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i> Log Keluar</a></li>
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
        
        <!-- CAROUSEL (MODERN SLEEK HERO) -->
        <div class="carousel-container">
            <div class="carousel-track" id="carousel-track">
                <div class="carousel-slide">
                    <img src="https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&w=1000&q=80" alt="Kereta 1">
                    <div class="carousel-caption-wrapper">
                        <span class="carousel-badge">Sistem Sewaan PMU</span>
                        <h1>Sewa Kereta Mudah & Pantas</h1>
                        <p>Khas untuk kemudahan pelajar Politeknik Mukah bergerak.</p>
                    </div>
                </div>
                <div class="carousel-item carousel-slide">
                    <img src="https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?auto=format&fit=crop&w=1000&q=80" alt="Kereta 2">
                    <div class="carousel-caption-wrapper">
                        <span class="carousel-badge" style="background: var(--green);">Kadar Berpatutan</span>
                        <h1>Pilihan Harian & Mengikut Jam</h1>
                        <p>Kadar sewaan fleksibel yang mesra bajet pelajar.</p>
                    </div>
                </div>
                <div class="carousel-item carousel-slide">
                    <img src="https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&fit=crop&w=1000&q=80" alt="Kereta 3">
                    <div class="carousel-caption-wrapper">
                        <span class="carousel-badge" style="background: var(--blue);">Terjamin Selamat</span>
                        <h1>Penyedia & Kenderaan Sah</h1>
                        <p>Semua penyedia dan kenderaan berdaftar telah disahkan.</p>
                    </div>
                </div>
            </div>
            <button class="carousel-btn btn-prev" id="btn-prev"><i class="bi bi-chevron-left"></i></button>
            <button class="carousel-btn btn-next" id="btn-next"><i class="bi bi-chevron-right"></i></button>
        </div>

        <div class="section-container">
            
            <!-- HEADING PANDUAN PENGGUNA (TANPA KOTAK) -->
            <div style="margin-bottom: 20px;">
                <h1 style="font-size: 1.4rem; font-weight: 900; text-transform: uppercase; margin-bottom: 4px; color: var(--black); display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-speedometer2 text-dark"></i> Papan Pemuka Pelajar
                </h1>
                <p style="font-weight: 700; color: #555; font-size: 0.9rem; margin: 0; line-height: 1.4;">
                    Selamat Datang, <strong><?php echo htmlspecialchars($student_name); ?></strong>!
                </p>
            </div>

            <!-- MENU UTAMA PINTAS (3 RUANGAN MENDATAR PADA MOBILE - TIADA SCROLL) -->
            <div class="section-title"><i class="bi bi-grid-fill me-1"></i> Akses Pintas</div>
            <div class="neo-grid-3" style="margin-bottom: 20px;">
                <div class="action-card bg-y" onclick="window.location.href='booking.php'">
                    <i class="bi bi-key-fill action-icon"></i>
                    <h4>Cari & Tempah</h4>
                </div>
                <div class="action-card bg-g" onclick="window.location.href='my_bookings.php'">
                    <i class="bi bi-clipboard-check-fill action-icon"></i>
                    <h4>Status Tempahan</h4>
                    <?php if ($total_active_bookings > 0): ?>
                        <div style="margin-top: 4px; font-weight: 900; font-size: 0.65rem; background: var(--pink); color: #000; border: 1.5px solid #000; padding: 1px 4px; text-transform: uppercase;">
                            <?php echo $total_active_bookings; ?> Aktif
                        </div>
                    <?php endif; ?>
                </div>
                <div class="action-card bg-w" onclick="window.location.href='booking_history.php'">
                    <i class="bi bi-clock-history action-icon"></i>
                    <h4>Sejarah Rekod</h4>
                </div>
            </div>

            <!-- BUTANG PANDUAN RINGKAS MENYEWA (BUKA MODAL AGAR TIDAK MEMENUHI DASHBOARD) -->
            <div style="margin-bottom: 25px;">
                <button type="button" class="neo-btn" onclick="openGuideModal()" style="background: var(--white); width: 100%; justify-content: space-between; padding: 12px 16px; border: var(--border-thick); box-shadow: var(--shadow-solid); color: var(--black);">
                    <span style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem;">
                        <i class="bi bi-info-circle-fill text-primary"></i>
                        <span>Cara Menyewa (3 Langkah)</span>
                    </span>
                    <span style="font-size: 0.75rem; background: var(--yellow); border: 2px solid var(--black); padding: 3px 8px; border-radius: 3px; display: inline-flex; align-items: center; gap: 2px; box-shadow: 2px 2px 0px var(--black);">
                        Panduan <i class="bi bi-arrow-right-short"></i>
                    </span>
                </button>
            </div>

            <?php if ($latest_booking): ?>
                <!-- LIVE TRACKING KAD TEMPAHAN TERKINI -->
                <div class="section-title"><i class="bi bi-activity me-1"></i> Status Tempahan Terkini Anda</div>
                <div class="booking-status-card" onclick="window.location.href='my_bookings.php'" style="background-color: var(--white); border: var(--border-thick); box-shadow: var(--shadow-solid); padding: 20px; cursor: pointer; transition: var(--transition); margin-bottom: 30px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; font-weight: 800; font-size: 0.95rem; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                        <span><i class="bi bi-car-front-fill me-1 text-primary"></i> <strong><?php echo htmlspecialchars($latest_booking['car_model']); ?> (<?php echo htmlspecialchars($latest_booking['car_plate']); ?>)</strong></span>
                        <span class="status-badge <?php echo strtolower($latest_booking['status']); ?>">
                            <?php echo htmlspecialchars($latest_booking['status']); ?>
                        </span>
                    </div>
                    
                    <?php
                    $progress = 0;
                    $bar_color = "var(--yellow)";
                    if ($latest_booking['status'] === 'Pending') {
                        $progress = 33;
                        $bar_color = "var(--yellow)";
                    } elseif ($latest_booking['status'] === 'Approved') {
                        $progress = 66;
                        $bar_color = "var(--blue)";
                    } elseif ($latest_booking['status'] === 'Completed') {
                        $progress = 100;
                        $bar_color = "var(--green)";
                    } elseif ($latest_booking['status'] === 'Rejected') {
                        $progress = 100;
                        $bar_color = "var(--pink)";
                    }
                    ?>
                    <div class="progress-bar-container" style="width: 100%; height: 14px; background-color: var(--bg-color); border: 3px solid var(--black); overflow: hidden; margin-bottom: 10px;">
                        <div class="progress-bar-fill" style="width: <?php echo $progress; ?>%; height: 100%; background-color: <?php echo $bar_color; ?>; transition: width 0.3s ease-in-out;"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 800; color: #555; text-transform: uppercase; flex-wrap: wrap; gap: 5px;">
                        <span>Tempoh: <?php echo date('d M, h:i A', strtotime($latest_booking['start_date'])); ?> ➔ <?php echo date('d M, h:i A', strtotime($latest_booking['end_date'])); ?></span>
                        <span style="color: #0055ff;">Tekan untuk buka Status Tempahan <i class="bi bi-arrow-right-circle-fill ms-1"></i></span>
                    </div>
                </div>
            <?php else: ?>
                <!-- JIKA BELUM ADA TEMPAHAN -->
                <div style="background: var(--white); border: var(--border-thick); box-shadow: var(--shadow-solid); padding: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 30px; text-align: left;">
                    <div>
                        <h4 style="font-weight: 900; text-transform: uppercase; margin-bottom: 4px;"><i class="bi bi-car-front-fill text-primary me-1"></i> Sedia Untuk Memulakan Perjalanan?</h4>
                        <p style="font-weight: 700; color: #555; font-size: 0.9rem; margin: 0;">Cari kenderaan yang sesuai mengikut bajet anda sekarang!</p>
                    </div>
                    <a href="booking.php" class="neo-btn btn-green">
                        <i class="bi bi-key-fill me-1"></i> Tempah Kereta Sekarang
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <!-- MODAL PANDUAN MENYEWA -->
    <div class="neo-modal-overlay" id="guideModalOverlay" onclick="closeGuideModalOutside(event)">
        <div class="neo-modal" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="modal-title"><i class="bi bi-info-circle-fill text-primary me-2"></i>Panduan Mudah Menyewa</h3>
            </div>
            <div class="modal-body">
                <p style="font-weight: 700; color: #555; font-size: 0.9rem; margin-bottom: 15px;">Ikuti 3 langkah mudah berikut untuk menyewa kenderaan di SCRS PMU:</p>
                <div class="guide-steps-grid">
                    <div class="guide-step-card">
                        <span class="step-num">Langkah 1</span>
                        <h5 style="font-weight: 900; text-transform: uppercase; font-size: 0.95rem; margin-bottom: 5px;"><i class="bi bi-search text-primary me-1"></i> Cari & Pilih Kereta</h5>
                        <p style="font-size: 0.85rem; font-weight: 600; color: #444; margin: 0; line-height: 1.4;">Pilih tarikh, masa sewaan mengikut jam atau harian dan hantar permohonan kepada penyedia kenderaan.</p>
                    </div>
                    <div class="guide-step-card">
                        <span class="step-num">Langkah 2</span>
                        <h5 style="font-weight: 900; text-transform: uppercase; font-size: 0.95rem; margin-bottom: 5px;"><i class="bi bi-qr-code text-success me-1"></i> Kelulusan & Bayaran QR</h5>
                        <p style="font-size: 0.85rem; font-weight: 600; color: #444; margin: 0; line-height: 1.4;">Setelah penyedia meluluskan tempahan, imbas Kod QR DuitNow penyedia dan muat naik bukti resit transaksi.</p>
                    </div>
                    <div class="guide-step-card">
                        <span class="step-num">Langkah 3</span>
                        <h5 style="font-weight: 900; text-transform: uppercase; font-size: 0.95rem; margin-bottom: 5px;"><i class="bi bi-car-front-fill text-danger me-1"></i> Ambil Kunci & Pulangkan</h5>
                        <p style="font-size: 0.85rem; font-weight: 600; color: #444; margin: 0; line-height: 1.4;">Hubungi penyedia melalui WhatsApp untuk serahan kunci dan muat naik gambar kenderaan selepas selesai dipulangkan.</p>
                    </div>
                </div>
                <div style="margin-top: 20px; text-align: right;">
                    <button type="button" class="neo-btn btn-green" onclick="closeGuideModal()" style="width: 100%;">
                        Faham & Tutup Panduan
                    </button>
                </div>
            </div>
        </div>
    </div>

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

        // 4. MODAL PANDUAN
        function openGuideModal() {
            document.getElementById('guideModalOverlay').classList.add('show');
        }

        function closeGuideModal() {
            document.getElementById('guideModalOverlay').classList.remove('show');
        }

        function closeGuideModalOutside(e) {
            if (e.target.id === 'guideModalOverlay') {
                closeGuideModal();
            }
        }
    </script>
</body>
</html>