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

// STATISTIK SEJARAH PELAJAR
$sql_stats = "SELECT 
                COUNT(*) as total_count,
                COUNT(CASE WHEN status = 'Completed' THEN 1 END) as completed_count,
                SUM(CASE WHEN status = 'Completed' THEN total_price ELSE 0 END) as total_spent
              FROM bookings 
              WHERE student_id = ?";
$stmt_s = $conn->prepare($sql_stats);
$stmt_s->bind_param("i", $student_id);
$stmt_s->execute();
$stats = $stmt_s->get_result()->fetch_assoc();
$total_count = $stats['total_count'] ?? 0;
$completed_count = $stats['completed_count'] ?? 0;
$total_spent = $stats['total_spent'] ?? 0;
$stmt_s->close();

// Ambil SEMUA rekod tempahan pelajar beserta maklumat kereta & penyedia
$sql_history = "SELECT b.*, c.car_model, c.car_plate, c.car_image, 
                       p.username AS provider_username, p.email AS provider_email, p.phone_no AS provider_phone, 
                       p.roadtax_file AS provider_roadtax, p.insurance_file AS provider_insurance, 
                       p.profile_picture AS provider_profile_picture, p.qr_code_image AS provider_qr_code,
                       p.full_name AS provider_name 
                FROM bookings b
                JOIN cars c ON b.car_id = c.id
                JOIN providers p ON c.provider_id = p.id
                WHERE b.student_id = ?
                ORDER BY b.created_at DESC";

