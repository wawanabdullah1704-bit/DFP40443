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

// 6. 10 Pendaftaran Pelajar Terkini
$sql_recent = "SELECT id, full_name, username, no_pendaftaran, phone_no, email, status, email_verified, student_id_file, driving_license_file, created_at 
               FROM students 
               ORDER BY created_at DESC LIMIT 10";
$recent_students = $conn->query($sql_recent);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Papan Pemuka JHEPP - SCRS PMU</title>
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700;900&display=swap" rel="stylesheet">

    <!-- CSS NEO-BRUTALISM -->
    <style>
        :root {
            --black: #000000;
            --white: #ffffff;
            --yellow: #ffde59;
            --green: #00e676;
            --blue: #00e5ff;
            --pink: #ff66c4;
            --orange: #ff914d;
            --bg-color: #f4f4f0;
            --border-thick: 4px solid var(--black);
            --shadow-solid: 6px 6px 0px var(--black);
            --shadow-hover: 4px 4px 0px var(--black);
            --shadow-active: 0px 0px 0px var(--black);
            --transition: all 0.15s ease-in-out;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Space Grotesk', sans-serif; }

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
        button, input, select { font-family: inherit; }

        /* NAVBAR */
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
            background: none;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: var(--transition);
        }
        .menu-toggle-btn:hover { transform: scale(1.1); }

        .neo-brand {
            font-size: 1.5rem;
            font-weight: 900;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--black);
            text-decoration: none;
            display: inline-block;
        }

        .neo-brand:hover { color: #333; }

        /* PROFILE DROPDOWN */
        .nav-right-actions { display: flex; align-items: center; gap: 12px; }
        .profile-container { position: relative; }
        .profile-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background-color: var(--yellow);
            border: 3px solid var(--black);
            padding: 8px 14px;
            font-weight: 800;
            box-shadow: 4px 4px 0px var(--black);
            cursor: pointer;
            transition: var(--transition);
        }
        .profile-btn:hover { transform: translate(-2px, -2px); box-shadow: 6px 6px 0px var(--black); }
        .dropdown-menu {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            background: var(--white);
            border: 3px solid var(--black);
            box-shadow: 6px 6px 0px var(--black);
            width: 170px;
            display: none;
            z-index: 1001;
            list-style: none;
        }
        .dropdown-menu.show { display: block; }
        .dropdown-item {
            display: flex;
            align-items: center;
            padding: 10px 14px;
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--black);
            text-decoration: none;
            transition: var(--transition);
        }
        .dropdown-item:hover { background-color: var(--pink); color: var(--black); }

        .neo-btn {
            background-color: var(--yellow);
            color: var(--black);
            font-weight: 900;
            text-transform: uppercase;
            padding: 10px 16px;
            border: 3px solid var(--black);
            box-shadow: 4px 4px 0px var(--black);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: var(--transition);
        }

        .neo-btn:hover {
            transform: translate(-2px, -2px);
            box-shadow: 6px 6px 0px var(--black);
        }

        .neo-btn:active {
            transform: translate(2px, 2px);
            box-shadow: 2px 2px 0px var(--black);
        }

        /* SIDEBAR */
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
            border-bottom: var(--border-thick);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--yellow);
        }
        .sidebar-header h2 { font-size: 1.2rem; font-weight: 900; text-transform: uppercase; }
        .close-btn {
            border: 3px solid var(--black);
            background: var(--white);
            padding: 5px 10px;
            font-weight: 900;
            box-shadow: 2px 2px 0px var(--black);
            cursor: pointer;
            transition: var(--transition);
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
        .sidebar-link.active, .sidebar-link:hover {
            border: 3px solid var(--black);
            background: var(--white);
            transform: translate(-2px, -2px);
            box-shadow: 4px 4px 0px var(--black);
        }
        .sidebar-link.logout-link:hover { background-color: var(--pink); }

        /* MAIN CONTENT */
        .main-content {
            flex: 1;
            padding: 2rem 20px;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        /* HERO CARD */
        .hero-banner {
            background: var(--yellow);
            border: var(--border-thick);
            box-shadow: var(--shadow-solid);
            padding: 24px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .hero-title {
            font-size: 1.6rem;
            font-weight: 900;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .hero-desc {
            font-size: 0.95rem;
            font-weight: 700;
            color: #333;
            line-height: 1.4;
        }

        /* STAT GRIDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--white);
            border: var(--border-thick);
            box-shadow: var(--shadow-solid);
            padding: 18px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: var(--transition);
        }
        .stat-card:hover {
            transform: translate(-3px, -3px);
            box-shadow: 8px 8px 0px var(--black);
        }

        .stat-icon {
            width: 55px;
            height: 55px;
            border: 3px solid var(--black);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            flex-shrink: 0;
            box-shadow: 3px 3px 0px var(--black);
        }

        .stat-content h3 {
            font-size: 1.8rem;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 4px;
        }
        .stat-content p {
            font-size: 0.85rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #444;
        }

        /* SECTION HEADER */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 900;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* QUICK ACTION CARD */
        .action-banner {
            background: var(--white);
            border: var(--border-thick);
            box-shadow: var(--shadow-solid);
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            border-left: 12px solid var(--pink);
        }

        /* TABLE */
        .table-card {
            background: var(--white);
            border: var(--border-thick);
            box-shadow: var(--shadow-solid);
            overflow: hidden;
            margin-bottom: 30px;
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
            padding: 12px 16px;
            font-weight: 900;
            font-size: 0.85rem;
            text-transform: uppercase;
            white-space: nowrap;
        }

        td {
            padding: 12px 16px;
            border-bottom: 2px solid #ddd;
            font-weight: 700;
            font-size: 0.9rem;
            vertical-align: middle;
        }

        tr:last-child td { border-bottom: none; }
        tr:hover td { background-color: #fafafa; }

        .badge-status {
            padding: 3px 8px;
            font-weight: 900;
            font-size: 0.75rem;
            text-transform: uppercase;
            border: 2px solid var(--black);
            box-shadow: 2px 2px 0px var(--black);
            display: inline-block;
        }
        .badge-pending { background: var(--yellow); }
        .badge-approved { background: var(--green); }
        .badge-rejected { background: var(--pink); }
        .badge-unverified { background: #e0e0e0; color: #555; }

        .doc-link-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            font-weight: 800;
            font-size: 0.75rem;
            background: var(--white);
            border: 2px solid var(--black);
            box-shadow: 2px 2px 0px var(--black);
            margin: 2px;
            transition: var(--transition);
        }
        .doc-link-btn:hover {
            background: var(--yellow);
            transform: translate(-1px, -1px);
            box-shadow: 3px 3px 0px var(--black);
        }

        footer {
            background: var(--black);
            color: var(--white);
            border-top: var(--border-thick);
            padding: 15px;
            text-align: center;
            font-weight: 900;
            text-transform: uppercase;
            margin-top: auto;
        }

        @media (max-width: 600px) {
            .neo-brand { font-size: 1.2rem; }
            .hero-banner { padding: 16px; }
            .hero-title { font-size: 1.3rem; }
            .main-content { padding: 1rem 10px; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <header class="neo-navbar">
        <div class="neo-nav-left">
            <button class="menu-toggle-btn" id="open-sidebar"><i class="bi bi-list"></i></button>
            <a href="jhepp_dashboard.php" class="neo-brand">SCRS PMU (JHEPP)</a>
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
            <a href="verify_account.php" class="sidebar-link"><i class="bi bi-shield-check"></i> Pengesahan Pelajar</a>
            <a href="edit_profile.php" class="sidebar-link"><i class="bi bi-person-gear"></i> Edit Profil</a>
            <a href="logout.php" class="sidebar-link logout-link"><i class="bi bi-box-arrow-right"></i> Log Keluar</a>
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
            <a href="verify_account.php" class="neo-btn" style="background: var(--blue);">
                <i class="bi bi-person-check-fill"></i> Semak Pengesahan
                <?php if ($pending_students > 0): ?>
                    <span style="background: var(--pink); border: 2px solid #000; padding: 2px 6px; font-size: 0.75rem; border-radius: 3px;">
                        <?php echo $pending_students; ?>
                    </span>
                <?php endif; ?>
            </a>
        </div>

        <!-- STATISTIK KAD -->
        <div class="section-title" style="margin-bottom: 12px;"><i class="bi bi-bar-chart-fill me-1"></i> Ringkasan Status Permohonan Pelajar</div>
        <div class="stats-grid">
            
            <!-- 1. PENDING VERIFICATION -->
            <a href="verify_account.php?tab=pending" class="stat-card" style="cursor: pointer;">
                <div class="stat-icon" style="background: var(--yellow);">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $pending_students; ?></h3>
                    <p>Menunggu Kelulusan</p>
                </div>
            </a>

            <!-- 2. APPROVED -->
            <a href="verify_account.php?tab=approved" class="stat-card" style="cursor: pointer;">
                <div class="stat-icon" style="background: var(--green);">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $approved_students; ?></h3>
                    <p>Pelajar Diluluskan</p>
                </div>
            </a>

            <!-- 3. REJECTED -->
            <a href="verify_account.php?tab=rejected" class="stat-card" style="cursor: pointer;">
                <div class="stat-icon" style="background: var(--pink);">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $rejected_students; ?></h3>
                    <p>Pelajar Ditolak</p>
                </div>
            </a>

            <!-- 4. TOTAL REGISTERED -->
            <a href="verify_account.php?tab=all" class="stat-card" style="cursor: pointer;">
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
                <a href="verify_account.php?tab=pending" class="neo-btn" style="background: var(--green);">
                    <i class="bi bi-shield-check"></i> Buka Pengesahan Sekarang
                </a>
            </div>
        <?php endif; ?>

        <!-- TABLE: PENDAFTARAN PELAJAR TERKINI -->
        <div class="section-header">
            <div class="section-title"><i class="bi bi-clock-history me-1"></i> Pendaftaran Pelajar Terkini</div>
            <a href="verify_account.php?tab=pending" class="neo-btn" style="padding: 6px 12px; font-size: 0.8rem; background: var(--white);">
                <i class="bi bi-arrow-right-short"></i> Lihat Pengesahan Pelajar
            </a>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Tarikh Daftar</th>
                            <th>Nama Penuh</th>
                            <th>No. Pendaftaran</th>
                            <th>No. Telefon</th>
                            <th>Dokumen Sokongan</th>
                            <th>Status JHEPP</th>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_students && $recent_students->num_rows > 0): ?>
                            <?php while ($st = $recent_students->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y h:i A', strtotime($st['created_at'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($st['full_name']); ?></strong><br><small style="color: #666;"><?php echo htmlspecialchars($st['email']); ?></small></td>
                                    <td><?php echo htmlspecialchars($st['no_pendaftaran']); ?></td>
                                    <td><?php echo htmlspecialchars($st['phone_no']); ?></td>
                                    <td>
                                        <?php if (!empty($st['student_id_file'])): ?>
                                            <a href="<?php echo htmlspecialchars($st['student_id_file']); ?>" target="_blank" class="doc-link-btn">
                                                <i class="bi bi-card-heading"></i> Kad Pelajar
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($st['driving_license_file'])): ?>
                                            <a href="<?php echo htmlspecialchars($st['driving_license_file']); ?>" target="_blank" class="doc-link-btn">
                                                <i class="bi bi-card-checklist"></i> Lesen
                                            </a>
                                        <?php endif; ?>
                                        <?php if (empty($st['student_id_file']) && empty($st['driving_license_file'])): ?>
                                            <span style="color:#999; font-size:0.8rem;">Tiada fail</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($st['status'] === 'approved'): ?>
                                            <span class="badge-status badge-approved">Lulus</span>
                                        <?php elseif ($st['status'] === 'rejected'): ?>
                                            <span class="badge-status badge-rejected">Ditolak</span>
                                        <?php else: ?>
                                            <span class="badge-status badge-pending">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="verify_account.php?tab=<?php echo $st['status']; ?>" class="neo-btn" style="padding: 4px 8px; font-size: 0.75rem; background: var(--yellow);">
                                            <i class="bi bi-eye-fill"></i> Urus
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem; color: #666;">Tiada rekod pendaftaran pelajar.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
    </script>
</body>
</html>
