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

// STATISTIK SEJARAH
$sql_stats = "SELECT 
                COUNT(CASE WHEN b.status = 'Completed' THEN 1 END) as completed_count,
                COUNT(CASE WHEN b.status = 'Rejected' THEN 1 END) as rejected_count,
                SUM(CASE WHEN b.status = 'Completed' THEN b.total_price ELSE 0 END) as total_earnings
              FROM bookings b
              JOIN cars c ON b.car_id = c.id
              WHERE c.provider_id = ?";
$stmt_s = $conn->prepare($sql_stats);
$stmt_s->bind_param("i", $provider_id);
$stmt_s->execute();
$stats = $stmt_s->get_result()->fetch_assoc();
$completed_count = $stats['completed_count'] ?? 0;
$rejected_count = $stats['rejected_count'] ?? 0;
$total_earnings = $stats['total_earnings'] ?? 0;
$stmt_s->close();

// AMBIL SENARAI SEJARAH TEMPAHAN
$sql_history = "SELECT b.*, c.car_model, c.car_plate, c.car_image,
                       s.username as student_username, s.full_name as student_name, s.email as student_email,
                       s.phone_no as student_phone, s.no_pendaftaran as student_matrix,
                       s.student_id_file as student_id_file, s.driving_license_file as student_license_file,
                       s.profile_picture as student_profile_pic
                FROM bookings b
                JOIN cars c ON b.car_id = c.id
                JOIN students s ON b.student_id = s.id
                WHERE c.provider_id = ? AND b.status IN ('Completed', 'Rejected')
                ORDER BY b.created_at DESC";

