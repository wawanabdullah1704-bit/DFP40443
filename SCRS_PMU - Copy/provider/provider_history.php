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
$sql_history = "SELECT b.*, c.car_brand, c.car_model, c.car_plate, c.car_image,
                       s.username as student_username, s.full_name as student_name, s.email as student_email,
                       s.phone_no as student_phone, s.no_pendaftaran as student_matrix,
                       s.student_id_file as student_id_file, s.driving_license_file as student_license_file,
                       s.profile_picture as student_profile_pic
                FROM bookings b
                JOIN cars c ON b.car_id = c.id
                JOIN students s ON b.student_id = s.id
                WHERE c.provider_id = ? AND b.status IN ('Completed', 'Rejected', 'Cancelled')
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
    <title>Rekod Tempahan - SCRS PMU</title>
    
    <!-- Ikon Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Master Neo-Brutalism CSS -->
    <link rel="stylesheet" href="../assets/css/neo-style.css">

    <style>
        /* Ringkasan Statistik Kompak (Bukan Berbentuk Butang) */
        .stats-summary-strip {
            background: var(--white);
            border: 2px solid var(--black);
            border-radius: var(--radius-md);
            padding: 12px 16px;
            margin-bottom: 22px;
            display: flex;
            justify-content: space-around;
            align-items: center;
            text-align: center;
            box-shadow: none !important;
        }
        .stat-summary-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .stat-summary-lbl {
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.5px;
        }
        .stat-summary-val {
            font-size: 1.25rem;
            font-weight: 900;
            line-height: 1.2;
            color: var(--black);
        }
        .stat-summary-sep {
            width: 1px;
            height: 36px;
            background: #cbd5e1;
        }

        /* Kad Rekod Tempahan Responsif */
        .history-card {
            background: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-solid);
            padding: 14px;
            transition: var(--transition);
        }
        .history-card:hover {
            transform: translate(-2px, -2px);
            box-shadow: 6px 6px 0px var(--black) !important;
        }

        .empty-box {
            background: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-solid);
            padding: 40px 20px;
            text-align: center;
        }
        .empty-box i { font-size: 3.5rem; display: block; margin-bottom: 12px; }

        @media (max-width: 768px) {
            .stats-summary-strip { padding: 10px 8px; }
            .stat-summary-lbl { font-size: 0.68rem; }
            .stat-summary-val { font-size: 1.05rem; }
            .header-flex { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px !important; }
        }

        /* Seksyen Pengesahan Dokumen (Kemas & Responsif) */
        .doc-verification-strip {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px dashed #e2e8f0;
        }
        .doc-strip-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 6px;
        }
        .doc-strip-label {
            color: #64748b;
            font-weight: 800;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            display: inline-flex;
            align-items: center;
        }
        .doc-strip-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 8px;
        }
        .doc-item-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 7px 10px;
            font-size: 0.78rem;
            font-weight: 800;
            border: 1.5px solid var(--black);
            border-radius: var(--radius-sm);
            box-shadow: 2px 2px 0px var(--black);
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition);
            white-space: nowrap;
            text-align: center;
        }
        .doc-item-btn:hover {
            transform: translate(-1px, -1px);
            box-shadow: 3px 3px 0px var(--black);
        }
        .doc-item-btn:active {
            transform: translate(1px, 1px);
            box-shadow: 1px 1px 0px var(--black);
        }
        .doc-item-btn.btn-receipt {
            background-color: var(--yellow);
            color: var(--black);
        }
        .doc-item-btn.btn-return {
            background-color: #00f5d4;
            color: var(--black);
        }

        /* Modal Pengesahan Dokumen Ringkas (Tanpa Zoom) */
        .doc-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.78);
            backdrop-filter: blur(3px);
            z-index: 3000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 12px;
            box-sizing: border-box;
        }
        .doc-modal-box {
            background: var(--white);
            border: 3px solid var(--black);
            border-radius: var(--radius-lg);
            box-shadow: 6px 6px 0px var(--black);
            width: 100%;
            max-width: 650px;
            max-height: 92vh;
            display: flex;
            flex-direction: column;
            padding: 16px;
            box-sizing: border-box;
            position: relative;
        }
        .doc-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--black);
            padding-bottom: 10px;
            margin-bottom: 12px;
            gap: 10px;
            flex-wrap: nowrap;
        }
        .doc-modal-badge {
            background: var(--yellow);
            color: var(--black);
            font-size: 0.75rem;
            font-weight: 900;
            text-transform: uppercase;
            padding: 3px 8px;
            border: 1.5px solid var(--black);
            border-radius: var(--radius-sm);
            box-shadow: 1.5px 1.5px 0px var(--black);
        }
        .doc-viewer-stage {
            width: 100%;
            max-height: 64vh;
            background: #0f172a;
            border: 2px solid var(--black);
            border-radius: var(--radius-md);
            overflow: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px;
            box-sizing: border-box;
        }
        .doc-viewer-img {
            max-width: 100%;
            max-height: 60vh;
            object-fit: contain;
            border-radius: 4px;
            display: block;
            margin: auto;
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <header class="neo-navbar">
        <div class="neo-nav-left">
            <button class="menu-toggle-btn" id="open-sidebar"><i class="bi bi-list"></i></button>
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
            <a href="provider_dashboard.php" class="sidebar-link"><i class="bi bi-speedometer2"></i> Papan Pemuka</a>
            <a href="provider_cars.php" class="sidebar-link"><i class="bi bi-car-front-fill"></i> Urus Kenderaan</a>
            <a href="provider_bookings.php" class="sidebar-link"><i class="bi bi-clipboard-check-fill"></i> Senarai Permohonan</a>
            <a href="provider_history.php" class="sidebar-link active"><i class="bi bi-clock-history"></i> Rekod Tempahan</a>
        </nav>
    </aside>

    <!-- KANDUNGAN UTAMA -->
    <main class="main-content">
        
        <!-- HEADING PANDUAN PENGGUNA (DENGAN BUTANG KEMBALI) -->
        <div style="margin-bottom: 25px;">
            <div class="page-title-row">
                <a href="provider_dashboard.php" class="neo-btn btn-yellow btn-arrow-back" title="Papan Pemuka" aria-label="Kembali ke Papan Pemuka">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h1 style="font-size: 1.55rem; font-weight: 900; text-transform: uppercase; margin: 0; color: var(--black);">
                    Rekod Tempahan
                </h1>
            </div>
            <p style="font-weight: 700; color: #555; font-size: 0.92rem; margin: 0; line-height: 1.5;">
                Senarai arkib keseluruhan transaksi tempahan yang telah selesai atau ditolak bagi kenderaan milik anda.
            </p>
        </div>

        <!-- STATISTIK RINGKAS (STRIP RATA, BUKAN BUTANG) -->
        <div class="stats-summary-strip">
            <div class="stat-summary-item">
                <span class="stat-summary-lbl">Selesai</span>
                <span class="stat-summary-val" style="color: #15803d;"><?php echo $completed_count; ?></span>
            </div>
            <div class="stat-summary-sep"></div>
            <div class="stat-summary-item">
                <span class="stat-summary-lbl">Jumlah Pendapatan</span>
                <span class="stat-summary-val" style="color: #0284c7;">RM <?php echo number_format($total_earnings, 2); ?></span>
            </div>
            <div class="stat-summary-sep"></div>
            <div class="stat-summary-item">
                <span class="stat-summary-lbl">Ditolak / Batal</span>
                <span class="stat-summary-val" style="color: #b91c1c;"><?php echo $rejected_count; ?></span>
            </div>
        </div>

        <!-- SENARAI REKOD TEMPAHAN (RESPONSIF SEPERTI PAGE STUDENT) -->
        <?php if ($result_history->num_rows > 0): ?>
            <div class="history-list-container" style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 30px;">
                <?php 
                while ($row = $result_history->fetch_assoc()): 
                    $status = $row['status'];
                    if ($status === 'Completed') {
                        $badge_class = 'badge-completed';
                        $status_display = 'Selesai';
                        $status_icon = 'bi-check-all';
                    } elseif ($status === 'Cancelled') {
                        $badge_class = 'badge-rejected';
                        $status_display = 'Dibatalkan';
                        $status_icon = 'bi-x-circle';
                    } else {
                        $badge_class = 'badge-rejected';
                        $status_display = 'Ditolak';
                        $status_icon = 'bi-x-circle-fill';
                    }

                    $car_display_name = (!empty($row['car_brand']) ? htmlspecialchars($row['car_brand']) . ' ' : '') . htmlspecialchars($row['car_model']);
                    $car_img_src = '';
                    if (!empty($row['car_image'])) {
                        $car_img_src = (strpos($row['car_image'], 'http') === 0 || strpos($row['car_image'], '../') === 0) ? htmlspecialchars($row['car_image']) : '../' . htmlspecialchars($row['car_image']);
                    }
                ?>
                    <div class="history-card">
                        <!-- Baris Atas: Info Kereta, Pelajar & Status -->
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
                                        Penyewa: 
                                        <a href="javascript:void(0)" onclick="showStudentModal('<?php echo htmlspecialchars($row['student_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['student_username'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['student_email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['student_phone'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['student_matrix'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['student_id_file'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['student_license_file'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['student_profile_pic'] ?? '', ENT_QUOTES); ?>')" style="color: #0055ff; font-weight: 800; text-decoration: underline; cursor: pointer;">
                                            <?php echo htmlspecialchars($row['student_name']); ?> <i class="bi bi-info-circle ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                                <span class="badge-status <?php echo $badge_class; ?>" style="font-size: 0.85rem;">
                                    <i class="bi <?php echo $status_icon; ?>"></i> <?php echo $status_display; ?>
                                </span>
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
                            <div style="font-size: 0.95rem; font-weight: 900; color: <?php echo ($status === 'Completed') ? '#15803d' : '#64748b'; ?>;">
                                RM <?php echo number_format($row['total_price'], 2); ?>
                            </div>
                        </div>

                        <!-- Baris Bawah: Pengesahan Dokumen (Kemas, Tanpa Dokumen Pelajar) -->
                        <?php 
                        $has_receipt = (!empty($row['payment_receipt']) && file_exists(__DIR__ . '/../' . $row['payment_receipt']));
                        $has_return = (!empty($row['return_image']) && file_exists(__DIR__ . '/../' . $row['return_image']));
                        if ($has_receipt || $has_return): 
                        ?>
                            <div class="doc-verification-strip">
                                <div class="doc-strip-header">
                                    <span class="doc-strip-label"><i class="bi bi-file-earmark-text-fill text-primary"></i> Dokumen Tempahan:</span>
                                </div>
                                <div class="doc-strip-buttons">
                                    <?php if ($has_receipt): ?>
                                        <button type="button" class="doc-item-btn btn-receipt" onclick="viewDocument('<?php echo htmlspecialchars($row['payment_receipt']); ?>', 'Resit Bayaran - #<?php echo $row['id']; ?>')">
                                            <i class="bi bi-receipt"></i> Resit Bayaran
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($has_return): ?>
                                        <button type="button" class="doc-item-btn btn-return" onclick="viewDocument('<?php echo htmlspecialchars($row['return_image']); ?>', 'Gambar Pemulangan - #<?php echo $row['id']; ?>')">
                                            <i class="bi bi-camera-fill"></i> Gambar Pulang
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-box">
                <i class="bi bi-folder-x" style="color: #64748b;"></i>
                <h2 style="font-weight: 900; text-transform: uppercase; font-size: 1.2rem; margin: 0 0 8px 0;">Tiada Rekod Tempahan</h2>
                <p style="font-weight: 700; color: #666; margin: 0; font-size: 0.88rem;">Belum ada rekod tempahan yang telah selesai, ditolak atau dibatalkan.</p>
            </div>
        <?php endif; ?>

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
                        <a id="modalStudentIdDoc" href="" target="_blank" class="neo-btn btn-sm btn-yellow" style="padding: 3px 8px; font-size: 0.75rem; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;"><i class="bi bi-file-earmark-image"></i> Lihat Dokumen</a>
                        <span id="modalStudentNoIdDoc" style="color: #999; display: none;">Tiada Fail</span>
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 8px 0; align-items: center;">
                    <span style="color: #666;">Lesen Memandu:</span>
                    <span>
                        <a id="modalStudentLicenseDoc" href="" target="_blank" class="neo-btn btn-sm btn-green" style="padding: 3px 8px; font-size: 0.75rem; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;"><i class="bi bi-file-earmark-image"></i> Lihat Lesen</a>
                        <span id="modalStudentNoLicenseDoc" style="color: #999; display: none;">Tiada Fail</span>
                    </span>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <button class="neo-btn btn-white" style="width: 100%; justify-content: center;" onclick="closeStudentModal()"><i class="bi bi-x-lg me-1"></i> Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL PENGESAHAN DOKUMEN (RINGKAS & KEMAS - TANPA ZOOM) -->
    <div class="doc-modal-overlay" id="docModalOverlay" onclick="if(event.target===this) closeDocModal()">
        <div class="doc-modal-box" onclick="event.stopPropagation()">
            <div class="doc-modal-header">
                <div style="display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0; overflow: hidden;">
                    <span class="doc-modal-badge" id="docTypeBadge" style="flex-shrink: 0;">Dokumen</span>
                    <h3 id="docModalTitle" style="font-weight: 900; font-size: 0.95rem; margin: 0; text-transform: uppercase; color: var(--black); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        Pratonton Dokumen
                    </h3>
                </div>
            </div>

            <!-- TINGKAP LIHAT DOKUMEN -->
            <div class="doc-viewer-stage">
                <img id="docPreviewImg" src="" alt="Pratonton Dokumen" class="doc-viewer-img">
                <iframe id="docPreviewPdf" src="" style="width: 100%; height: 58vh; border: none; display: none;"></iframe>
            </div>

            <!-- BUTANG TINDAKAN RINGKAS -->
            <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 12px; padding-top: 10px; border-top: 1.5px dashed #ddd;">
                <a id="docOpenTabBtn" href="" target="_blank" class="neo-btn btn-sm btn-yellow" style="padding: 5px 14px; font-size: 0.8rem;">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Buka Penuh
                </a>
                <button type="button" class="neo-btn btn-sm btn-white" onclick="closeDocModal()" style="padding: 5px 14px; font-size: 0.8rem;">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        &copy; <?php echo date("Y"); ?> SCRS PMU. SISTEM SEWAAN KERETA.
    </footer>

    <!-- SKRIP ASLI & KAWALAN DOKUMEN -->
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
                imgElem.src = (profilePic.startsWith('http') || profilePic.startsWith('../')) ? profilePic : ('../' + profilePic);
            } else {
                imgElem.src = 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
            }
            
            const idElem = document.getElementById('modalStudentIdDoc');
            const noIdElem = document.getElementById('modalStudentNoIdDoc');
            if (idDoc && idDoc.trim() !== '') {
                idElem.href = (idDoc.startsWith('http') || idDoc.startsWith('../')) ? idDoc : ('../' + idDoc);
                idElem.style.display = 'inline-block';
                noIdElem.style.display = 'none';
            } else {
                idElem.style.display = 'none';
                noIdElem.style.display = 'inline-block';
            }
            
            const licElem = document.getElementById('modalStudentLicenseDoc');
            const noLicElem = document.getElementById('modalStudentNoLicenseDoc');
            if (licenseDoc && licenseDoc.trim() !== '') {
                licElem.href = (licenseDoc.startsWith('http') || licenseDoc.startsWith('../')) ? licenseDoc : ('../' + licenseDoc);
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

        // KAWALAN DOKUMEN VIEWER MODAL (RINGKAS TANPA ZOOM)
        const docModalOverlay = document.getElementById('docModalOverlay');
        const docModalTitle = document.getElementById('docModalTitle');
        const docTypeBadge = document.getElementById('docTypeBadge');
        const docPreviewImg = document.getElementById('docPreviewImg');
        const docPreviewPdf = document.getElementById('docPreviewPdf');
        const docOpenTabBtn = document.getElementById('docOpenTabBtn');

        function viewDocument(url, title) {
            if (!url || url.trim() === '') return;
            docModalTitle.textContent = title;

            if (docTypeBadge) {
                const lowerTitle = title.toLowerCase();
                if (lowerTitle.includes('resit')) {
                    docTypeBadge.textContent = 'Resit Bayaran';
                    docTypeBadge.style.background = 'var(--yellow)';
                } else if (lowerTitle.includes('pulang') || lowerTitle.includes('pemulangan')) {
                    docTypeBadge.textContent = 'Gambar Pemulangan';
                    docTypeBadge.style.background = '#00f5d4';
                } else {
                    docTypeBadge.textContent = 'Dokumen';
                    docTypeBadge.style.background = '#e2e8f0';
                }
            }

            const fullUrl = (url && !url.startsWith('../') && !url.startsWith('http')) ? '../' + url : url;
            docOpenTabBtn.href = fullUrl;

            const isPdf = fullUrl.toLowerCase().endsWith('.pdf');
            if (isPdf) {
                docPreviewImg.style.display = 'none';
                docPreviewPdf.style.display = 'block';
                docPreviewPdf.src = fullUrl;
            } else {
                docPreviewPdf.style.display = 'none';
                docPreviewImg.style.display = 'block';
                docPreviewImg.src = fullUrl;
            }

            docModalOverlay.style.display = 'flex';
        }

        function closeDocModal() {
            if (docPreviewPdf) docPreviewPdf.src = '';
            if (docPreviewImg) docPreviewImg.src = '';
            docModalOverlay.style.display = 'none';
        }
    </script>
</body>
</html>
