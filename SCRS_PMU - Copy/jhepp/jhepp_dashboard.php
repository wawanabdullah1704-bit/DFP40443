<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/notifications.php';

// Semak jika pengguna telah log masuk dan merupakan pegawai JHEPP
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'jhepp') {
    header("Location: ../index.php");
    exit();
}

$jhepp_id = $_SESSION['jhepp_id'] ?? 0;
$jhepp_username = $_SESSION['username'] ?? 'JHEPP';
$jhepp_fullname = $_SESSION['full_name'] ?? $jhepp_username;

// STATISTIK JHEPP
// 1. Permohonan Pelajar Pending (E-mel Telah Disahkan - Menunggu Tindakan JHEPP)
$res_pending = $conn->query("SELECT COUNT(*) AS total FROM students WHERE status = 'pending' AND email_verified = 1");
$pending_students = $res_pending ? (int)($res_pending->fetch_assoc()['total'] ?? 0) : 0;

// 2. Pelajar Belum Sahkan E-mel
$res_unverified = $conn->query("SELECT COUNT(*) AS total FROM students WHERE status = 'pending' AND email_verified = 0");
$unverified_email_students = $res_unverified ? (int)($res_unverified->fetch_assoc()['total'] ?? 0) : 0;

// 3. Pelajar Diluluskan
$res_approved = $conn->query("SELECT COUNT(*) AS total FROM students WHERE status = 'approved'");
$approved_students = $res_approved ? (int)($res_approved->fetch_assoc()['total'] ?? 0) : 0;

// 4. Pelajar Ditolak
$res_rejected = $conn->query("SELECT COUNT(*) AS total FROM students WHERE status = 'rejected'");
$rejected_students = $res_rejected ? (int)($res_rejected->fetch_assoc()['total'] ?? 0) : 0;

// 5. Jumlah Keseluruhan Pelajar
$res_total = $conn->query("SELECT COUNT(*) AS total FROM students");
$total_students = $res_total ? (int)($res_total->fetch_assoc()['total'] ?? 0) : 0;

