<?php
session_start();
require 'db.php';

// Semak jika pengguna telah log masuk dan merupakan pegawai JHEPP
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'jhepp') {
    header("Location: index.php");
    exit();
}

$jhepp_username = $_SESSION['username'] ?? 'JHEPP';
$jhepp_fullname = $_SESSION['full_name'] ?? $jhepp_username;

// STATISTIK JHEPP
// 1. Permohonan Pelajar Pending (E-mel Telah Disahkan - Menunggu Tindakan JHEPP)
$res_pending = $conn->query("SELECT COUNT(*) AS total FROM students WHERE status = 'pending' AND email_verified = 1");
$pending_students = $res_pending ? ($res_pending->fetch_assoc()['total'] ?? 0) : 0;

// 2. Pelajar Belum Sahkan E-mel
$res_unverified = $conn->query("SELECT COUNT(*) AS total FROM students WHERE status = 'pending' AND email_verified = 0");
$unverified_email_students = $res_unverified ? ($res_unverified->fetch_assoc()['total'] ?? 0) : 0;

// 3. Pelajar Diluluskan
$res_approved = $conn->query("SELECT COUNT(*) AS total FROM students WHERE status = 'approved'");
$approved_students = $res_approved ? ($res_approved->fetch_assoc()['total'] ?? 0) : 0;

// 4. Pelajar Ditolak
$res_rejected = $conn->query("SELECT COUNT(*) AS total FROM students WHERE status = 'rejected'");
$rejected_students = $res_rejected ? ($res_rejected->fetch_assoc()['total'] ?? 0) : 0;

