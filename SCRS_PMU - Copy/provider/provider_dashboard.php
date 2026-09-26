<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/notifications.php';

// Semak jika pengguna telah log masuk dan merupakan Penyedia Kereta (Provider)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'provider') {
    header("Location: ../index.php");
    exit();
}

$provider_id = $_SESSION['provider_id'];
$provider_name = $_SESSION['username'];
$message = "";

// --- 1. PROSES MUAT NAIK QR CODE PEMBAYARAN CEPAT ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_qr'])) {
    if (!empty($_FILES["qr_image"]["name"])) {
        $targetDir = "../uploads/qr_codes/";
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
            if (!empty($old_qr_row['qr_code_image']) && file_exists('../' . $old_qr_row['qr_code_image'])) {
                unlink('../' . $old_qr_row['qr_code_image']);
            }
        }
        $stmt_old_qr->close();

        if (move_uploaded_file($_FILES["qr_image"]["tmp_name"], $targetPath)) {
            $db_qr_path = "uploads/qr_codes/" . $newQrName;
            $sql = "UPDATE providers SET qr_code_image = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $db_qr_path, $provider_id);
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
$has_qr = (!empty($provider_info['qr_code_image']) && (file_exists($provider_info['qr_code_image']) || file_exists('../' . $provider_info['qr_code_image'])));
$stmt_prov->close();

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
            background-color: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-sm);
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            font-size: 1.25rem;
            font-weight: 900;
            cursor: pointer;
            transition: var(--transition);
            z-index: 10;
        }
        .carousel-btn:hover { background-color: var(--yellow); }
        .carousel-btn:active { transform: translateY(-50%) translate(2px, 2px); box-shadow: var(--shadow-active); }
        .btn-prev { left: 16px; }
        .btn-next { right: 16px; }

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
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
            align-items: stretch;
        }
        .neo-grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }

        /* STAT WIDGETS (IMBASAN SISTEM - METRIK MAKLUMAT, BUKAN BUTTON) */
        .stat-widget {
            background-color: var(--white);
            border: 1px solid #e2e8f0;
            border-radius: var(--radius-md);
            padding: 14px 16px;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 14px;
            cursor: default;
            user-select: none;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: none;
        }
        .stat-widget:hover {
            transform: none !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05) !important;
        }

        .stat-widget.widget-blue { border-left: 4px solid var(--blue); }
        .stat-widget.widget-yellow { border-left: 4px solid var(--yellow); }
        .stat-widget.widget-green { border-left: 4px solid var(--green); }
        .stat-widget.widget-pink { border-left: 4px solid var(--pink); }
        
        .stat-widget .stat-info { display: flex; flex-direction: column; min-width: 0; }
        .stat-widget h2 { font-size: 1.65rem; font-weight: 900; line-height: 1.1; margin-bottom: 2px; color: var(--black); }
        .stat-widget p { font-weight: 700; font-size: 0.72rem; text-transform: uppercase; color: #64748b; margin: 0; letter-spacing: 0.4px; }
        .stat-widget .stat-icon {
            width: 44px;
            height: 44px;
            min-width: 44px;
            border: none;
            border-radius: 10px;
            box-shadow: none !important;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
        }
        .icon-box-b { background-color: rgba(67, 97, 238, 0.14); color: #2563eb; }
        .icon-box-y { background-color: rgba(255, 190, 11, 0.22); color: #b45309; }
        .icon-box-g { background-color: rgba(0, 245, 212, 0.28); color: #047857; }
        .icon-box-p { background-color: rgba(255, 102, 196, 0.22); color: #be185d; }

        /* ACTION CARDS */
        .action-card {
            position: relative;
            border: var(--border-thick);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-solid);
            padding: 18px 8px 14px 8px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            color: var(--black);
            width: 100%;
            height: 100%;
            min-height: 125px;
            box-sizing: border-box;
            overflow: visible;
        }
        .action-card:hover { 
            transform: translate(-2px, -2px); 
            box-shadow: var(--shadow-lg); 
        }
        .action-card:active { 
            transform: translate(2px, 2px); 
            box-shadow: var(--shadow-active); 
        }
        .action-card h4 { 
            font-size: 0.85rem; 
            font-weight: 900; 
            text-transform: uppercase; 
            margin: 0; 
            line-height: 1.25;
            height: 2.5em;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            width: 100%;
            word-break: break-word;
        }
        .action-card .action-icon { 
            font-size: 2rem; 
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 4px;
            margin-bottom: 6px;
            flex-shrink: 0;
        }

        .action-badge {
            position: absolute;
            top: -11px;
            left: 50%;
            transform: translateX(-50%);
            background: #ff2a6d;
            color: #ffffff;
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 0.3px;
            padding: 2.5px 10px;
            border: 1.5px solid var(--black);
            border-radius: 20px;
            box-shadow: 2px 2px 0px var(--black);
            line-height: 1.2;
            white-space: nowrap;
            pointer-events: none;
            z-index: 5;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-transform: none;
        }
        .badge-pulse-dot {
            width: 6px;
            height: 6px;
            background-color: #ffffff;
            border-radius: 50%;
            display: inline-block;
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
            .neo-grid-4 { grid-template-columns: repeat(2, 1fr); gap: 8px; margin-bottom: 16px; }
            .neo-grid { 
                grid-template-columns: repeat(3, minmax(0, 1fr)); 
                gap: 8px; 
                margin-bottom: 16px; 
                align-items: stretch;
            }
            
            .stat-widget {
                padding: 10px 10px;
                display: flex;
                flex-direction: row;
                align-items: center;
                text-align: left;
                gap: 10px;
                border: 1px solid #e2e8f0 !important;
                border-left: 4px solid var(--black) !important;
                border-radius: var(--radius-sm);
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04) !important;
                background-color: var(--white);
            }
            .stat-widget.widget-blue { border-left-color: var(--blue) !important; }
            .stat-widget.widget-yellow { border-left-color: var(--yellow) !important; }
            .stat-widget.widget-green { border-left-color: var(--green) !important; }
            .stat-widget.widget-pink { border-left-color: var(--pink) !important; }
            .stat-widget:hover { transform: none !important; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04) !important; }
            
            .stat-widget .stat-info { display: flex; flex-direction: column; min-width: 0; }
            .stat-widget h2 { font-size: 1.25rem; font-weight: 900; line-height: 1; margin-bottom: 2px; color: var(--black); }
            .stat-widget p { font-size: 0.62rem; font-weight: 700; color: #64748b; text-transform: uppercase; line-height: 1.15; margin: 0; }
            .stat-widget .stat-icon { 
                width: 36px; 
                height: 36px; 
                min-width: 36px; 
                font-size: 1.15rem; 
                border: none !important;
                border-radius: 8px; 
                box-shadow: none !important;
                margin: 0; 
                order: 0;
            }

            .action-card {
                padding: 12px 4px 10px 4px;
                box-shadow: var(--shadow-sm);
                height: 110px;
                min-height: 110px;
            }
            .action-card:hover { transform: none; box-shadow: var(--shadow-sm); }
            .action-card:active { transform: translate(2px, 2px); box-shadow: var(--shadow-active); }
            .action-card .action-icon { 
                font-size: 1.55rem; 
                height: 28px;
                margin-top: 4px;
                margin-bottom: 4px;
            }
            .action-card h4 { 
                font-size: 0.72rem; 
                height: 2.4em;
                line-height: 1.15;
            }
            .action-badge {
                top: -9px;
                left: 50%;
                transform: translateX(-50%);
                font-size: 0.58rem;
                font-weight: 800;
                padding: 2px 8px;
                border-width: 1.5px;
                box-shadow: 1.5px 1.5px 0px var(--black);
                gap: 4px;
            }
            .badge-pulse-dot {
                width: 5px;
                height: 5px;
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
            <a href="provider_dashboard.php" class="neo-brand">SCRS PMU</a>
        </div>

        <?php render_navbar_actions($conn, 'provider', $provider_id, $provider_name, '../'); ?>
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
            <a href="javascript:void(0)" onclick="closeSidebar(); openModal('qrCodeModal');" class="sidebar-link"><i class="bi bi-qr-code"></i> Kod QR DuitNow</a>
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
                <div class="neo-alert alert-warning" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        <span><strong>Perhatian:</strong> Anda belum memuat naik Kod QR DuitNow. Sila muat naik Kod QR anda untuk menerima bayaran daripada pelajar.</span>
                    </div>
                    <button type="button" class="neo-btn btn-green" onclick="openModal('qrCodeModal')">
                        <i class="bi bi-cloud-arrow-up-fill me-1"></i> Muat Naik QR
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
                <div class="stat-widget widget-blue">
                    <div class="stat-icon icon-box-b">
                        <i class="bi bi-car-front-fill"></i>
                    </div>
                    <div class="stat-info">
                        <h2><?php echo $total_cars; ?></h2>
                        <p>Jumlah kereta didaftarkan</p>
                    </div>
                </div>
                <div class="stat-widget widget-yellow">
                    <div class="stat-icon icon-box-y">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="stat-info">
                        <h2><?php echo $total_pending; ?></h2>
                        <p>Menunggu disahkan</p>
                    </div>
                </div>
                <div class="stat-widget widget-green">
                    <div class="stat-icon icon-box-g">
                        <i class="bi bi-clipboard-check-fill"></i>
                    </div>
                    <div class="stat-info">
                        <h2><?php echo $total_active; ?></h2>
                        <p>Tempahan aktif</p>
                    </div>
                </div>
                <div class="stat-widget widget-pink">
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
            <div class="neo-grid" style="margin-bottom: 24px;">
                <div class="action-card bg-y" onclick="window.location.href='provider_cars.php'">
                    <i class="bi bi-car-front-fill action-icon"></i>
                    <h4>Senarai Kereta</h4>
                </div>
                <div class="action-card bg-g" onclick="window.location.href='provider_bookings.php'">
                    <?php if ($total_pending > 0): ?>
                        <span class="action-badge">
                            <span class="badge-pulse-dot"></span>
                            <?php echo $total_pending; ?> Menunggu
                        </span>
                    <?php endif; ?>
                    <i class="bi bi-clipboard-check-fill action-icon"></i>
                    <h4>Senarai Permohonan</h4>
                </div>
                <div class="action-card bg-w" onclick="window.location.href='provider_history.php'">
                    <i class="bi bi-clock-history action-icon"></i>
                    <h4>Rekod Tempahan</h4>
                </div>
            </div>

            <!-- BUTANG PANDUAN PENGGUNAAN SISTEM (BUKA MODAL AGAR TIDAK MEMENUHI DASHBOARD SEPERTI DI STUDENT DASHBOARD) -->
            <div style="margin-bottom: 25px;">
                <button type="button" class="neo-btn" onclick="openGuideModal()" style="background: var(--white); width: 100%; justify-content: space-between; padding: 12px 16px; border: var(--border-thick); box-shadow: var(--shadow-solid); color: var(--black);">
                    <span style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem;">
                        <i class="bi bi-info-circle-fill text-primary"></i>
                        <span>Cara Mengurus Sewaan</span>
                    </span>
                    <span style="font-size: 0.75rem; background: var(--yellow); border: 2px solid var(--black); padding: 3px 10px; border-radius: var(--radius-full); display: inline-flex; align-items: center; gap: 2px; box-shadow: 2px 2px 0px var(--black);">
                        Panduan <i class="bi bi-arrow-right-short"></i>
                    </span>
                </button>
            </div>

        </div>
    </main>

    <!-- MODAL PANDUAN PENYEDIA -->
    <div class="neo-modal-overlay" id="guideModalOverlay" onclick="closeGuideModalOutside(event)">
        <div class="neo-modal" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="modal-title"><i class="bi bi-info-circle-fill text-primary me-2"></i>Panduan Penggunaan Sistem</h3>
                <button type="button" class="close-btn" onclick="closeGuideModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p style="font-weight: 700; color: #555; font-size: 0.9rem; margin-bottom: 15px;">Ikuti 4 langkah mudah berikut untuk menguruskan kenderaan dan tempahan anda:</p>
                <div class="guide-steps-grid">
                    <div class="guide-step-card">
                        <span class="step-num">Langkah 1</span>
                        <h5 style="font-weight: 900; text-transform: uppercase; font-size: 0.95rem; margin-bottom: 5px;"><i class="bi bi-qr-code text-primary me-1"></i> Sediakan Kod QR Pembayaran</h5>
                        <p style="font-size: 0.85rem; font-weight: 600; color: #444; margin: 0; line-height: 1.4;">Pastikan Kod QR DuitNow anda telah dimuat naik. Kod QR ini dipaparkan kepada pelajar untuk pembayaran selepas tempahan diluluskan.</p>
                        <div style="margin-top: 8px;">
                            <button type="button" class="neo-btn <?php echo $has_qr ? 'btn-blue' : 'btn-yellow'; ?>" style="padding: 4px 10px; font-size: 0.75rem;" onclick="closeGuideModal(); openModal('qrCodeModal');">
                                <i class="bi bi-cloud-arrow-up-fill me-1"></i> <?php echo $has_qr ? 'Kemaskini Kod QR' : 'Muat Naik Kod QR Sekarang'; ?>
                            </button>
                        </div>
                    </div>
                    <div class="guide-step-card">
                        <span class="step-num">Langkah 2</span>
                        <h5 style="font-weight: 900; text-transform: uppercase; font-size: 0.95rem; margin-bottom: 5px;"><i class="bi bi-car-front-fill text-success me-1"></i> Tambah & Urus Kenderaan</h5>
                        <p style="font-size: 0.85rem; font-weight: 600; color: #444; margin: 0; line-height: 1.4;">Di menu <strong>Senarai Kereta</strong>, daftarkan kenderaan sewa anda dengan menetapkan model, plat pendaftaran, kadar sewa (per jam atau harian), dan gambar kereta.</p>
                    </div>
                    <div class="guide-step-card">
                        <span class="step-num">Langkah 3</span>
                        <h5 style="font-weight: 900; text-transform: uppercase; font-size: 0.95rem; margin-bottom: 5px;"><i class="bi bi-clipboard-check-fill text-warning me-1"></i> Semak Tempahan & Bayaran</h5>
                        <p style="font-size: 0.85rem; font-weight: 600; color: #444; margin: 0; line-height: 1.4;">Pantau tempahan masuk di menu <strong>Senarai Permohonan</strong>. Luluskan tempahan, semak resit bayaran yang dihantar pelajar, dan serahkan kunci kenderaan.</p>
                    </div>
                    <div class="guide-step-card">
                        <span class="step-num">Langkah 4</span>
                        <h5 style="font-weight: 900; text-transform: uppercase; font-size: 0.95rem; margin-bottom: 5px;"><i class="bi bi-check-circle-fill text-info me-1"></i> Sahkan Pemulangan Kenderaan</h5>
                        <p style="font-size: 0.85rem; font-weight: 600; color: #444; margin: 0; line-height: 1.4;">Selepas penyewa memulangkan kereta bersama gambar kenderaan, semak fizikal kereta dan sahkan status tempahan sebagai selesai.</p>
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

    <!-- MODAL QR CODE PEMBAYARAN -->
    <div class="neo-modal-overlay" id="qrCodeModal" onclick="if(event.target === this) closeModal('qrCodeModal')">
        <div class="neo-modal" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="modal-title"><i class="bi bi-qr-code me-1"></i> Kod QR DuitNow</h3>
                <button type="button" class="close-btn" onclick="closeModal('qrCodeModal')">&times;</button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div style="text-align: center; margin-bottom: 20px;">
                    <?php if ($has_qr): ?>
                        <?php 
                        $qr_img_src = htmlspecialchars($provider_info['qr_code_image'] ?? '');
                        if (!empty($qr_img_src) && strpos($qr_img_src, '../') !== 0 && !filter_var($qr_img_src, FILTER_VALIDATE_URL)) {
                            $qr_img_src = '../' . $qr_img_src;
                        }
                        if (!empty($qr_img_src)) {
                            $qr_img_src .= (strpos($qr_img_src, '?') !== false ? '&' : '?') . 'v=' . time();
                        }
                        ?>
                        <img src="<?php echo $qr_img_src; ?>" alt="QR Code" style="max-height: 180px; max-width: 100%; border: 3px solid var(--black); box-shadow: 4px 4px 0px var(--black); padding: 5px; background: #fff;">
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

        // 5. MODAL PANDUAN PENYEDIA
        function openGuideModal() {
            const guideModal = document.getElementById('guideModalOverlay');
            if (guideModal) guideModal.classList.add('show');
        }

        function closeGuideModal() {
            const guideModal = document.getElementById('guideModalOverlay');
            if (guideModal) guideModal.classList.remove('show');
        }

        function closeGuideModalOutside(e) {
            if (e.target.id === 'guideModalOverlay') {
                closeGuideModal();
            }
        }

        // Cegah paparan semula melalui butang Back selepas log keluar
        window.addEventListener('pageshow', function(event) {
            if (event.persisted || (window.performance && window.performance.navigation && window.performance.navigation.type === 2)) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>