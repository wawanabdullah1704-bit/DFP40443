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
$message = "";

// PROSES BATALKAN TEMPAHAN PENDING OLEH PELAJAR
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_booking'])) {
    $booking_id = (int)$_POST['booking_id'];
    $sql_cancel = "UPDATE bookings SET status = 'Cancelled' WHERE id = ? AND student_id = ? AND status = 'Pending'";
    $stmt_cancel = $conn->prepare($sql_cancel);
    $stmt_cancel->bind_param("ii", $booking_id, $student_id);
    if ($stmt_cancel->execute() && $stmt_cancel->affected_rows > 0) {
        $message = "<div class='neo-alert alert-success mb-3'><i class='bi bi-check-circle-fill me-2'></i>Tempahan #{$booking_id} telah berjaya dibatalkan.</div>";
    } else {
        $message = "<div class='neo-alert alert-danger mb-3'><i class='bi bi-exclamation-triangle-fill me-2'></i>Gagal membatalkan tempahan.</div>";
    }
    $stmt_cancel->close();
}

// STATISTIK REKOD PELAJAR
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
$sql_history = "SELECT b.*, c.car_brand, c.car_model, c.car_plate, c.car_image, 
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
    <title>Rekod Tempahan - SCRS PMU</title>
    
    <!-- Ikon Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Master Neo-Brutalism CSS -->
    <link rel="stylesheet" href="../assets/css/neo-style.css">

    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 22px;
        }
        .stat-card {
            border: var(--border-thick) !important;
            border-radius: var(--radius-lg) !important;
            box-shadow: var(--shadow-solid) !important;
            padding: 18px 20px !important;
            text-align: left !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: stretch !important;
            justify-content: space-between !important;
            cursor: default;
            user-select: none;
            background: var(--white) !important;
            min-height: 135px;
            transition: var(--transition) !important;
        }
        .stat-card:hover {
            transform: translate(-2px, -2px);
            box-shadow: var(--shadow-lg) !important;
        }
        .stat-card-top {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            width: 100% !important;
            margin-bottom: 12px !important;
        }
        .stat-title {
            font-size: 0.85rem !important;
            font-weight: 900 !important;
            text-transform: uppercase !important;
            color: #555 !important;
            letter-spacing: 0.5px;
            margin: 0 !important;
            line-height: 1.2 !important;
        }
        .stat-icon-badge {
            font-size: 1.35rem !important;
            width: 44px !important;
            height: 44px !important;
            min-width: 44px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            border: var(--border-thin) !important;
            border-radius: var(--radius-md) !important;
            box-shadow: var(--shadow-sm) !important;
            color: var(--black) !important;
            flex-shrink: 0 !important;
        }
        .stat-number {
            font-size: 2rem !important;
            font-weight: 900 !important;
            line-height: 1 !important;
            margin-bottom: 6px !important;
            color: var(--black) !important;
            text-align: left !important;
        }
        .stat-sub {
            font-size: 0.775rem !important;
            font-weight: 700 !important;
            color: #666 !important;
            text-align: left !important;
            line-height: 1.3 !important;
        }

        .history-card {
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .history-card:hover {
            transform: translate(-2px, -2px);
            box-shadow: 6px 6px 0px var(--black) !important;
        }

        table.neo-table th, table.neo-table td {
            padding: 11px 13px;
            border-bottom: var(--border-thin);
            border-right: var(--border-thin);
            font-weight: 700;
            font-size: 0.85rem;
            vertical-align: middle;
        }
        table.neo-table th:last-child, table.neo-table td:last-child {
            border-right: none;
        }
        table.neo-table tr:last-child td {
            border-bottom: none;
        }

        table.neo-table th {
            background-color: var(--yellow);
            font-weight: 900;
            text-transform: uppercase;
        }

        table.neo-table tr:nth-child(even) {
            background-color: #fafafa;
        }

        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr; gap: 10px; }
            .header-flex { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px !important; }
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
            <a href="dashboard.php" class="sidebar-link"><i class="bi bi-speedometer2"></i> Papan Pemuka</a>
            <a href="booking.php" class="sidebar-link"><i class="bi bi-car-front-fill"></i> Cari & Tempah</a>
            <a href="my_bookings.php" class="sidebar-link"><i class="bi bi-calendar-check-fill"></i> Tempahan Saya</a>
            <a href="booking_history.php" class="sidebar-link active"><i class="bi bi-clock-history"></i> Rekod Tempahan</a>
        </nav>
    </aside>

    <!-- KANDUNGAN UTAMA -->
    <main class="main-content">
        
        <!-- HEADING PANDUAN PENGGUNA (DENGAN BUTANG KEMBALI) -->
        <div style="margin-bottom: 25px;">
            <div class="page-title-row">
                <a href="dashboard.php" class="neo-btn btn-yellow btn-arrow-back" title="Papan Pemuka" aria-label="Kembali ke Papan Pemuka">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h1 style="font-size: 1.55rem; font-weight: 900; text-transform: uppercase; margin: 0; color: var(--black);">
                    Rekod Tempahan
                </h1>
            </div>
            <p style="font-weight: 700; color: #555; font-size: 0.92rem; margin: 0; line-height: 1.5;">
                Senarai arkib keseluruhan rekod tempahan dan transaksi kenderaan anda (Selesai, Diluluskan, Menunggu, atau Ditolak).
            </p>
        </div>

        <?php echo $message; ?>

        <!-- SENARAI REKOD TEMPAHAN (RESPONSIF - TIADA SCROLL MENDATAR) -->
        <?php if ($result_history->num_rows > 0): ?>
            <div class="history-list-container" style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 30px;">
                <?php 
                $no = 1;
                while ($row = $result_history->fetch_assoc()): 
                    $status = $row['status'];
                    $badge_class = 'badge-pending';
                    $status_display = 'Menunggu';
                    $status_icon = 'bi-hourglass-split';

                    if ($status == 'Approved') {
                        $badge_class = 'badge-approved';
                        $status_display = 'Diluluskan';
                        $status_icon = 'bi-check-circle-fill';
                    } else if ($status == 'Completed') {
                        $badge_class = 'badge-completed';
                        $status_display = 'Selesai';
                        $status_icon = 'bi-check-all';
                    } else if ($status == 'Rejected') {
                        $badge_class = 'badge-rejected';
                        $status_display = 'Ditolak';
                        $status_icon = 'bi-x-circle-fill';
                    } else if ($status == 'Cancelled') {
                        $badge_class = 'badge-rejected';
                        $status_display = 'Dibatalkan';
                        $status_icon = 'bi-x-circle';
                    } else {
                        $badge_class = 'badge-pending';
                        $status_display = 'Menunggu';
                        $status_icon = 'bi-hourglass-split';
                    }

                    $car_display_name = (!empty($row['car_brand']) ? htmlspecialchars($row['car_brand']) . ' ' : '') . htmlspecialchars($row['car_model']);
                    $car_img_src = '';
                    if (!empty($row['car_image'])) {
                        $car_img_src = (strpos($row['car_image'], 'http') === 0 || strpos($row['car_image'], '../') === 0) ? htmlspecialchars($row['car_image']) : '../' . htmlspecialchars($row['car_image']);
                    }
                ?>
                    <div class="history-card" style="background: var(--white); border: var(--border-thick); border-radius: var(--radius-lg); box-shadow: var(--shadow-solid); padding: 14px; transition: var(--transition);">
                        <!-- Baris Atas: Kereta & Status -->
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 10px; flex-wrap: wrap;">
                            <div style="display: flex; align-items: center; gap: 10px; min-width: 0; flex: 1;">
                                <?php if (!empty($car_img_src)): ?>
                                    <img src="<?php echo $car_img_src; ?>" alt="<?php echo $car_display_name; ?>" style="width: 64px; height: 48px; object-fit: cover; border: 2px solid var(--black); border-radius: var(--radius-sm); flex-shrink: 0;">
                                <?php endif; ?>
                                <div style="min-width: 0;">
                                    <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                        <h4 style="font-weight: 900; text-transform: uppercase; margin: 0; font-size: 0.95rem; color: var(--black); line-height: 1.2;">
                                            <?php echo $car_display_name; ?>
                                        </h4>
                                        <span style="background: var(--black); color: var(--white); font-size: 0.68rem; font-weight: 800; padding: 1px 6px; border-radius: 4px;">
                                            <?php echo htmlspecialchars($row['car_plate']); ?>
                                        </span>
                                    </div>
                                    <div style="font-size: 0.78rem; font-weight: 700; color: #555; margin-top: 3px;">
                                        Penyedia: 
                                        <a href="javascript:void(0)" onclick="showProviderModal('<?php echo htmlspecialchars($row['provider_username'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['provider_email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['provider_phone'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['provider_roadtax'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['provider_insurance'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['provider_profile_picture'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['provider_qr_code'] ?? '', ENT_QUOTES); ?>')" style="color: #0055ff; font-weight: 800; text-decoration: underline; cursor: pointer;">
                                            <?php echo htmlspecialchars($row['provider_name']); ?> <i class="bi bi-info-circle ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                                <span class="badge-status <?php echo $badge_class; ?>" style="font-size: 0.85rem;">
                                    <i class="bi <?php echo $status_icon; ?>"></i> <?php echo $status_display; ?>
                                </span>
                                <?php if ($status == 'Pending'): ?>
                                    <form action="" method="POST" style="margin: 0;" onsubmit="return confirm('Adakah anda pasti mahu membatalkan tempahan ini?');">
                                        <input type="hidden" name="booking_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="cancel_booking" class="neo-btn btn-sm btn-pink" style="padding: 2px 8px; font-size: 0.72rem;">
                                            <i class="bi bi-x-circle me-1"></i> Batal
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Baris Tengah: Info Tarikh & Jumlah Bayaran -->
                        <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: var(--radius-md); padding: 8px 12px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; font-size: 0.82rem; font-weight: 700;">
                            <div style="display: flex; align-items: center; gap: 6px; color: #475569; flex-wrap: wrap;">
                                <span><i class="bi bi-clock text-primary me-1"></i><?php echo date('d M Y, h:i A', strtotime($row['start_date'])); ?></span>
                                <span>➔</span>
                                <span><?php echo date('d M Y, h:i A', strtotime($row['end_date'])); ?></span>
                                <span style="background: #e2e8f0; color: #334155; font-size: 0.7rem; padding: 1px 6px; border-radius: 4px; font-weight: 800;">
                                    <?php echo ($row['rent_type'] === 'Daily') ? 'Harian' : 'Jam'; ?>
                                </span>
                            </div>
                            <div style="font-size: 0.95rem; font-weight: 900; color: #007700;">
                                RM <?php echo number_format($row['total_price'], 2); ?>
                            </div>
                        </div>

                        <!-- Baris Bawah: Dokumen (Jika Ada) -->
                        <?php 
                        $has_receipt = (!empty($row['payment_receipt']) && file_exists(__DIR__ . '/../' . $row['payment_receipt']));
                        $has_return = (!empty($row['return_image']) && file_exists(__DIR__ . '/../' . $row['return_image']));
                        if ($has_receipt || $has_return): 
                        ?>
                            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-top: 10px; padding-top: 8px; border-top: 1px dashed #e2e8f0;">
                                <span style="color: #64748b; font-weight: 800; font-size: 0.78rem; text-transform: uppercase;">Dokumen:</span>
                                <?php if ($has_receipt): ?>
                                    <a href="../<?php echo htmlspecialchars($row['payment_receipt']); ?>" target="_blank" class="neo-btn btn-sm btn-yellow" style="padding: 4px 10px; font-size: 0.76rem; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                                        <i class="bi bi-receipt"></i> Resit Bayaran
                                    </a>
                                <?php endif; ?>
                                <?php if ($has_return): ?>
                                    <a href="../<?php echo htmlspecialchars($row['return_image']); ?>" target="_blank" class="neo-btn btn-sm btn-blue" style="padding: 4px 10px; font-size: 0.76rem; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                                        <i class="bi bi-camera-fill"></i> Gambar Pulang
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div style="background: var(--white); border: var(--border-thick); border-radius: var(--radius-lg); box-shadow: var(--shadow-solid); text-align: center; padding: 3rem 1rem;">
                <i class="bi bi-folder-x" style="font-size: 3.5rem; display: block; margin-bottom: 12px; color: #666;"></i>
                <h3 style="font-weight: 900; text-transform: uppercase;">Tiada Rekod Tempahan</h3>
                <p style="font-weight: 700; color: #666; margin-bottom: 20px;">Anda belum membuat sebarang tempahan lagi.</p>
                <a href="booking.php" class="neo-btn btn-green">
                    <i class="bi bi-plus-lg me-1"></i> Tempah Kereta Pertama Anda
                </a>
            </div>
        <?php endif; ?>
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
                imgElem.src = (profilePic.startsWith('http') || profilePic.startsWith('../')) ? profilePic : ('../' + profilePic);
            } else {
                imgElem.src = 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
            }
            
            const rtElem = document.getElementById('modalProviderRoadtax');
            const noRtElem = document.getElementById('modalProviderNoRoadtax');
            if (roadtax && roadtax.trim() !== '') {
                rtElem.href = (roadtax.startsWith('http') || roadtax.startsWith('../')) ? roadtax : ('../' + roadtax);
                rtElem.style.display = 'inline-block';
                noRtElem.style.display = 'none';
            } else {
                rtElem.style.display = 'none';
                noRtElem.style.display = 'inline-block';
            }
            
            const insElem = document.getElementById('modalProviderInsurance');
            const noInsElem = document.getElementById('modalProviderNoInsurance');
            if (insurance && insurance.trim() !== '') {
                insElem.href = (insurance.startsWith('http') || insurance.startsWith('../')) ? insurance : ('../' + insurance);
                insElem.style.display = 'inline-block';
                noInsElem.style.display = 'none';
            } else {
                insElem.style.display = 'none';
                noInsElem.style.display = 'inline-block';
            }

            const qrElem = document.getElementById('modalProviderQr');
            const noQrElem = document.getElementById('modalProviderNoQr');
            if (qrCode && qrCode.trim() !== '') {
                const cleanQr = (qrCode.startsWith('http') || qrCode.startsWith('../')) ? qrCode : ('../' + qrCode);
                qrElem.href = cleanQr + (cleanQr.includes('?') ? '&' : '?') + 't=' + new Date().getTime();
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