$stmt_h = $conn->prepare($sql_history);
$stmt_h->bind_param("i", $provider_id);
$stmt_h->execute();
$result_history = $stmt_h->get_result();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sejarah Rekod Tempahan - SCRS PMU</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700;900&display=swap" rel="stylesheet">

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
        ul { list-style: none; }
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
        .menu-toggle-btn { font-size: 2rem; color: var(--black); transition: var(--transition); border: none; background: none; cursor: pointer; }
        .menu-toggle-btn:hover { transform: scale(1.1); }
        .neo-brand { font-size: 1.5rem; font-weight: 900; letter-spacing: 2px; text-transform: uppercase; }

        /* Dropdown Profil */
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
            cursor: pointer;
            transition: var(--transition);
        }
        .profile-btn:hover { transform: translate(-2px, -2px); box-shadow: var(--shadow-solid); }

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
            text-decoration: none;
        }
        .dropdown-item:last-child { border-bottom: none; background-color: var(--pink); }
        .dropdown-item:hover { background-color: var(--yellow); }
        .dropdown-item:last-child:hover { background-color: #ff33aa; }

        /* SIDEBAR */
        .sidebar-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 1005; display: none; opacity: 0; transition: opacity 0.3s;
        }
        .sidebar-overlay.show { display: block; opacity: 1; }

        .sidebar {
            position: fixed; top: 0; left: -300px; width: 280px; height: 100%;
            background-color: var(--bg-color); border-right: var(--border-thick);
            z-index: 1010; transition: left 0.3s ease; display: flex; flex-direction: column;
        }
        .sidebar.open { left: 0; }
        
        .sidebar-header {
            padding: 20px; background-color: var(--yellow); border-bottom: var(--border-thick);
            display: flex; justify-content: space-between; align-items: center;
        }
        .sidebar-header h2 { font-weight: 900; text-transform: uppercase; font-size: 1.2rem; }
        .close-btn { border: 3px solid var(--black); background: var(--white); padding: 5px 10px; font-weight: 900; box-shadow: 2px 2px 0px var(--black); cursor: pointer; }

        .sidebar-nav { padding: 20px; display: flex; flex-direction: column; gap: 10px; }
        .sidebar-link {
            padding: 12px 15px; border: 3px solid transparent; font-weight: 800;
            text-transform: uppercase; display: flex; align-items: center; gap: 15px; transition: var(--transition);
        }
        .sidebar-link.active, .sidebar-link:hover { border: 3px solid var(--black); background: var(--white); transform: translate(-2px, -2px); box-shadow: 4px 4px 0px var(--black); }

        /* KANDUNGAN UTAMA */
        .main-content { flex: 1; padding: 2rem 20px; max-width: 1200px; margin: 0 auto; width: 100%; }

        .neo-btn {
            background-color: var(--yellow); border: 3px solid var(--black); box-shadow: 4px 4px 0px var(--black);
            font-weight: 900; text-transform: uppercase; padding: 10px 18px; cursor: pointer; transition: var(--transition);
            display: inline-flex; align-items: center; gap: 8px; justify-content: center;
        }
        .neo-btn:hover { transform: translate(-2px, -2px); box-shadow: 6px 6px 0px var(--black); }
        .neo-btn:active { transform: translate(2px, 2px); box-shadow: var(--shadow-active); }
        .btn-green { background-color: var(--green); }
        .btn-blue { background-color: var(--blue); }
        .btn-pink { background-color: var(--pink); }

        /* KAD STATISTIK (BUKAN BUTTON - MAKLUMAT WIDGET) */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat-card {
            border: var(--border-thick);
            box-shadow: 4px 4px 0px var(--black);
            padding: 16px 18px;
            text-align: left;
            display: flex;
            flex-direction: column;
            justify-content: center;
            cursor: default;
            user-select: none;
        }
        .stat-card h3 { font-size: 2.2rem; font-weight: 900; margin-bottom: 4px; line-height: 1; }
        .stat-card p { font-weight: 800; font-size: 0.85rem; text-transform: uppercase; margin: 0; color: #222; }

        /* JADUAL NEO-BRUTALISM */
        .neo-table-card {
            background: var(--white);
            border: var(--border-thick);
            box-shadow: var(--shadow-solid);
            padding: 20px;
            overflow-x: auto;
            margin-bottom: 30px;
        }

        table.neo-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            min-width: 700px;
        }

        table.neo-table th {
            background-color: var(--yellow);
            border: 2px solid var(--black);
            padding: 12px 14px;
            font-weight: 900;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        table.neo-table td {
            border: 2px solid var(--black);
            padding: 12px 14px;
            font-weight: 700;
            font-size: 0.85rem;
            vertical-align: middle;
        }

        table.neo-table tr:nth-child(even) {
            background-color: #fafaf5;
        }

        .neo-badge {
            border: 2px solid var(--black);
            padding: 3px 8px;
            font-weight: 900;
            text-transform: uppercase;
            font-size: 0.75rem;
            border-radius: 4px;
            display: inline-block;
            box-shadow: none;
        }
        .badge-completed { background-color: var(--blue); }
        .badge-rejected { background-color: var(--pink); }

        .empty-box {
            background: var(--white);
            border: var(--border-thick);
            box-shadow: var(--shadow-solid);
            padding: 50px 20px;
            text-align: center;
        }
        .empty-box i { font-size: 4rem; display: block; margin-bottom: 15px; }

        /* Modal Popup */
        .neo-modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6); z-index: 2000; display: none; align-items: center; justify-content: center; padding: 15px;
        }
        .neo-modal-overlay.show { display: flex; }
        .neo-modal {
            background: var(--white); border: var(--border-thick); box-shadow: 10px 10px 0px var(--black);
            width: 100%; max-width: 480px; padding: 25px; position: relative; max-height: 90vh; overflow-y: auto;
        }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid var(--black); padding-bottom: 10px; margin-bottom: 15px; }

        /* FOOTER */
        footer {
            background-color: var(--yellow);
            border-top: var(--border-thick);
            padding: 20px;
            text-align: center;
            font-weight: 900;
            text-transform: uppercase;
            margin-top: auto;
        }

        /* RESPONSIVE MOBILE */
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr; }
            .main-content { padding: 1rem 10px; }
            .neo-brand { font-size: 1.2rem; }
            .profile-btn { padding: 6px 10px; font-size: 0.85rem; }
            .header-flex { flex-direction: column; align-items: stretch !important; gap: 10px !important; }
            .mobile-btn-full { width: 100% !important; justify-content: center !important; text-align: center; }
            .neo-table-card { padding: 12px; }
            table.neo-table th, table.neo-table td { padding: 8px 10px; font-size: 0.8rem; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <header class="neo-navbar">
        <div class="neo-nav-left">
            <button class="menu-toggle-btn" id="open-sidebar"><i class="bi bi-list"></i></button>
            <a href="provider_dashboard.php" class="neo-brand">SCRS PMU (PROVIDER)</a>
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
            <h2>Menu Penyedia</h2>
            <button class="close-btn" id="close-sidebar"><i class="bi bi-x-lg"></i></button>
        </div>
        <nav class="sidebar-nav">
            <a href="provider_dashboard.php" class="sidebar-link"><i class="bi bi-speedometer2"></i> Papan Pemuka</a>
            <a href="provider_cars.php" class="sidebar-link"><i class="bi bi-car-front-fill"></i> Senarai Kereta</a>
            <a href="provider_bookings.php" class="sidebar-link"><i class="bi bi-clipboard-check-fill"></i> Tempahan Semasa</a>
            <a href="provider_history.php" class="sidebar-link active"><i class="bi bi-clock-history"></i> Sejarah Rekod</a>
        </nav>
    </aside>

    <!-- KANDUNGAN UTAMA -->
    <main class="main-content">
        
        <!-- HEADING PANDUAN PENGGUNA (TANPA KOTAK) -->
        <div style="margin-bottom: 25px;">
            <div class="header-flex" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 6px;">
                <h1 style="font-size: 1.6rem; font-weight: 900; text-transform: uppercase; margin: 0; color: var(--black); display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-clock-history text-dark"></i> Sejarah Rekod Tempahan
                </h1>
                <a href="provider_bookings.php" class="neo-btn mobile-btn-full" style="background: var(--green); padding: 8px 16px; font-size: 0.85rem;">
                    <i class="bi bi-clipboard-check-fill me-1"></i> Tempahan Semasa
                </a>
            </div>
            <p style="font-weight: 700; color: #555; font-size: 0.95rem; margin: 0; line-height: 1.5;">
                Senarai arkib keseluruhan transaksi tempahan yang telah selesai atau ditolak bagi kenderaan milik anda.
            </p>
        </div>

        <!-- STATISTIK RINGKAS -->
        <div class="stats-grid">
            <div class="stat-card" style="background: var(--blue);">
                <h3><?php echo $completed_count; ?></h3>
                <p>Tempahan Selesai</p>
            </div>
            <div class="stat-card" style="background: var(--green);">
                <h3>RM <?php echo number_format($total_earnings, 2); ?></h3>
                <p>Jumlah Pendapatan</p>
            </div>
            <div class="stat-card" style="background: var(--pink);">
                <h3><?php echo $rejected_count; ?></h3>
                <p>Permohonan Ditolak</p>
            </div>
        </div>

        <!-- JADUAL SEJARAH -->
        <div class="neo-table-card">
            <?php if ($result_history->num_rows > 0): ?>
                <table class="neo-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Pelajar (Penyewa)</th>
                            <th>Kereta</th>
                            <th>Tarikh Ambil</th>
                            <th>Tarikh Pulang</th>
                            <th>Jenis</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                            <th>Dokumen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 1;
                        while ($row = $result_history->fetch_assoc()): 
                            $is_comp = ($row['status'] === 'Completed');
                            $badge_style = $is_comp ? 'badge-completed' : 'badge-rejected';
                            $status_label = $is_comp ? 'Selesai' : 'Ditolak';
                        ?>
                            <tr>
                                <td style="text-align: center;"><?php echo $counter++; ?></td>
                                <td>
                                    <a href="javascript:void(0)" onclick="showStudentModal('<?php echo htmlspecialchars($row['student_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['student_username'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['student_email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['student_phone'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['student_matrix'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['student_id_file'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['student_license_file'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['student_profile_pic'] ?? '', ENT_QUOTES); ?>')" style="color: #0055ff; font-weight: 800; text-decoration: underline;">
                                        <?php echo htmlspecialchars($row['student_name']); ?> <i class="bi bi-info-circle ms-1"></i>
                                    </a>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['car_model']); ?></strong><br>
                                    <small style="color: #666; font-weight: 700;"><?php echo htmlspecialchars($row['car_plate']); ?></small>
                                </td>
                                <td><?php echo date('d M Y, h:i A', strtotime($row['start_date'])); ?></td>
                                <td><?php echo date('d M Y, h:i A', strtotime($row['end_date'])); ?></td>
                                <td><?php echo ($row['rent_type'] === 'Daily') ? 'Harian' : 'Jam'; ?></td>
                                <td style="color: #008800; font-weight: 900;">RM <?php echo number_format($row['total_price'], 2); ?></td>
                                <td>
                                    <span class="neo-badge <?php echo $badge_style; ?>">
                                        <?php echo $status_label; ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 4px; flex-direction: column;">
                                        <?php if (!empty($row['payment_receipt']) && file_exists($row['payment_receipt'])): ?>
                                            <a href="<?php echo htmlspecialchars($row['payment_receipt']); ?>" target="_blank" style="font-size: 0.75rem; color: #0055ff; text-decoration: underline; font-weight: 800;">
                                                <i class="bi bi-receipt me-1"></i>Resit Bayaran
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($row['return_image']) && file_exists($row['return_image'])): ?>
                                            <a href="<?php echo htmlspecialchars($row['return_image']); ?>" target="_blank" style="font-size: 0.75rem; color: #2e7d32; text-decoration: underline; font-weight: 800;">
                                                <i class="bi bi-camera me-1"></i>Gambar Pulang
                                            </a>
                                        <?php endif; ?>
                                        <?php if (empty($row['payment_receipt']) && empty($row['return_image'])): ?>
                                            <span style="color: #999; font-size: 0.75rem;">-</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-box" style="box-shadow: none; border: none; padding: 40px 10px;">
                    <i class="bi bi-clock-history"></i>
                    <h2 style="font-weight: 900; text-transform: uppercase;">Tiada Sejarah Rekod</h2>
                    <p style="font-weight: 700; color: #666; margin: 10px 0 0 0;">Belum ada rekod tempahan yang telah selesai atau ditolak.</p>
                </div>
            <?php endif; ?>
        </div>

    </main>

    <!-- MODAL MAKLUMAT PELAJAR (POPUP) -->
    <div class="neo-modal-overlay" id="studentModalOverlay" onclick="closeStudentModalOutside(event)">
        <div class="neo-modal" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="modal-title"><i class="bi bi-mortarboard-fill me-1"></i> Maklumat Pelajar (Penyewa)</h3>
            </div>
            <div class="modal-body" style="font-weight: 700; font-size: 0.95rem;">
                <div style="text-align: center; margin-bottom: 20px;">
                    <img id="modalStudentImg" src="" alt="Gambar Pelajar" style="width: 100px; height: 100px; border-radius: 50%; border: 3px solid var(--black); box-shadow: 4px 4px 0px var(--black); object-fit: cover;">
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 8px 0;">
                    <span style="color: #666;">Nama Penuh:</span>
                    <span id="modalStudentName" style="color: var(--black); font-weight: 800;"></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 8px 0;">
                    <span style="color: #666;">No. Matrik:</span>
                    <span id="modalStudentMatrix" style="color: var(--black); font-weight: 800;"></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 8px 0;">
                    <span style="color: #666;">Email:</span>
                    <span id="modalStudentEmail" style="color: var(--black); font-weight: 800;"></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 8px 0;">
                    <span style="color: #666;">No. Telefon:</span>
                    <span id="modalStudentPhone" style="color: var(--black); font-weight: 800;"></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 8px 0; align-items: center;">
                    <span style="color: #666;">Kad Pelajar:</span>
                    <span>
                        <a id="modalStudentIdDoc" href="" target="_blank" class="neo-badge" style="display: inline-block; cursor: pointer; text-decoration: none; border: 2px solid var(--black); padding: 2px 6px; font-size: 0.75rem; background: var(--yellow); font-weight: 800;"><i class="bi bi-file-earmark-image me-1"></i>Lihat Dokumen</a>
                        <span id="modalStudentNoIdDoc" style="color: #999; display: none;">Tiada Fail</span>
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 8px 0; align-items: center;">
                    <span style="color: #666;">Lesen Memandu:</span>
                    <span>
                        <a id="modalStudentLicenseDoc" href="" target="_blank" class="neo-badge" style="display: inline-block; cursor: pointer; text-decoration: none; border: 2px solid var(--black); padding: 2px 6px; font-size: 0.75rem; background: var(--green); font-weight: 800;"><i class="bi bi-file-earmark-image me-1"></i>Lihat Lesen</a>
                        <span id="modalStudentNoLicenseDoc" style="color: #999; display: none;">Tiada Fail</span>
                    </span>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <button class="neo-btn bg-p" style="width: 100%; justify-content: center;" onclick="closeStudentModal()"><i class="bi bi-arrow-left-short me-1"></i>Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        &copy; <?php echo date("Y"); ?> SCRS PMU. SISTEM SEWAAN KERETA.
    </footer>

    <!-- SKRIP ASLI -->
    <script>
        // Dropdown Profil
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

        // Sidebar
        const openSidebarBtn = document.getElementById('open-sidebar');
        const closeSidebarBtn = document.getElementById('close-sidebar');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');

        function openSidebar() { sidebar.classList.add('open'); sidebarOverlay.classList.add('show'); }
        function closeSidebar() { sidebar.classList.remove('open'); sidebarOverlay.classList.remove('show'); }

        if (openSidebarBtn) openSidebarBtn.addEventListener('click', openSidebar);
        if (closeSidebarBtn) closeSidebarBtn.addEventListener('click', closeSidebar);
        if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

        // POPUP STUDENT MODAL CONTROL
        function showStudentModal(name, username, email, phone, matrix, idDoc, licenseDoc, profilePic) {
            document.getElementById('modalStudentName').textContent = name;
            document.getElementById('modalStudentMatrix').textContent = matrix;
            document.getElementById('modalStudentEmail').textContent = email;
            document.getElementById('modalStudentPhone').textContent = phone;
            
            const imgElem = document.getElementById('modalStudentImg');
            if (profilePic && profilePic.trim() !== '') {
                imgElem.src = profilePic;
            } else {
                imgElem.src = 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
            }
            
            const idElem = document.getElementById('modalStudentIdDoc');
            const noIdElem = document.getElementById('modalStudentNoIdDoc');
            if (idDoc && idDoc.trim() !== '') {
                idElem.href = idDoc;
                idElem.style.display = 'inline-block';
                noIdElem.style.display = 'none';
            } else {
                idElem.style.display = 'none';
                noIdElem.style.display = 'inline-block';
            }
            
            const licElem = document.getElementById('modalStudentLicenseDoc');
            const noLicElem = document.getElementById('modalStudentNoLicenseDoc');
            if (licenseDoc && licenseDoc.trim() !== '') {
                licElem.href = licenseDoc;
                licElem.style.display = 'inline-block';
                noLicElem.style.display = 'none';
            } else {
                licElem.style.display = 'none';
                noLicElem.style.display = 'inline-block';
            }

            document.getElementById('studentModalOverlay').classList.add('show');
        }

        function closeStudentModal() {
            document.getElementById('studentModalOverlay').classList.remove('show');
        }

        function closeStudentModalOutside(e) {
            if (e.target.id === 'studentModalOverlay') {
                closeStudentModal();
            }
        }
    </script>
</body>
</html>