// 6. Rekod Pendaftaran Pelajar Terkini (5 Pelajar Terbaru)
$sql_recent = "SELECT id, username, full_name, email, phone_no, no_ic, no_pendaftaran, status, email_verified, created_at 
               FROM students 
               ORDER BY created_at DESC LIMIT 5";
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
    <!-- Master Neo-Brutalism CSS -->
    <link rel="stylesheet" href="../assets/css/neo-style.css">

    <style>
        .main-content {
            flex: 1;
            padding: 2rem 24px;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        /* PAGE HEADER (TAJUK HALAMAN BERSIH, PROFESIONAL & BUKAN BUTTON) */
        .page-header {
            margin-bottom: 24px;
            border-bottom: 2.5px solid var(--black);
            padding-bottom: 16px;
        }

        .page-header-title {
            font-size: 1.65rem;
            font-weight: 900;
            text-transform: uppercase;
            margin: 0 0 6px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--black);
            letter-spacing: -0.5px;
        }

        .page-header-desc {
            font-size: 0.95rem;
            font-weight: 700;
            color: #555;
            line-height: 1.5;
            margin: 0;
        }

        /* SECTION HEADER */
        .section-header-title {
            font-size: 1.15rem;
            font-weight: 900;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0 0 14px 0;
            color: var(--black);
        }

        /* KPI SUMMARY PANEL (SATU JALUR STATISTIK BERSATU - BUKAN KAD/BUTTON TERPISAH) */
        .kpi-summary-panel {
            background-color: var(--white);
            border: 2px solid var(--black);
            border-radius: var(--radius-md);
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            margin-bottom: 26px;
            overflow: hidden;
            box-shadow: none;
        }

        .kpi-item {
            padding: 18px 20px;
            border-right: 1.5px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            cursor: default;
            user-select: none;
            position: relative;
        }
        .kpi-item:last-child {
            border-right: none;
        }

        /* Garis aksen warna atas yang halus */
        .kpi-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        .kpi-item.kpi-yellow::before { background-color: var(--yellow); }
        .kpi-item.kpi-green::before  { background-color: var(--green); }
        .kpi-item.kpi-pink::before   { background-color: var(--pink); }
        .kpi-item.kpi-blue::before   { background-color: var(--blue); }

        .kpi-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .kpi-label {
            font-size: 0.8rem;
            font-weight: 900;
            text-transform: uppercase;
            color: #555;
            letter-spacing: 0.5px;
        }

        .kpi-icon {
            font-size: 1.1rem;
        }

        .kpi-number {
            font-size: 2.3rem;
            font-weight: 900;
            line-height: 1;
            color: var(--black);
            margin-bottom: 6px;
        }

        .kpi-sub {
            font-size: 0.775rem;
            font-weight: 700;
            color: #666;
            margin: 0;
        }

        /* MODUL TINDAKAN PANTAS (SATU-SATUNYA TEMPAT PINTASAN NAVIGASI) */
        .mgmt-shortcuts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }

        .shortcut-card {
            background-color: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-solid);
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--black);
            transition: var(--transition);
        }
        .shortcut-card:hover {
            transform: translate(-2px, -2px);
            box-shadow: var(--shadow-lg);
            background-color: #fafafa;
        }
        .shortcut-card:active {
            transform: translate(2px, 2px);
            box-shadow: var(--shadow-active);
        }

        .shortcut-icon-box {
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

        .shortcut-text-title {
            font-weight: 900;
            font-size: 0.88rem;
            text-transform: uppercase;
            color: var(--black);
            line-height: 1.2;
            margin-bottom: 3px;
        }
        .shortcut-text-sub {
            font-size: 0.75rem;
            color: #666;
            font-weight: 700;
        }

        /* DATA SECTION BOX & TABLE */
        .section-box {
            background: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-solid);
            padding: 20px;
            margin-bottom: 24px;
        }

        .section-box-header {
            border-bottom: 2.5px solid var(--black);
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table.neo-data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: var(--border-thin);
            border-radius: var(--radius-md);
            overflow: hidden;
            text-align: left;
            font-size: 0.85rem;
        }

        table.neo-data-table th {
            background: var(--yellow);
            color: var(--black);
            padding: 11px 14px;
            border-bottom: var(--border-thin);
            border-right: var(--border-thin);
            font-weight: 900;
            text-transform: uppercase;
            font-size: 0.78rem;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        table.neo-data-table th:last-child { border-right: none; }

        table.neo-data-table td {
            padding: 11px 14px;
            border-bottom: var(--border-thin);
            border-right: var(--border-thin);
            vertical-align: middle;
            font-weight: 700;
        }
        table.neo-data-table td:last-child { border-right: none; }
        table.neo-data-table tr:last-child td { border-bottom: none; }
        table.neo-data-table tbody tr:hover { background-color: #fdfae6; }

        /* GARIS PANDUAN SENARAI BERSIH */
        .guidelines-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .guideline-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 14px;
            background: #fafafa;
            border: 1.5px solid var(--black);
            border-radius: var(--radius-sm);
        }

        .guideline-dot {
            font-size: 1.1rem;
            color: var(--black);
            line-height: 1;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .guideline-content h4 {
            font-size: 0.9rem;
            font-weight: 900;
            text-transform: uppercase;
            margin: 0 0 3px 0;
            color: var(--black);
        }

        .guideline-content p {
            font-size: 0.84rem;
            font-weight: 700;
            color: #555;
            line-height: 1.45;
            margin: 0;
        }

        @media (max-width: 900px) {
            .kpi-summary-panel { grid-template-columns: repeat(2, 1fr); }
            .kpi-item:nth-child(2) { border-right: none; }
            .kpi-item:nth-child(-n+2) { border-bottom: 1.5px solid #e5e7eb; }
        }

        @media (max-width: 768px) {
            .main-content { padding: 1.25rem 12px; }
            .page-header-title { font-size: 1.35rem; }
            .mgmt-shortcuts { grid-template-columns: 1fr; }
        }

        @media (max-width: 520px) {
            .kpi-summary-panel { grid-template-columns: 1fr; }
            .kpi-item { border-right: none; border-bottom: 1.5px solid #e5e7eb; }
            .kpi-item:last-child { border-bottom: none; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <header class="neo-navbar">
        <div class="neo-nav-left">
            <button class="menu-toggle-btn" id="open-sidebar" aria-label="Buka Menu"><i class="bi bi-list"></i></button>
            <a href="jhepp_dashboard.php" class="neo-brand">SCRS PMU</a>
        </div>
        <?php render_navbar_actions($conn, 'jhepp', $jhepp_id, $jhepp_username, '../'); ?>
    </header>

    <!-- SIDEBAR -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Portal JHEPP</h2>
            <button class="close-btn" id="close-sidebar" aria-label="Tutup Menu"><i class="bi bi-x-lg"></i></button>
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
        
        <!-- PAGE HEADER (TAJUK HALAMAN RASMI - BUKAN BUTTON) -->
        <div class="page-header">
            <h1 class="page-header-title">
                <i class="bi bi-shield-check text-primary"></i> Portal Pegawai JHEPP PMU
            </h1>
            <p class="page-header-desc">
                Selamat bertugas, <strong><?php echo htmlspecialchars($jhepp_fullname); ?></strong>. Semak dan sahkan dokumen permohonan akaun pelajar Politeknik Mukah.
            </p>
        </div>

        <!-- PERINGATAN RINGKAS SEKIRANYA ADA PERMOHONAN MENUNGGU -->
        <?php if ($pending_students > 0): ?>
            <div class="neo-alert alert-warning" style="margin-bottom: 20px;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <span><strong>Perhatian:</strong> Terdapat <strong><?php echo $pending_students; ?></strong> permohonan pelajar sedang menunggu kelulusan dokumen daripada pihak JHEPP.</span>
            </div>
        <?php endif; ?>

        <!-- RINGKASAN STATUS PERMOHONAN PELAJAR (PANEL STATISTIK KPI BERSATU) -->
        <h2 class="section-header-title">
            <i class="bi bi-bar-chart-fill text-primary"></i> Ringkasan Status Permohonan Pelajar
        </h2>

        <div class="kpi-summary-panel">
            <!-- 1. PENDING -->
            <div class="kpi-item kpi-yellow">
                <div class="kpi-header">
                    <i class="bi bi-hourglass-split kpi-icon" style="color: #ca8a04;"></i>
                    <span class="kpi-label">Menunggu Kelulusan</span>
                </div>
                <div class="kpi-number"><?php echo $pending_students; ?></div>
                <p class="kpi-sub">
                    <?php echo ($pending_students > 0) ? '<span style="color: #b45309; font-weight:800;">Perlu tindakan semakan</span>' : 'Tiada permohonan tertunggak'; ?>
                </p>
            </div>

            <!-- 2. APPROVED -->
            <div class="kpi-item kpi-green">
                <div class="kpi-header">
                    <i class="bi bi-check-circle-fill kpi-icon" style="color: #16a34a;"></i>
                    <span class="kpi-label">Pelajar Diluluskan</span>
                </div>
                <div class="kpi-number"><?php echo $approved_students; ?></div>
                <p class="kpi-sub">Akaun aktif &amp; layak menyewa</p>
            </div>

            <!-- 3. REJECTED -->
            <div class="kpi-item kpi-pink">
                <div class="kpi-header">
                    <i class="bi bi-x-circle-fill kpi-icon" style="color: #e11d48;"></i>
                    <span class="kpi-label">Pelajar Ditolak</span>
                </div>
                <div class="kpi-number"><?php echo $rejected_students; ?></div>
                <p class="kpi-sub">Dokumen tidak lengkap / ditolak</p>
            </div>

            <!-- 4. TOTAL -->
            <div class="kpi-item kpi-blue">
                <div class="kpi-header">
                    <i class="bi bi-people-fill kpi-icon" style="color: #2563eb;"></i>
                    <span class="kpi-label">Jumlah Pelajar</span>
                </div>
                <div class="kpi-number"><?php echo $total_students; ?></div>
                <p class="kpi-sub">Keseluruhan rekod pelajar</p>
            </div>
        </div>

        <!-- MODUL PENGURUSAN JHEPP (SATU-SATUNYA BLOK BUTTON NAVIGASI UTAMA) -->
        <h2 class="section-header-title">
            <i class="bi bi-grid-fill text-primary"></i> Modul Pengesahan & Pengurusan
        </h2>

        <div class="mgmt-shortcuts">
            <a href="jhepp_pending.php" class="shortcut-card">
                <div class="shortcut-icon-box" style="background: var(--yellow);">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <div class="shortcut-text-title">Menunggu Kelulusan</div>
                    <span class="shortcut-text-sub"><?php echo $pending_students; ?> permohonan semakan</span>
                </div>
            </a>

            <a href="jhepp_approved.php" class="shortcut-card">
                <div class="shortcut-icon-box" style="background: var(--green);">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <div class="shortcut-text-title">Pelajar Diluluskan</div>
                    <span class="shortcut-text-sub"><?php echo $approved_students; ?> pelajar disahkan</span>
                </div>
            </a>

            <a href="jhepp_rejected.php" class="shortcut-card">
                <div class="shortcut-icon-box" style="background: var(--pink);">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div>
                    <div class="shortcut-text-title">Pendaftaran Ditolak</div>
                    <span class="shortcut-text-sub"><?php echo $rejected_students; ?> rekod ditolak</span>
                </div>
            </a>

            <a href="senarai_pendaftaran.php" class="shortcut-card">
                <div class="shortcut-icon-box" style="background: var(--blue);">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <div class="shortcut-text-title">Semua Rekod Pelajar</div>
                    <span class="shortcut-text-sub">Carian & maklumat lengkap</span>
                </div>
            </a>
        </div>

        <!-- JADUAL PENDAFTARAN TERKINI (MAKLUMAT SAHAJA) -->
        <div class="section-box">
            <div class="section-box-header">
                <h3 style="font-size: 1.1rem; font-weight: 900; text-transform: uppercase; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-clock-history text-primary"></i> Pendaftaran Pelajar Terkini
                </h3>
            </div>

            <div class="table-responsive">
                <table class="neo-data-table">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">Bil</th>
                            <th>Tarikh Daftar</th>
                            <th>Nama & E-mel</th>
                            <th>No. Matrik & IC</th>
                            <th>No. Telefon</th>
                            <th>Status Pengesahan</th>
                            <th style="text-align: center; width: 100px;">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_students && $recent_students->num_rows > 0): ?>
                            <?php $bil = 1; while ($row = $recent_students->fetch_assoc()): ?>
                                <tr>
                                    <td style="text-align: center;"><?php echo $bil++; ?></td>
                                    <td>
                                        <div><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></div>
                                        <div style="font-size: 0.75rem; color: #666; font-weight: 700;"><?php echo date('h:i A', strtotime($row['created_at'])); ?></div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 900; color: var(--black);"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                        <div style="font-size: 0.78rem; color: #555;"><?php echo htmlspecialchars($row['email']); ?></div>
                                    </td>
                                    <td>
                                        <div style="font-family: monospace; font-weight: 900;"><?php echo htmlspecialchars($row['no_pendaftaran']); ?></div>
                                        <div style="font-size: 0.75rem; color: #666;">IC: <?php echo htmlspecialchars($row['no_ic']); ?></div>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($row['phone_no']); ?></div>
                                    </td>
                                    <td>
                                        <?php if ($row['status'] === 'approved'): ?>
                                            <span style="color: #15803d; font-weight: 900;"><i class="bi bi-check-circle-fill"></i> Diluluskan</span>
                                        <?php elseif ($row['status'] === 'rejected'): ?>
                                            <span style="color: #dc2626; font-weight: 900;"><i class="bi bi-x-circle-fill"></i> Ditolak</span>
                                        <?php else: ?>
                                            <span style="color: #d97706; font-weight: 900;"><i class="bi bi-hourglass-split"></i> Menunggu Semakan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php if ($row['status'] === 'pending'): ?>
                                            <a href="jhepp_pending.php" class="neo-btn btn-sm btn-yellow" style="padding: 4px 10px; font-size: 0.75rem;">
                                                Semak
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #888; font-size: 0.8rem; font-weight: 700;">Selesai</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 20px; color: #777;">
                                    Belum ada rekod pendaftaran pelajar dalam sistem.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- GARIS PANDUAN PENGESAHAN DOKUMEN JHEPP (FORMAT SENARAI BERSIH) -->
        <div class="section-box">
            <div class="section-box-header">
                <h3 style="font-size: 1.1rem; font-weight: 900; text-transform: uppercase; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-info-circle-fill text-primary"></i> Garis Panduan Pengesahan Dokumen JHEPP
                </h3>
            </div>
            
            <div class="guidelines-list">
                <div class="guideline-row">
                    <i class="bi bi-check2-square guideline-dot text-primary"></i>
                    <div class="guideline-content">
                        <h4>1. Semakan Kad Pelajar PMU</h4>
                        <p>Pastikan nombor pendaftaran (matrik) dan nama pelajar sepadan dengan rekod rasmi Politeknik Mukah serta semester pengajian masih aktif.</p>
                    </div>
                </div>

                <div class="guideline-row">
                    <i class="bi bi-check2-square guideline-dot text-primary"></i>
                    <div class="guideline-content">
                        <h4>2. Pengesahan Lesen Memandu</h4>
                        <p>Pastikan lesen memandu adalah Kelas D / DA (CDL atau P) yang sah untuk memandu kereta dan tarikh luput lesen belum tamat tempoh.</p>
                    </div>
                </div>

                <div class="guideline-row">
                    <i class="bi bi-check2-square guideline-dot text-primary"></i>
                    <div class="guideline-content">
                        <h4>3. Pengesahan Alamat E-mel</h4>
                        <p>Pelajar perlu mengesahkan pautan dalam e-mel pendaftaran terlebih dahulu sebelum dokumen mereka dibuka untuk semakan pegawai JHEPP.</p>
                    </div>
                </div>
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

        function openSidebar() {
            if (sidebar) sidebar.classList.add('open');
            if (sidebarOverlay) sidebarOverlay.classList.add('show');
        }

        function closeSidebar() {
            if (sidebar) sidebar.classList.remove('open');
            if (sidebarOverlay) sidebarOverlay.classList.remove('show');
        }

        if (openSidebarBtn) openSidebarBtn.addEventListener('click', openSidebar);
        if (closeSidebarBtn) closeSidebarBtn.addEventListener('click', closeSidebar);
        if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

        // Cegah paparan semula melalui butang Back selepas log keluar
        window.addEventListener('pageshow', function(event) {
            if (event.persisted || (window.performance && window.performance.navigation && window.performance.navigation.type === 2)) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>
