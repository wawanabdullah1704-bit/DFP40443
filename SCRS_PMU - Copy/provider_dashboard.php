<?php
session_start();
require 'db.php';

// Semak jika pengguna telah log masuk dan merupakan Penyedia Kereta (Provider)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'provider') {
    header("Location: index.php");
    exit();
}

$provider_id = $_SESSION['provider_id'];
$provider_name = $_SESSION['username'];
$message = "";

// --- 1. PROSES MUAT NAIK QR CODE PEMBAYARAN CEPAT ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_qr'])) {
    if (!empty($_FILES["qr_image"]["name"])) {
        $targetDir = "uploads/qr_codes/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $qrName = basename($_FILES["qr_image"]["name"]);
        $newQrName = "QR_" . $provider_id . "_" . time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $qrName);
        $targetPath = $targetDir . $newQrName;

        $sql_old_qr = "SELECT qr_code_image FROM providers WHERE id = ?";
        $stmt_old_qr = $conn->prepare($sql_old_qr);
        $stmt_old_qr->bind_param("i", $provider_id);
        $stmt_old_qr->execute();
        $res_old_qr = $stmt_old_qr->get_result();
        if ($old_qr_row = $res_old_qr->fetch_assoc()) {
            if (!empty($old_qr_row['qr_code_image']) && file_exists($old_qr_row['qr_code_image'])) {
                unlink($old_qr_row['qr_code_image']);
            }
        }
        $stmt_old_qr->close();

        if (move_uploaded_file($_FILES["qr_image"]["tmp_name"], $targetPath)) {
            $sql = "UPDATE providers SET qr_code_image = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $targetPath, $provider_id);
            if ($stmt->execute()) {
                $message = "<div class='neo-alert alert-success'><i class='bi bi-check-circle-fill me-2'></i>Berjaya: Kod QR Pembayaran DuitNow anda telah dikemaskini!</div>";
            }
            $stmt->close();
        } else {
            $message = "<div class='neo-alert alert-danger'>Ralat: Gagal memuat naik Kod QR.</div>";
        }
    }
}

// --- 2. KIRAAN STATISTIK PAPAN PEMUKA PENYEDIA ---
// Total Cars
$sql_cars = "SELECT COUNT(*) as total FROM cars WHERE provider_id = ?";
$stmt_c = $conn->prepare($sql_cars);
$stmt_c->bind_param("i", $provider_id);
$stmt_c->execute();
$total_cars = $stmt_c->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_c->close();

// Total Pending Bookings
$sql_pending = "SELECT COUNT(*) as total FROM bookings b JOIN cars c ON b.car_id = c.id WHERE c.provider_id = ? AND b.status = 'Pending'";
$stmt_p = $conn->prepare($sql_pending);
$stmt_p->bind_param("i", $provider_id);
$stmt_p->execute();
$total_pending = $stmt_p->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_p->close();

// Total Active (Approved) Bookings
$sql_active = "SELECT COUNT(*) as total FROM bookings b JOIN cars c ON b.car_id = c.id WHERE c.provider_id = ? AND b.status = 'Approved'";
$stmt_a = $conn->prepare($sql_active);
$stmt_a->bind_param("i", $provider_id);
$stmt_a->execute();
$total_active = $stmt_a->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_a->close();

// Total Completed Bookings & Earnings
$sql_completed = "SELECT COUNT(*) as total, SUM(b.total_price) as earnings FROM bookings b JOIN cars c ON b.car_id = c.id WHERE c.provider_id = ? AND b.status = 'Completed'";
$stmt_comp = $conn->prepare($sql_completed);
$stmt_comp->bind_param("i", $provider_id);
$stmt_comp->execute();
$comp_data = $stmt_comp->get_result()->fetch_assoc();
$total_completed = $comp_data['total'] ?? 0;
$total_earnings = $comp_data['earnings'] ?? 0;
$stmt_comp->close();

// Check Provider QR Code Status
$sql_prov = "SELECT qr_code_image FROM providers WHERE id = ?";
$stmt_prov = $conn->prepare($sql_prov);
$stmt_prov->bind_param("i", $provider_id);
$stmt_prov->execute();
$provider_info = $stmt_prov->get_result()->fetch_assoc();
$has_qr = (!empty($provider_info['qr_code_image']) && file_exists($provider_info['qr_code_image']));
$stmt_prov->close();