$stmt = $conn->prepare($sql_history);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result_history = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sejarah Rekod Tempahan - SCRS PMU</title>
    
    <!-- Ikon Bootstrap -->
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
        .menu-toggle-btn { font-size: 2rem; color: var(--black); transition: var(--transition); }
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
            transition: background 0.1s;
            text-decoration: none;
        }
        .dropdown-item:last-child { border-bottom: none; background-color: var(--pink); }
        .dropdown-item:hover { background-color: var(--yellow); }
        .dropdown-item:last-child:hover { background-color: #ff33aa; }

        /* --- SIDEBAR --- */
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
        .close-btn { border: 3px solid var(--black); background: var(--white); padding: 5px 10px; font-weight: 900; box-shadow: 2px 2px 0px var(--black); }

        .sidebar-nav { padding: 20px; display: flex; flex-direction: column; gap: 10px; }
        .sidebar-link {
            padding: 12px 15px; border: 3px solid transparent; font-weight: 800;
            text-transform: uppercase; display: flex; align-items: center; gap: 15px; transition: var(--transition);
        }
        .sidebar-link.active, .sidebar-link:hover { border: 3px solid var(--black); background: var(--white); transform: translate(-2px, -2px); box-shadow: 4px 4px 0px var(--black); }

        /* --- KANDUNGAN UTAMA --- */
        .main-content { flex: 1; padding: 2rem 20px; max-width: 1200px; margin: 0 auto; width: 100%; }

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

        table.neo-table th, table.neo-table td {
            padding: 12px 14px;
            border: 2px solid var(--black);
            font-weight: 700;
            font-size: 0.85rem;
            vertical-align: middle;
        }

        table.neo-table th {
            background-color: var(--yellow);
            font-weight: 900;
            text-transform: uppercase;
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
            display: inline-block;
            border-radius: 4px;
            box-shadow: none;
        }
        .badge-approved { background-color: var(--green); }
        .badge-pending { background-color: var(--yellow); }
        .badge-rejected { background-color: var(--pink); }
        .badge-completed { background-color: var(--blue); }

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
            <a href="dashboard.php" class="sidebar-link"><i class="bi bi-house-door-fill"></i> Papan Pemuka</a>
            <a href="booking.php" class="sidebar-link"><i class="bi bi-car-front-fill"></i> Cari & Tempah</a>
            <a href="my_bookings.php" class="sidebar-link"><i class="bi bi-clipboard-check-fill"></i> Status Tempahan</a>
            <a href="booking_history.php" class="sidebar-link active"><i class="bi bi-clock-history"></i> Sejarah Rekod</a>
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
                <a href="booking.php" class="neo-btn btn-green mobile-btn-full" style="padding: 10px 20px; font-size: 0.9rem; border-width: 3px;">
                    <i class="bi bi-plus-circle-fill me-1"></i> Tempah Kereta Baharu
                </a>
            </div>
            <p style="font-weight: 700; color: #555; font-size: 0.95rem; margin: 0; line-height: 1.5;">
                Senarai arkib keseluruhan rekod tempahan dan transaksi kenderaan anda (Selesai, Diluluskan, Menunggu, atau Ditolak).
            </p>
        </div>

        <!-- STATISTIK RINGKASAN PELAJAR -->
        <div class="stats-grid">
            <div class="stat-card" style="background: var(--blue);">
                <h3><?php echo $total_count; ?></h3>
                <p>Jumlah Rekod Tempahan</p>
            </div>
            <div class="stat-card" style="background: var(--green);">
                <h3><?php echo $completed_count; ?></h3>
                <p>Tempahan Selesai</p>
            </div>
            <div class="stat-card" style="background: var(--yellow);">
                <h3>RM <?php echo number_format($total_spent, 2); ?></h3>
                <p>Jumlah Perbelanjaan</p>
            </div>
        </div>

        <div class="neo-table-card">
            <?php if ($result_history->num_rows > 0): ?>
                <table class="neo-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Kereta</th>
                            <th>Penyedia</th>
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
                        $no = 1;
                        while ($row = $result_history->fetch_assoc()): 
                            $status = $row['status'];
                            $badge_class = 'badge-pending';
                            if ($status == 'Approved') $badge_class = 'badge-approved';
                            else if ($status == 'Completed') $badge_class = 'badge-completed';
                            else if ($status == 'Rejected') $badge_class = 'badge-rejected';
                        ?>
                            <tr>
                                <td style="text-align: center;"><?php echo $no++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['car_model']); ?></strong><br>
                                    <small style="color: #666; font-weight: 700;"><?php echo htmlspecialchars($row['car_plate']); ?></small>
                                </td>
                                <td>
                                    <a href="javascript:void(0)" onclick="showProviderModal('<?php echo htmlspecialchars($row['provider_username'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['provider_email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['provider_phone'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['provider_roadtax'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['provider_insurance'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['provider_profile_picture'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['provider_qr_code'] ?? '', ENT_QUOTES); ?>')" style="color: #0055ff; font-weight: 800; text-decoration: underline; cursor: pointer;">
                                        <?php echo htmlspecialchars($row['provider_name']); ?> <i class="bi bi-info-circle ms-1"></i>
                                    </a>
                                </td>
                                <td><?php echo date('d M Y, h:i A', strtotime($row['start_date'])); ?></td>
                                <td><?php echo date('d M Y, h:i A', strtotime($row['end_date'])); ?></td>
                                <td><?php echo ($row['rent_type'] === 'Daily') ? 'Harian' : 'Jam'; ?></td>
                                <td style="color: #008800; font-weight: 900;">RM <?php echo number_format($row['total_price'], 2); ?></td>
                                <td>
                                    <span class="neo-badge <?php echo $badge_class; ?>">
                                        <?php echo htmlspecialchars($status); ?>
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
                <div style="text-align: center; padding: 3rem 1rem;">
                    <i class="bi bi-folder-x" style="font-size: 3.5rem; display: block; margin-bottom: 12px; color: #666;"></i>
                    <h3 style="font-weight: 900; text-transform: uppercase;">Tiada Sejarah Rekod</h3>
                    <p style="font-weight: 700; color: #666; margin-bottom: 20px;">Anda belum membuat sebarang tempahan lagi.</p>
                    <a href="booking.php" class="neo-btn btn-green">
                        <i class="bi bi-key-fill me-1"></i> Tempah Kereta Pertama Anda
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- MODAL MAKLUMAT PROVIDER (POPUP) -->
    <div class="neo-modal-overlay" id="providerModalOverlay" onclick="closeProviderModalOutside(event)" style="z-index: 3000;">
        <div class="neo-modal" onclick="event.stopPropagation()" style="max-width: 480px;">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid var(--black); padding-bottom: 10px; margin-bottom: 15px;">
                <h3 class="modal-title" style="font-weight: 900; text-transform: uppercase; font-size: 1.2rem;">Maklumat Penyedia Kereta</h3>
            </div>
            <div class="modal-body" style="font-weight: 700; font-size: 0.95rem;">
                <div style="text-align: center; margin-bottom: 20px;">
                    <img id="modalProviderImg" src="" alt="Gambar Profil" style="width: 100px; height: 100px; border-radius: 50%; border: 3px solid var(--black); box-shadow: 4px 4px 0px var(--black); object-fit: cover;">
                </div>
                <div class="detail-row" style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 8px 0;">
                    <span style="color: #666;">Username:</span>
                    <span id="modalProviderUsername" style="color: var(--black); font-weight: 800;"></span>
                </div>
                <div class="detail-row" style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 8px 0;">
                    <span style="color: #666;">Email:</span>
                    <span id="modalProviderEmail" style="color: var(--black); font-weight: 800;"></span>
                </div>
                <div class="detail-row" style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 8px 0;">
                    <span style="color: #666;">No. Telefon:</span>
                    <span id="modalProviderPhone" style="color: var(--black); font-weight: 800;"></span>
                </div>
                <div class="detail-row" style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 8px 0;">
                    <span style="color: #666;">Roadtax (Cukai Jalan):</span>
                    <span>
                        <a id="modalProviderRoadtax" href="" target="_blank" class="neo-badge" style="display: inline-block; cursor: pointer; text-decoration: none; border: 2px solid var(--black); padding: 2px 6px; font-size: 0.75rem; background: var(--yellow); font-weight: 800;"><i class="bi bi-file-earmark-image me-1"></i>Lihat Fail</a>
                        <span id="modalProviderNoRoadtax" style="color: #999; display: none;">Tiada Fail</span>
                    </span>
                </div>
                <div class="detail-row" style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 8px 0;">
                    <span style="color: #666;">Insurans (Insurance):</span>
                    <span>
                        <a id="modalProviderInsurance" href="" target="_blank" class="neo-badge" style="display: inline-block; cursor: pointer; text-decoration: none; border: 2px solid var(--black); padding: 2px 6px; font-size: 0.75rem; background: var(--green); font-weight: 800;"><i class="bi bi-file-earmark-image me-1"></i>Lihat Fail</a>
                        <span id="modalProviderNoInsurance" style="color: #999; display: none;">Tiada Fail</span>
                    </span>
                </div>
                <div class="detail-row" style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 8px 0;">
                    <span style="color: #666;">Kod QR DuitNow:</span>
                    <span>
                        <a id="modalProviderQr" href="" target="_blank" class="neo-badge" style="display: inline-block; cursor: pointer; text-decoration: none; border: 2px solid var(--black); padding: 2px 6px; font-size: 0.75rem; background: var(--yellow); font-weight: 800;"><i class="bi bi-qr-code me-1"></i>Lihat QR</a>
                        <span id="modalProviderNoQr" style="color: #999; display: none;">Tiada QR</span>
                    </span>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <button class="neo-btn" style="width: 100%; justify-content: center; background: var(--pink);" onclick="closeProviderModal()"><i class="bi bi-arrow-left-short me-1"></i>Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        &copy; <?php echo date("Y"); ?> SCRS PMU. SISTEM SEWAAN KERETA.
    </footer>

    <!-- SKRIP ASLI (VANILLA JS) -->
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

        // Sidebar Offcanvas
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

        if (openSidebarBtn) openSidebarBtn.addEventListener('click', openSidebar);
        if (closeSidebarBtn) closeSidebarBtn.addEventListener('click', closeSidebar);
        if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

        // POPUP PROVIDER MODAL CONTROL
        window.showProviderModal = function(username, email, phone, roadtax, insurance, profilePic, qrCode) {
            document.getElementById('modalProviderUsername').textContent = username;
            document.getElementById('modalProviderEmail').textContent = email;
            document.getElementById('modalProviderPhone').textContent = phone;
            
            const imgElem = document.getElementById('modalProviderImg');
            if (profilePic && profilePic.trim() !== '') {
                imgElem.src = profilePic;
            } else {
                imgElem.src = 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
            }
            
            const rtElem = document.getElementById('modalProviderRoadtax');
            const noRtElem = document.getElementById('modalProviderNoRoadtax');
            if (roadtax && roadtax.trim() !== '') {
                rtElem.href = roadtax;
                rtElem.style.display = 'inline-block';
                noRtElem.style.display = 'none';
            } else {
                rtElem.style.display = 'none';
                noRtElem.style.display = 'inline-block';
            }
            
            const insElem = document.getElementById('modalProviderInsurance');
            const noInsElem = document.getElementById('modalProviderNoInsurance');
            if (insurance && insurance.trim() !== '') {
                insElem.href = insurance;
                insElem.style.display = 'inline-block';
                noInsElem.style.display = 'none';
            } else {
                insElem.style.display = 'none';
                noInsElem.style.display = 'inline-block';
            }

            const qrElem = document.getElementById('modalProviderQr');
            const noQrElem = document.getElementById('modalProviderNoQr');
            if (qrCode && qrCode.trim() !== '') {
                qrElem.href = qrCode;
                qrElem.style.display = 'inline-block';
                noQrElem.style.display = 'none';
            } else {
                qrElem.style.display = 'none';
                noQrElem.style.display = 'inline-block';
            }

            document.getElementById('providerModalOverlay').classList.add('show');
        };

        window.closeProviderModal = function() {
            document.getElementById('providerModalOverlay').classList.remove('show');
        };

        window.closeProviderModalOutside = function(e) {
            if (e.target.id === 'providerModalOverlay') {
                closeProviderModal();
            }
        };
    </script>
</body>
</html>
