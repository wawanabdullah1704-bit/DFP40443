<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/notifications.php';

// Semak jika pengguna telah log masuk dan merupakan seorang pelajar
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
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
$sql_latest_booking = "SELECT b.*, c.car_brand, c.car_model, c.car_plate, c.car_image, p.username AS provider_username, p.email AS provider_email, p.phone_no AS provider_phone, p.roadtax_file AS provider_roadtax, p.insurance_file AS provider_insurance 
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
    <!-- Master Neo-Brutalism CSS -->
    <link rel="stylesheet" href="../assets/css/neo-style.css">

    <style>
        /* --- CAROUSEL HERO --- */
        .carousel-container {
            width: 100%;
            height: 340px;
            position: relative;
            overflow: hidden;
            border-bottom: var(--border-thick);
            background-color: var(--black);
        }
        .carousel-track {
            display: flex;
            height: 100%;
            transition: transform 0.45s ease-in-out;
        }
        .carousel-slide {
            min-width: 100%;
            height: 100%;
            position: relative;
        }
        .carousel-slide img {
            width: 100%; 
            height: 100%; 
            object-fit: cover;
            filter: contrast(105%) brightness(0.65);
        }
        
        .carousel-caption-wrapper {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(0,0,0,0.1) 0%, rgba(0,0,0,0.85) 100%);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 24px 75px;
            text-align: left;
            pointer-events: none;
        }
        .carousel-badge {
            background: var(--yellow);
            color: var(--black);
            font-weight: 900;
            font-size: 0.8rem;
            text-transform: uppercase;
            padding: 3px 10px;
            border: var(--border-thin);
            border-radius: var(--radius-full);
            display: inline-block;
            margin-bottom: 8px;
            width: fit-content;
            box-shadow: var(--shadow-sm);
        }
        .carousel-caption-wrapper h1 {
            font-size: 1.8rem;
            font-weight: 900;
            text-transform: uppercase;
            color: var(--white);
            margin-bottom: 4px;
            text-shadow: 2px 2px 0px var(--black);
            line-height: 1.2;
        }
        .carousel-caption-wrapper p {
            font-weight: 700;
            font-size: 0.95rem;
            color: #f0f0f0;
            margin: 0;
            max-width: 600px;
        }

        .carousel-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-sm);
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            font-size: 1.25rem;
            box-shadow: var(--shadow-sm);
            z-index: 10;
            cursor: pointer;
            transition: var(--transition);
        }
        .carousel-btn:hover { background: var(--yellow); }
        .carousel-btn:active { transform: translateY(-50%) translate(2px, 2px); box-shadow: var(--shadow-active); }
        .btn-prev { left: 16px; }
        .btn-next { right: 16px; }

        /* --- SEKSYEN GRID --- */
        .section-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.75rem 20px;
            text-align: left;
        }
        
        .section-title {
            font-size: 1.15rem;
            font-weight: 900;
            text-transform: uppercase;
            color: var(--black);
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
            border-bottom: 3px solid var(--black);
            padding-bottom: 6px;
        }

        .neo-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        /* STAT WIDGETS (METRIK MAKLUMAT - BUKAN BUTTON) */
        .stat-widget {
            background-color: var(--white);
            border: 2px solid var(--black);
            border-radius: var(--radius-sm);
            padding: 14px 16px;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 14px;
            cursor: default;
            user-select: none;
            box-shadow: none !important;
            transition: none;
        }
        .stat-widget:hover {
            transform: none !important;
            box-shadow: none !important;
        }
        
        .stat-widget .stat-info { display: flex; flex-direction: column; min-width: 0; }
        .stat-widget h2 { font-size: 1.8rem; font-weight: 900; line-height: 1.1; margin-bottom: 2px; color: var(--black); }
        .stat-widget p { font-weight: 800; font-size: 0.72rem; text-transform: uppercase; color: #555; margin: 0; letter-spacing: 0.4px; }
        .stat-widget .stat-icon {
            width: 42px;
            height: 42px;
            min-width: 42px;
            border: 1.5px solid var(--black);
            border-radius: var(--radius-sm);
            box-shadow: none !important;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            color: var(--black);
        }
        .icon-box-g { background-color: var(--green); }
        .icon-box-y { background-color: var(--yellow); }
        .icon-box-b { background-color: var(--blue); }
        .icon-box-p { background-color: var(--pink); }

        /* ACTION CARDS */
        .action-card {
            position: relative;
            border: var(--border-thick);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-solid);
            padding: 16px 14px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            color: var(--black);
        }
        .action-card:hover { 
            transform: translate(-2px, -2px); 
            box-shadow: var(--shadow-lg); 
        }
        .action-card:active { 
            transform: translate(2px, 2px); 
            box-shadow: var(--shadow-active); 
        }
        .action-card h4 { font-size: 0.95rem; font-weight: 900; text-transform: uppercase; margin: 8px 0 0 0; }
        .action-card .action-icon { font-size: 2rem; }

        .action-badge {
            position: absolute;
            top: 6px;
            right: 6px;
            background: var(--pink);
            color: var(--black);
            font-size: 0.65rem;
            font-weight: 900;
            text-transform: uppercase;
            padding: 2px 6px;
            border: 1.5px solid var(--black);
            border-radius: var(--radius-sm);
            box-shadow: 1px 1px 0px var(--black);
            line-height: 1.2;
            white-space: nowrap;
            pointer-events: none;
        }

        .bg-y { background-color: var(--yellow); }
        .bg-g { background-color: var(--green); }
        .bg-b { background-color: var(--blue); }

        /* GUIDE STEPS */
        .guide-steps-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }
        .guide-step-card {
            background: var(--white);
            border: var(--border-thin);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            padding: 14px;
            text-align: left;
        }
        .step-num {
            background: var(--black);
            color: var(--white);
            font-weight: 900;
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: var(--radius-full);
            display: inline-block;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .booking-status-card {
            border-radius: var(--radius-lg);
            transition: var(--transition);
            text-align: left;
        }
        .booking-status-card:hover {
            transform: translate(-2px, -2px);
            box-shadow: var(--shadow-lg) !important;
        }

        @media (max-width: 768px) {
            .carousel-container { height: 215px; }
            .carousel-caption-wrapper { padding: 14px 52px; }
            .carousel-caption-wrapper h1 { font-size: 1.15rem; margin-bottom: 3px; }
            .carousel-caption-wrapper p { font-size: 0.8rem; line-height: 1.35; }
            .carousel-badge { font-size: 0.72rem; padding: 2px 8px; margin-bottom: 6px; }
            .carousel-btn { 
                width: 34px; 
                height: 34px; 
                font-size: 1rem; 
                padding: 0;
            }
            .btn-prev { left: 8px; }
            .btn-next { right: 8px; }

            .section-container { padding: 1.2rem 14px; }
            .neo-grid-3 {
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
                margin-bottom: 18px;
            }
            .stat-widget {
                padding: 10px 4px;
                flex-direction: column;
                text-align: center;
                align-items: center;
            }
            .stat-widget h2 { font-size: 1.4rem; }
            .stat-widget p { font-size: 0.65rem; }
            .stat-widget .stat-icon { font-size: 1.3rem; order: -1; margin-bottom: 2px; }

            .action-card {
                padding: 12px 4px;
                box-shadow: var(--shadow-sm);
            }
            .action-card:hover { transform: none; box-shadow: var(--shadow-sm); }
            .action-card:active { transform: translate(2px, 2px); box-shadow: var(--shadow-active); }
            .action-card .action-icon { font-size: 1.5rem; }
            .action-card h4 { font-size: 0.72rem; margin-top: 4px; }
            .action-badge {
                top: 4px;
                right: 4px;
                font-size: 0.58rem;
                padding: 1px 4px;
                border-width: 1px;
            }
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
            <a href="dashboard.php" class="neo-brand">SCRS PMU</a>
        </div>

        <?php render_navbar_actions($conn, 'student', $student_id, $student_name, '../'); ?>
    </header>

    <!-- SIDEBAR -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Menu Utama</h2>
            <button class="close-btn" id="close-sidebar"><i class="bi bi-x-lg"></i></button>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="sidebar-link active"><i class="bi bi-speedometer2"></i> Papan Pemuka</a>
            <a href="booking.php" class="sidebar-link"><i class="bi bi-car-front-fill"></i> Cari & Tempah</a>
            <a href="my_bookings.php" class="sidebar-link"><i class="bi bi-calendar-check-fill"></i> Tempahan Saya</a>
            <a href="booking_history.php" class="sidebar-link"><i class="bi bi-clock-history"></i> Rekod Tempahan</a>
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
                <h1 style="font-size: 1.4rem; font-weight: 900; text-transform: uppercase; margin-bottom: 4px; color: var(--black);">
                    Papan Pemuka Pelajar
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
                </div>
                <div class="action-card bg-w" onclick="window.location.href='booking_history.php'">
                    <i class="bi bi-clock-history action-icon"></i>
                    <h4>Rekod Tempahan</h4>
                </div>
            </div>

            <!-- BUTANG PANDUAN RINGKAS MENYEWA (BUKA MODAL AGAR TIDAK MEMENUHI DASHBOARD) -->
            <div style="margin-bottom: 25px;">
                <button type="button" class="neo-btn" onclick="openGuideModal()" style="background: var(--white); width: 100%; justify-content: space-between; padding: 12px 16px; border: var(--border-thick); box-shadow: var(--shadow-solid); color: var(--black);">
                    <span style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem;">
                        <i class="bi bi-info-circle-fill text-primary"></i>
                        <span>Cara Menyewa</span>
                    </span>
                    <span style="font-size: 0.75rem; background: var(--yellow); border: 2px solid var(--black); padding: 3px 10px; border-radius: var(--radius-full); display: inline-flex; align-items: center; gap: 2px; box-shadow: 2px 2px 0px var(--black);">
                        Panduan <i class="bi bi-arrow-right-short"></i>
                    </span>
                </button>
            </div>

            <?php if ($latest_booking): ?>
                <!-- LIVE TRACKING KAD TEMPAHAN TERKINI -->
                <div class="section-title"><i class="bi bi-geo-alt-fill me-1"></i> Perjalanan Anda</div>
                <div class="booking-status-card" onclick="window.location.href='my_bookings.php'" style="background-color: var(--white); border: var(--border-thick); border-radius: var(--radius-lg); box-shadow: var(--shadow-solid); padding: 20px; cursor: pointer; transition: var(--transition); margin-bottom: 30px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; font-weight: 800; font-size: 0.95rem; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                        <span><i class="bi bi-car-front-fill me-1 text-primary"></i> <strong><?php echo (!empty($latest_booking['car_brand']) ? htmlspecialchars($latest_booking['car_brand']) . ' ' : '') . htmlspecialchars($latest_booking['car_model']); ?> (<?php echo htmlspecialchars($latest_booking['car_plate']); ?>)</strong></span>
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
                <div class="section-title"><i class="bi bi-geo-alt-fill me-1"></i> Perjalanan Anda</div>
                <div class="booking-status-card" style="background-color: var(--white); border: var(--border-thick); border-radius: var(--radius-lg); box-shadow: var(--shadow-solid); padding: 20px; margin-bottom: 30px;">
                    <div style="font-weight: 900; font-size: 1.05rem; text-transform: uppercase; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-geo-alt-fill text-primary"></i> <strong>Perjalanan Anda</strong>
                    </div>
                    <div class="progress-bar-container" style="width: 100%; height: 14px; background-color: var(--bg-color); border: 3px solid var(--black); overflow: hidden; margin-bottom: 10px;">
                        <div class="progress-bar-fill" style="width: 0%; height: 100%; background-color: var(--gray);"></div>
                    </div>
                    <div style="font-size: 0.9rem; font-weight: 700; color: #666; text-transform: none;">
                        belum ada perjalanan
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <!-- MODAL PANDUAN MENYEWA -->
    <div class="neo-modal-overlay" id="guideModalOverlay" onclick="closeGuideModalOutside(event)">
        <div class="neo-modal" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="modal-title"><i class="bi bi-info-circle-fill text-primary me-2"></i>Panduan Menyewa Kenderaan</h3>
            </div>
            <div class="modal-body">
                <p style="font-weight: 700; color: #555; font-size: 0.9rem; margin-bottom: 15px;">Ikuti 3 langkah mudah berikut untuk menyewa kenderaan di SCRS PMU:</p>
                <div class="guide-steps-grid">
                    <div class="guide-step-card">
                        <span class="step-num">Langkah 1</span>
                        <h5 style="font-weight: 900; text-transform: uppercase; font-size: 0.95rem; margin-bottom: 5px;"><i class="bi bi-search text-primary me-1"></i> Tetapkan Tarikh & Pilih Kereta</h5>
                        <p style="font-size: 0.85rem; font-weight: 600; color: #444; margin: 0; line-height: 1.4;">Pilih mod sewaan (Jam atau Harian) serta tarikh & masa di menu <strong>Cari & Tempah</strong>, kemudian pilih kereta yang tersedia dan hantar permohonan tempahan.</p>
                    </div>
                    <div class="guide-step-card">
                        <span class="step-num">Langkah 2</span>
                        <h5 style="font-weight: 900; text-transform: uppercase; font-size: 0.95rem; margin-bottom: 5px;"><i class="bi bi-qr-code text-success me-1"></i> Kelulusan & Bayaran QR</h5>
                        <p style="font-size: 0.85rem; font-weight: 600; color: #444; margin: 0; line-height: 1.4;">Pantau tempahan di menu <strong>Status Tempahan</strong>. Apabila penyedia meluluskan tempahan, imbas Kod QR DuitNow penyedia dan muat naik resit bayaran.</p>
                    </div>
                    <div class="guide-step-card">
                        <span class="step-num">Langkah 3</span>
                        <h5 style="font-weight: 900; text-transform: uppercase; font-size: 0.95rem; margin-bottom: 5px;"><i class="bi bi-car-front-fill text-danger me-1"></i> Ambil Kunci & Pemulangan</h5>
                        <p style="font-size: 0.85rem; font-weight: 600; color: #444; margin: 0; line-height: 1.4;">Hubungi penyedia melalui WhatsApp untuk serahan kunci. Selepas selesai tempoh sewaan, muat naik gambar kereta dan tekan <strong>Sahkan Kereta Telah Dikembalikan</strong>.</p>
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

        // 5. CEGAH PAPARAN SEMULA MELALUI BUTANG BACK SELEPAS LOG KELUAR
        window.addEventListener('pageshow', function(event) {
            if (event.persisted || (window.performance && window.performance.navigation && window.performance.navigation.type === 2)) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>