// Senarai Permohonan Tempahan untuk Kenderaan Provider (Pending didahulukan)
$sql_applications = "SELECT b.*, c.car_model, c.car_plate, c.car_brand, c.car_image, 
                            s.full_name as student_name, s.phone_no as student_phone, s.no_pendaftaran as student_matrix
                     FROM bookings b 
                     JOIN cars c ON b.car_id = c.id 
                     JOIN students s ON b.student_id = s.id 
                     WHERE c.provider_id = ? 
                     ORDER BY 
                        CASE 
                            WHEN b.status = 'Pending' THEN 1 
                            WHEN b.status = 'Approved' THEN 2 
                            ELSE 3 
                        END, 
                        b.created_at DESC 
                     LIMIT 6";
$stmt_app = $conn->prepare($sql_applications);
$stmt_app->bind_param("i", $provider_id);
$stmt_app->execute();
$result_applications = $stmt_app->get_result();
$stmt_app->close();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Papan Pemuka Penyedia - SCRS PMU</title>
    
    <!-- Ikon Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Master Neo-Brutalism CSS -->
    <link rel="stylesheet" href="neo-style.css">

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
            padding: 24px 30px;
            text-align: left;
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
            background-color: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-sm);
            padding: 6px 12px;
            font-size: 1.2rem;
            font-weight: 900;
            cursor: pointer;
            transition: var(--transition);
            z-index: 10;
        }
        .carousel-btn:hover { background-color: var(--yellow); }
        .carousel-btn:active { transform: translateY(-50%) translate(2px, 2px); box-shadow: var(--shadow-active); }
        .btn-prev { left: 15px; }
        .btn-next { right: 15px; }

        /* --- SEKSYEN KANDUNGAN --- */
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

        .neo-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .neo-grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }

        /* STAT WIDGETS */
        .stat-widget {
            background-color: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-lg);
            padding: 16px 14px;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 14px;
            cursor: default;
            user-select: none;
            box-shadow: var(--shadow-solid);
            transition: var(--transition);
        }
        .stat-widget:hover {
            transform: translate(-2px, -2px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-widget .stat-info { display: flex; flex-direction: column; }
        .stat-widget h2 { font-size: 1.6rem; font-weight: 900; line-height: 1.1; margin-bottom: 2px; color: var(--black); }
        .stat-widget p { font-weight: 800; font-size: 0.75rem; text-transform: uppercase; color: #666; margin: 0; }
        .stat-widget .stat-icon {
            width: 44px;
            height: 44px;
            min-width: 44px;
            border: var(--border-thin);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            color: var(--black);
        }
        .icon-box-b { background-color: var(--blue); }
        .icon-box-y { background-color: var(--yellow); }
        .icon-box-g { background-color: var(--green); }
        .icon-box-p { background-color: var(--pink); }

        /* ACTION CARDS */
        .action-card {
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

        .bg-y { background-color: var(--yellow); }
        .bg-g { background-color: var(--green); }
        .bg-b { background-color: var(--blue); }

        @media (max-width: 768px) {
            .carousel-container { height: 210px; }
            .carousel-caption-wrapper { padding: 14px 16px; }
            .carousel-caption-wrapper h1 { font-size: 1.15rem; margin-bottom: 2px; }
            .carousel-caption-wrapper p { font-size: 0.8rem; }
            .carousel-btn { padding: 4px 8px; font-size: 1rem; }

            .section-container { padding: 1.2rem 14px; }
            .neo-grid-4 { grid-template-columns: repeat(2, 1fr); gap: 8px; margin-bottom: 16px; }
            .neo-grid { grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 16px; }
            
            .stat-widget {
                padding: 10px 4px;
                flex-direction: column;
                text-align: center;
                align-items: center;
            }
            .stat-widget h2 { font-size: 1.3rem; }
            .stat-widget p { font-size: 0.62rem; }
            .stat-widget .stat-icon { font-size: 1.2rem; order: -1; margin-bottom: 2px; }

            .action-card {
                padding: 12px 4px;
                box-shadow: var(--shadow-sm);
            }
            .action-card:hover { transform: none; box-shadow: var(--shadow-sm); }
            .action-card:active { transform: translate(2px, 2px); box-shadow: var(--shadow-active); }
            .action-card .action-icon { font-size: 1.5rem; }
            .action-card h4 { font-size: 0.72rem; margin-top: 4px; }
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
            <a href="provider_dashboard.php" class="neo-brand"><i class="bi bi-car-front-fill me-1"></i>SCRS <span>PMU</span></a>
        </div>

        <div class="profile-container">
            <button class="profile-btn" id="profile-toggle">
                <i class="bi bi-person-fill fs-5"></i>
                <span><?php echo htmlspecialchars($provider_name); ?></span>
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
            <h2>Penyedia Kereta</h2>
            <button class="close-btn" id="close-sidebar"><i class="bi bi-x-lg"></i></button>
        </div>
        <nav class="sidebar-nav">
            <a href="provider_dashboard.php" class="sidebar-link active"><i class="bi bi-speedometer2"></i> Papan Pemuka</a>
            <a href="provider_cars.php" class="sidebar-link"><i class="bi bi-car-front-fill"></i> Urus Kenderaan</a>
            <a href="provider_bookings.php" class="sidebar-link"><i class="bi bi-clipboard-check-fill"></i> Senarai Permohonan</a>
            <a href="provider_history.php" class="sidebar-link"><i class="bi bi-clock-history"></i> Rekod Tempahan</a>
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
                        <span class="carousel-badge">Portal Penyedia Kereta PMU</span>
                        <h1>Papan Pemuka Kenderaan</h1>
                        <p>Pantau kenderaan sewaan dan semak tempahan masuk pelajar.</p>
                    </div>
                </div>
                <div class="carousel-item carousel-slide">
                    <img src="https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?auto=format&fit=crop&w=1000&q=80" alt="Kereta 2">
                    <div class="carousel-caption-wrapper">
                        <span class="carousel-badge" style="background: var(--green);">Kelulusan Pantas</span>
                        <h1>Urus Pembayaran DuitNow</h1>
                        <p>Sahkan tempahan dan kongsi Kod QR pembayaran DuitNow.</p>
                    </div>
                </div>
                <div class="carousel-item carousel-slide">
                    <img src="https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&fit=crop&w=1000&q=80" alt="Kereta 3">
                    <div class="carousel-caption-wrapper">
                        <span class="carousel-badge" style="background: var(--blue);">Rekod Sistematik</span>
                        <h1>Pantau Resit & Pulangan</h1>
                        <p>Semua bukti bayaran dan gambar pemulangan tersimpan rapi.</p>
                    </div>
                </div>
            </div>
            <button class="carousel-btn btn-prev" id="btn-prev"><i class="bi bi-chevron-left"></i></button>
            <button class="carousel-btn btn-next" id="btn-next"><i class="bi bi-chevron-right"></i></button>
        </div>

        <div class="section-container">
            
            <?php echo $message; ?>

            <?php if (!$has_qr): ?>
                <div class="neo-alert alert-warning" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>Perhatian:</strong> Sila muat naik Kod QR DuitNow anda untuk menerima bayaran pelajar.
                    </div>
                    <button type="button" class="neo-btn btn-green" onclick="openModal('qrCodeModal')">
                        <i class="bi bi-qr-code me-1"></i> Muat Naik QR
                    </button>
                </div>
            <?php endif; ?>

            <!-- HEADING PANDUAN PENGGUNA (TANPA KOTAK) -->
            <div style="margin-bottom: 20px;">
                <h1 style="font-size: 1.4rem; font-weight: 900; text-transform: uppercase; margin-bottom: 4px; color: var(--black); display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-speedometer2 text-dark"></i> Papan Pemuka Penyedia
                </h1>
                <p style="font-weight: 700; color: #555; font-size: 0.9rem; margin: 0; line-height: 1.4;">
                    Selamat Datang, <strong><?php echo htmlspecialchars($provider_name); ?></strong>!
                </p>
            </div>

            <!-- STATISTIK RINGKASAN (PAPAN METRIK MAKLUMAT - BUKAN BUTTON) -->
            <div class="section-title"><i class="bi bi-bar-chart-fill me-1"></i> Imbasan Sistem</div>
            <div class="neo-grid-4">
                <div class="stat-widget">
                    <div class="stat-icon icon-box-b">
                        <i class="bi bi-car-front-fill"></i>
                    </div>
                    <div class="stat-info">
                        <h2><?php echo $total_cars; ?></h2>
                        <p>Jumlah kereta didaftarkan</p>
                    </div>
                </div>
                <div class="stat-widget">
                    <div class="stat-icon icon-box-y">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="stat-info">
                        <h2><?php echo $total_pending; ?></h2>
                        <p>Menunggu disahkan</p>
                    </div>
                </div>
                <div class="stat-widget">
                    <div class="stat-icon icon-box-g">
                        <i class="bi bi-clipboard-check-fill"></i>
                    </div>
                    <div class="stat-info">
                        <h2><?php echo $total_active; ?></h2>
                        <p>Tempahan aktif</p>
                    </div>
                </div>
                <div class="stat-widget">
                    <div class="stat-icon icon-box-p">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div class="stat-info">
                        <h2>RM <?php echo number_format($total_earnings, 0); ?></h2>
                        <p>Pendapatan</p>
                    </div>
                </div>
            </div>

            <!-- MENU UTAMA PINTAS PENYEDIA (3 RUANGAN MENDATAR PADA MOBILE - TIADA SCROLL) -->
            <div class="section-title"><i class="bi bi-grid-fill me-1"></i> Akses Pintas</div>
            <div class="neo-grid" style="margin-bottom: 20px;">
                <div class="action-card bg-y" onclick="window.location.href='provider_cars.php'">
                    <i class="bi bi-car-front-fill action-icon"></i>
                    <h4>Senarai Kereta</h4>
                </div>
                <div class="action-card bg-g" onclick="window.location.href='provider_bookings.php'">
                    <i class="bi bi-clipboard-check-fill action-icon"></i>
                    <h4>Senarai Permohonan</h4>
                    <?php if ($total_pending > 0): ?>
                        <div style="margin-top: 4px; font-weight: 900; font-size: 0.65rem; background: var(--pink); color: #000; border: 1.5px solid #000; padding: 1px 4px; text-transform: uppercase;">
                            <?php echo $total_pending; ?> Menunggu Disahkan
                        </div>
                    <?php endif; ?>
                </div>
                <div class="action-card bg-w" onclick="window.location.href='provider_history.php'">
                    <i class="bi bi-clock-history action-icon"></i>
                    <h4>Rekod Tempahan</h4>
                </div>
            </div>

            <!-- BUTANG CEPAT QR BAYARAN -->
            <div style="background: var(--white); border: var(--border-thick); box-shadow: var(--shadow-solid); padding: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 30px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="bi bi-qr-code fs-1 text-primary"></i>
                    <div>
                        <h4 style="font-weight: 900; text-transform: uppercase; margin-bottom: 2px;">Kod QR DuitNow Pembayaran</h4>
                        <p style="font-weight: 700; color: #555; font-size: 0.85rem; margin: 0;">
                            <?php echo $has_qr ? 'Kod QR anda sedia digunakan untuk menerima bayaran daripada pelajar.' : 'Anda belum memuat naik Kod QR DuitNow.'; ?>
                        </p>
                    </div>
                </div>
                <button type="button" class="neo-btn btn-blue" onclick="openModal('qrCodeModal')">
                    <i class="bi bi-cloud-arrow-up-fill me-1"></i> <?php echo $has_qr ? 'Kemaskini QR' : 'Muat Naik QR'; ?>
                </button>
            </div>

            <!-- SENARAI PERMOHONAN PELAJAR -->
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 14px;">
                <div class="section-title" style="margin: 0;"><i class="bi bi-clipboard-data-fill me-1"></i> Senarai Permohonan</div>
                <a href="provider_bookings.php" class="neo-btn btn-sm btn-yellow" style="font-size: 0.78rem; padding: 6px 12px;">
                    Urus Semua Permohonan <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>

            <?php if ($result_applications && $result_applications->num_rows > 0): ?>
                <div style="display: flex; flex-direction: column; gap: 14px; margin-bottom: 30px;">
                    <?php while ($app = $result_applications->fetch_assoc()): 
                        $app_status = $app['status'];
                        $is_pending = ($app_status === 'Pending');
                        $is_approved = ($app_status === 'Approved');
                        
                        if ($is_pending) {
                            $status_label = 'Menunggu disahkan';
                            $status_bg = 'var(--yellow)';
                            $status_icon = 'bi-hourglass-split';
                            $border_accent = '#eab308';
                        } elseif ($is_approved) {
                            $status_label = 'Tempahan aktif';
                            $status_bg = 'var(--blue)';
                            $status_icon = 'bi-check-circle-fill';
                            $border_accent = '#0284c7';
                        } elseif ($app_status === 'Completed') {
                            $status_label = 'Selesai';
                            $status_bg = 'var(--green)';
                            $status_icon = 'bi-patch-check-fill';
                            $border_accent = '#16a34a';
                        } else {
                            $status_label = 'Ditolak';
                            $status_bg = 'var(--pink)';
                            $status_icon = 'bi-x-circle-fill';
                            $border_accent = '#dc2626';
                        }
                        
                        $phone_clean = preg_replace('/[^0-9]/', '', $app['student_phone']);
                        if (strpos($phone_clean, '0') === 0) {
                            $phone_clean = '6' . $phone_clean;
                        }
                        
                        $start_fmt = date("d/m/Y, h:i A", strtotime($app['start_date']));
                        $end_fmt = date("d/m/Y, h:i A", strtotime($app['end_date']));
                    ?>
                        <div class="neo-card bg-w" style="text-align: left; padding: 18px; margin-bottom: 0; display: flex; flex-direction: column; gap: 12px; border-left: 6px solid <?php echo $border_accent; ?>;">
                            
                            <!-- BARIS ATAS: KENDERAAN & STATUS -->
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; border-bottom: 2px dashed #ddd; padding-bottom: 10px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <?php if (!empty($app['car_image']) && file_exists($app['car_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($app['car_image']); ?>" alt="Car" style="width: 48px; height: 36px; object-fit: cover; border: 2px solid var(--black); border-radius: 4px;">
                                    <?php else: ?>
                                        <div style="width: 42px; height: 34px; background: var(--bg-color); border: 2px solid var(--black); display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                                            <i class="bi bi-car-front-fill text-primary"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <h4 style="margin: 0; font-size: 1rem; font-weight: 900; text-transform: uppercase;">
                                            <?php echo (!empty($app['car_brand']) ? htmlspecialchars($app['car_brand']) . ' ' : '') . htmlspecialchars($app['car_model']); ?> 
                                            <span style="font-size: 0.8rem; color: #555; font-weight: 700;">(<?php echo htmlspecialchars($app['car_plate']); ?>)</span>
                                        </h4>
                                        <span style="font-size: 0.75rem; font-weight: 800; color: #666;">ID Permohonan: #<?php echo $app['id']; ?></span>
                                    </div>
                                </div>
                                <span style="border: 2px solid var(--black); padding: 3px 10px; font-size: 0.72rem; text-transform: uppercase; font-weight: 900; background: <?php echo $status_bg; ?>; display: inline-flex; align-items: center; gap: 4px; box-shadow: 2px 2px 0px var(--black);">
                                    <i class="bi <?php echo $status_icon; ?>"></i> <?php echo $status_label; ?>
                                </span>
                            </div>

                            <!-- BARIS TENGAH: MAKLUMAT PELAJAR, TARIKH & JUMLAH -->
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; font-size: 0.85rem;">
                                <div>
                                    <div style="font-size: 0.72rem; font-weight: 900; text-transform: uppercase; color: #777;">Pelajar Pemohon:</div>
                                    <div style="font-weight: 800; color: var(--black); font-size: 0.92rem; margin-top: 2px;">
                                        <i class="bi bi-person-fill text-dark me-1"></i> <?php echo htmlspecialchars($app['student_name']); ?>
                                    </div>
                                    <?php if (!empty($app['student_matrix'])): ?>
                                        <div style="font-size: 0.75rem; font-weight: 700; color: #555;">No Matrik: <?php echo htmlspecialchars($app['student_matrix']); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($app['student_phone'])): ?>
                                        <a href="https://wa.me/<?php echo $phone_clean; ?>" target="_blank" style="display: inline-flex; align-items: center; gap: 4px; font-weight: 800; color: #15803d; font-size: 0.8rem; margin-top: 3px; text-decoration: none;">
                                            <i class="bi bi-whatsapp"></i> <?php echo htmlspecialchars($app['student_phone']); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <div>
                                    <div style="font-size: 0.72rem; font-weight: 900; text-transform: uppercase; color: #777;">Tempoh Sewaan:</div>
                                    <div style="font-weight: 700; color: #333; margin-top: 2px; font-size: 0.82rem;">
                                        <div><i class="bi bi-box-arrow-up-right text-success me-1"></i> <strong>Ambil:</strong> <?php echo $start_fmt; ?></div>
                                        <div style="margin-top: 2px;"><i class="bi bi-box-arrow-in-down-left text-danger me-1"></i> <strong>Pulang:</strong> <?php echo $end_fmt; ?></div>
                                    </div>
                                </div>

                                <div>
                                    <div style="font-size: 0.72rem; font-weight: 900; text-transform: uppercase; color: #777;">Jumlah Bayaran:</div>
                                    <div style="font-size: 1.2rem; font-weight: 900; color: var(--black); margin-top: 2px;">
                                        RM <?php echo number_format($app['total_price'], 2); ?>
                                    </div>
                                    <div style="font-size: 0.75rem; font-weight: 700; margin-top: 2px;">
                                        <?php if (!empty($app['payment_receipt']) && file_exists($app['payment_receipt'])): ?>
                                            <span style="color: #16a34a; font-weight: 800;"><i class="bi bi-receipt-cutoff me-1"></i> Resit Bayaran Ada</span>
                                        <?php else: ?>
                                            <span style="color: #666;"><i class="bi bi-clock me-1"></i> Resit belum dimuat naik</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- BARIS BAWAH: BUTANG TINDAKAN -->
                            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #eee; padding-top: 10px; flex-wrap: wrap; gap: 8px;">
                                <div style="font-size: 0.75rem; font-weight: 700; color: #888;">
                                    Permohonan dibuat: <?php echo date("d/m/Y, h:i A", strtotime($app['created_at'])); ?>
                                </div>
                                <div>
                                    <a href="provider_bookings.php" class="neo-btn btn-sm <?php echo $is_pending ? 'btn-green' : 'btn-blue'; ?>" style="font-size: 0.78rem; padding: 6px 14px;">
                                        <?php if ($is_pending): ?>
                                            <i class="bi bi-check2-circle me-1"></i> Semak & Sahkan Permohonan
                                        <?php elseif ($is_approved): ?>
                                            <i class="bi bi-gear-fill me-1"></i> Urus Tempahan & Pemulangan
                                        <?php else: ?>
                                            <i class="bi bi-eye-fill me-1"></i> Lihat Rekod Tempahan
                                        <?php endif; ?>
                                    </a>
                                </div>
                            </div>

                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="neo-card bg-w" style="text-align: center; padding: 35px 20px; margin-bottom: 30px;">
                    <i class="bi bi-inbox" style="font-size: 2.5rem; color: #999;"></i>
                    <h4 style="font-weight: 900; text-transform: uppercase; margin-top: 10px; font-size: 1rem;">Tiada Permohonan Tempahan</h4>
                    <p style="font-weight: 700; color: #666; font-size: 0.85rem; margin-bottom: 15px;">Pelajar belum membuat permohonan tempahan untuk kenderaan anda pada masa ini.</p>
                    <a href="provider_cars.php" class="neo-btn btn-blue" style="display: inline-flex;">
                        <i class="bi bi-car-front-fill me-1"></i> Semak Senarai Kenderaan Anda
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <!-- MODAL QR CODE PEMBAYARAN -->
    <div class="neo-modal-overlay" id="qrCodeModal">
        <div class="neo-modal">
            <div class="modal-header">
                <h3 class="modal-title"><i class="bi bi-qr-code me-1"></i> Kod QR DuitNow</h3>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div style="text-align: center; margin-bottom: 20px;">
                    <?php if ($has_qr): ?>
                        <img src="<?php echo htmlspecialchars($provider_info['qr_code_image']); ?>" alt="QR Code" style="max-height: 180px; max-width: 100%; border: 3px solid var(--black); box-shadow: 4px 4px 0px var(--black); padding: 5px; background: #fff;">
                        <p style="font-weight: 800; font-size: 0.85rem; margin-top: 10px; color: #2e7d32;"><i class="bi bi-check-circle-fill me-1"></i> Kod QR Aktif</p>
                    <?php else: ?>
                        <div style="border: 2px dashed var(--black); padding: 30px 10px; background: var(--bg-color);">
                            <i class="bi bi-qr-code-scan" style="font-size: 3rem; color: #666;"></i>
                            <p style="font-weight: 800; font-size: 0.85rem; margin-top: 10px; color: #666;">Belum ada Kod QR dimuat naik</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="display: flex; flex-direction: column; gap: 6px; margin-bottom: 20px;">
                    <label style="font-weight: 800; text-transform: uppercase; font-size: 0.85rem;">Pilih Gambar Kod QR DuitNow Baharu</label>
                    <input type="file" name="qr_image" accept=".jpg,.jpeg,.png" required style="border: 3px solid var(--black); padding: 8px; font-weight: 700; background: var(--bg-color);">
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="neo-btn" style="background: #ccc;" onclick="closeModal('qrCodeModal')">Tutup</button>
                    <button type="submit" name="upload_qr" class="neo-btn btn-green"><i class="bi bi-cloud-arrow-up-fill me-1"></i> Simpan Kod QR</button>
                </div>
            </form>
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        &copy; <?php echo date("Y"); ?> SCRS PMU. SISTEM SEWAAN KERETA.
    </footer>

    <!-- JAVASCRIPT ASLI -->
    <script>
        // 1. DROPDOWN PROFIL
        const profileToggle = document.getElementById('profile-toggle');
        const profileMenu = document.getElementById('profile-menu');
        
        if (profileToggle && profileMenu) {
            profileToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                profileMenu.classList.toggle('show');
            });

            document.addEventListener('click', function(e) {
                if (!profileToggle.contains(e.target) && !profileMenu.contains(e.target)) {
                    profileMenu.classList.remove('show');
                }
            });
        }

        // 2. SIDEBAR
        const openSidebarBtn = document.getElementById('open-sidebar');
        const closeSidebarBtn = document.getElementById('close-sidebar');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');

        function openSidebar() { sidebar.classList.add('open'); sidebarOverlay.classList.add('show'); }
        function closeSidebar() { sidebar.classList.remove('open'); sidebarOverlay.classList.remove('show'); }

        if (openSidebarBtn) openSidebarBtn.addEventListener('click', openSidebar);
        if (closeSidebarBtn) closeSidebarBtn.addEventListener('click', closeSidebar);
        if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

        // 3. CAROUSEL ASLI
        const track = document.getElementById('carousel-track');
        if (track) {
            const slides = track.getElementsByClassName('carousel-slide');
            const totalSlides = slides.length;
            let currentSlide = 0;

            function updateSlide() {
                track.style.transform = `translateX(-${currentSlide * 100}%)`;
            }

            document.getElementById('btn-next').addEventListener('click', () => {
                currentSlide = (currentSlide + 1) % totalSlides;
                updateSlide();
            });

            document.getElementById('btn-prev').addEventListener('click', () => {
                currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
                updateSlide();
            });

            setInterval(() => {
                currentSlide = (currentSlide + 1) % totalSlides;
                updateSlide();
            }, 6000);
        }

        // 4. MODAL
        window.openModal = function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.add('show');
        };

        window.closeModal = function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.remove('show');
        };

        // Cegah paparan semula melalui butang Back selepas log keluar
        window.addEventListener('pageshow', function(event) {
            if (event.persisted || (window.performance && window.performance.navigation && window.performance.navigation.type === 2)) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>