// 5. Jumlah Keseluruhan Pelajar
$res_total = $conn->query("SELECT COUNT(*) AS total FROM students");
$total_students = $res_total ? ($res_total->fetch_assoc()['total'] ?? 0) : 0;
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Papan Pemuka JHEPP - SCRS PMU</title>
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Master Neo-Brutalism CSS -->
    <link rel="stylesheet" href="neo-style.css">

    <style>
        /* HERO CARD */
        .hero-banner {
            background: var(--yellow);
            border: var(--border-thick);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-solid);
            padding: 22px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .hero-title {
            font-size: 1.45rem;
            font-weight: 900;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .hero-desc {
            font-size: 0.9rem;
            font-weight: 700;
            color: #333;
            line-height: 1.4;
        }

        /* STAT GRIDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-solid);
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: var(--transition);
        }
        .stat-card:hover {
            box-shadow: var(--shadow-lg);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border: var(--border-thin);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            flex-shrink: 0;
            box-shadow: var(--shadow-sm);
        }

        .stat-content h3 {
            font-size: 1.6rem;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 3px;
        }
        .stat-content p {
            font-size: 0.775rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #555;
        }

        /* QUICK ACTION CARD */
        .action-banner {
            background: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-solid);
            padding: 18px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px;
            border-left: 10px solid var(--pink);
        }

        /* TABLE */
        .table-card {
            background: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-solid);
            overflow: hidden;
            margin-bottom: 25px;
        }

        .table-responsive {
            overflow-x: auto;
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background: var(--yellow);
            border-bottom: var(--border-thick);
            padding: 10px 14px;
            font-weight: 900;
            font-size: 0.8rem;
            text-transform: uppercase;
            white-space: nowrap;
        }

        td {
            padding: 10px 14px;
            border-bottom: 2px solid #ddd;
            font-weight: 700;
            font-size: 0.85rem;
            vertical-align: middle;
        }

        tr:last-child td { border-bottom: none; }
        tr:hover td { background-color: #fafafa; }

        .doc-link-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            font-weight: 800;
            font-size: 0.75rem;
            background: var(--white);
            border: var(--border-thin);
            box-shadow: var(--shadow-sm);
            margin: 2px;
            transition: var(--transition);
        }
        .doc-link-btn:hover {
            background: var(--yellow);
            transform: translate(-1px, -1px);
        }

        @media (max-width: 600px) {
            .hero-banner { padding: 14px; }
            .hero-title { font-size: 1.25rem; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <header class="neo-navbar">
        <div class="neo-nav-left">
            <button class="menu-toggle-btn" id="open-sidebar"><i class="bi bi-list"></i></button>
            <a href="jhepp_dashboard.php" class="neo-brand"><i class="bi bi-car-front-fill me-1"></i>SCRS <span>PMU</span></a>
        </div>
        <div class="nav-right-actions">
            <div class="profile-container">
                <button class="profile-btn" id="profile-toggle">
                    <i class="bi bi-person-fill fs-5"></i>
                    <span><?php echo htmlspecialchars($jhepp_username); ?></span>
                </button>
                <ul class="dropdown-menu" id="profile-menu">
                    <li><a href="edit_profile.php" class="dropdown-item"><i class="bi bi-gear-fill me-2"></i> Edit Profil</a></li>
                    <li><a href="logout.php" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i> Log Keluar</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- SIDEBAR -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Portal JHEPP</h2>
            <button class="close-btn" id="close-sidebar"><i class="bi bi-x-lg"></i></button>
        </div>
        <nav class="sidebar-nav">
            <a href="jhepp_dashboard.php" class="sidebar-link active"><i class="bi bi-speedometer2"></i> Papan Pemuka</a>
            <a href="jhepp_pending.php" class="sidebar-link"><i class="bi bi-hourglass-split"></i> Menunggu Kelulusan</a>
            <a href="jhepp_approved.php" class="sidebar-link"><i class="bi bi-check-circle-fill"></i> Pelajar Diluluskan</a>
            <a href="jhepp_rejected.php" class="sidebar-link"><i class="bi bi-x-circle-fill"></i> Pendaftaran Ditolak</a>
            <a href="senarai_pendaftaran.php" class="sidebar-link"><i class="bi bi-people-fill"></i> Semua Rekod Pelajar</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        
        <!-- HERO BANNER -->
        <div class="hero-banner">
            <div>
                <h1 class="hero-title"><i class="bi bi-shield-shaded me-2"></i>Portal Pegawai JHEPP PMU</h1>
                <p class="hero-desc">Selamat bertugas, <strong><?php echo htmlspecialchars($jhepp_fullname); ?></strong>. Semak dan sahkan permohonan akaun pelajar Politeknik Mukah.</p>
            </div>
            <a href="jhepp_pending.php" class="neo-btn" style="background: var(--blue);">
                <i class="bi bi-hourglass-split"></i> Menunggu Kelulusan
                <?php if ($pending_students > 0): ?>
                    <span style="background: var(--pink); border: 2px solid #000; padding: 2px 8px; font-size: 0.75rem; border-radius: var(--radius-full);">
                        <?php echo $pending_students; ?>
                    </span>
                <?php endif; ?>
            </a>
        </div>

        <!-- STATISTIK KAD -->
        <div class="section-title" style="margin-bottom: 12px;"><i class="bi bi-bar-chart-fill me-1"></i> Ringkasan Status Permohonan Pelajar</div>
        <div class="stats-grid">
            
            <!-- 1. PENDING VERIFICATION -->
            <a href="jhepp_pending.php" class="stat-card" style="cursor: pointer;">
                <div class="stat-icon" style="background: var(--yellow);">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $pending_students; ?></h3>
                    <p>Menunggu Kelulusan</p>
                </div>
            </a>

            <!-- 2. APPROVED -->
            <a href="jhepp_approved.php" class="stat-card" style="cursor: pointer;">
                <div class="stat-icon" style="background: var(--green);">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $approved_students; ?></h3>
                    <p>Pelajar Diluluskan</p>
                </div>
            </a>

            <!-- 3. REJECTED -->
            <a href="jhepp_rejected.php" class="stat-card" style="cursor: pointer;">
                <div class="stat-icon" style="background: var(--pink);">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $rejected_students; ?></h3>
                    <p>Pelajar Ditolak</p>
                </div>
            </a>

            <!-- 4. TOTAL REGISTERED -->
            <a href="senarai_pendaftaran.php" class="stat-card" style="cursor: pointer;">
                <div class="stat-icon" style="background: var(--blue);">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_students; ?></h3>
                    <p>Jumlah Pelajar</p>
                </div>
            </a>

        </div>

        <!-- QUICK ACTION CARD -->
        <?php if ($pending_students > 0): ?>
            <div class="action-banner">
                <div>
                    <h3 style="font-size: 1.2rem; font-weight: 900; text-transform: uppercase; margin-bottom: 4px; color: var(--black);">
                        <i class="bi bi-exclamation-circle-fill text-warning me-1"></i> Terdapat <?php echo $pending_students; ?> Permohonan Pelajar Perlu Disemak
                    </h3>
                    <p style="font-weight: 700; color: #555; font-size: 0.9rem; margin: 0;">
                        Pelajar ini telah mengesahkan alamat e-mel dan memuat naik kad matrik serta lesen memandu.
                    </p>
                </div>
                <a href="jhepp_pending.php" class="neo-btn" style="background: var(--green);">
                    <i class="bi bi-check-circle-fill"></i> Buka Menunggu Kelulusan Sekarang
                </a>
            </div>
        <?php endif; ?>

        <!-- PANDUAN & TINDAKAN PANTAS JHEPP -->
        <div class="section-title" style="margin-bottom: 12px;"><i class="bi bi-lightning-charge-fill me-1"></i> Tindakan Pantas & Pengurusan JHEPP</div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 24px;">
            <div class="neo-card" style="display: flex; flex-direction: column; justify-content: space-between; margin-bottom: 0;">
                <div>
                    <h3 style="font-weight: 900; font-size: 1.15rem; text-transform: uppercase; margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-person-lines-fill text-primary"></i> Senarai Pendaftaran Pelajar
                    </h3>
                    <p style="font-weight: 700; color: #555; font-size: 0.9rem; line-height: 1.5; margin-bottom: 16px;">
                        Akses rekod penuh semua pendaftaran pelajar, semak dokumen Kad Matrik PMU dan Lesen Memandu, serta uruskan kelulusan akaun.
                    </p>
                </div>
                <a href="senarai_pendaftaran.php" class="neo-btn btn-yellow" style="width: 100%; justify-content: center;">
                    <i class="bi bi-arrow-right-circle me-1"></i> Buka Senarai Pendaftaran
                </a>
            </div>

            <div class="neo-card" style="display: flex; flex-direction: column; justify-content: space-between; margin-bottom: 0;">
                <div>
                    <h3 style="font-weight: 900; font-size: 1.15rem; text-transform: uppercase; margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-shield-check text-success"></i> Garis Panduan Pengesahan
                    </h3>
                    <p style="font-weight: 700; color: #555; font-size: 0.9rem; line-height: 1.5; margin-bottom: 16px;">
                        Pastikan Kad Pelajar PMU sah dan Lesen Memandu masih aktif sebelum meluluskan akaun bagi memastikan kepatuhan peraturan PMU.
                    </p>
                </div>
                <a href="jhepp_pending.php" class="neo-btn btn-green" style="width: 100%; justify-content: center;">
                    <i class="bi bi-check2-square me-1"></i> Semak Pelajar Menunggu
                </a>
            </div>
        </div>

    </main>

    <!-- FOOTER -->
    <footer>
        &copy; <?php echo date("Y"); ?> SCRS PMU. SISTEM SEWAAN KERETA.
    </footer>

    <!-- VANILLA JS -->
    <script>
        const openSidebarBtn = document.getElementById('open-sidebar');
        const closeSidebarBtn = document.getElementById('close-sidebar');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        const profileToggle = document.getElementById('profile-toggle');
        const profileMenu = document.getElementById('profile-menu');

        function openSidebar() {
            sidebar.classList.add('open');
            sidebarOverlay.classList.add('show');
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('show');
        }

        if (openSidebarBtn) openSidebarBtn.addEventListener('click', openSidebar);
        if (closeSidebarBtn) closeSidebarBtn.addEventListener('click', closeSidebar);
        if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

        if (profileToggle && profileMenu) {
            profileToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                profileMenu.classList.toggle('show');
            });
            document.addEventListener('click', () => {
                profileMenu.classList.remove('show');
            });
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
