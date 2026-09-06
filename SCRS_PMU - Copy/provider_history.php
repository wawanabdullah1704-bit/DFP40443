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
    <link rel="stylesheet" href="neo-style.css">

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

        .neo-table-card {
            background: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-solid);
            padding: 20px;
            overflow-x: auto;
            margin-bottom: 25px;
        }

        table.neo-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: var(--border-thin);
            border-radius: var(--radius-md);
            overflow: hidden;
            text-align: left;
            min-width: 700px;
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
            .stats-grid { grid-template-columns: 1fr; gap: 10px; }
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
            <h2>Menu Penyedia</h2>
            <button class="close-btn" id="close-sidebar"><i class="bi bi-x-lg"></i></button>
        </div>
        <nav class="sidebar-nav">
            <a href="provider_dashboard.php" class="sidebar-link"><i class="bi bi-speedometer2"></i> Papan Pemuka</a>
            <a href="provider_cars.php" class="sidebar-link"><i class="bi bi-car-front-fill"></i> Urus Kenderaan</a>
            <a href="provider_bookings.php" class="sidebar-link"><i class="bi bi-calendar-check-fill"></i> Urus Tempahan</a>
            <a href="provider_history.php" class="sidebar-link active"><i class="bi bi-clock-history"></i> Rekod Tempahan</a>
        </nav>
    </aside>

    <!-- KANDUNGAN UTAMA -->
    <main class="main-content">
        
        <!-- HEADING PANDUAN PENGGUNA (DENGAN BUTANG KEMBALI) -->
        <div style="margin-bottom: 25px;">
            <div class="header-flex" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 6px;">
                <div>
                    <h1 style="font-size: 1.6rem; font-weight: 900; text-transform: uppercase; margin: 0; color: var(--black); display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-clock-history text-dark"></i> Rekod Tempahan
                    </h1>
                </div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <a href="provider_dashboard.php" class="neo-btn btn-sm btn-yellow">
                        <i class="bi bi-arrow-left"></i> Papan Pemuka
                    </a>
                    <a href="provider_bookings.php" class="neo-btn btn-sm btn-green">
                        <i class="bi bi-clipboard-check-fill me-1"></i> Tempahan Semasa
                    </a>
                </div>
            </div>
            <p style="font-weight: 700; color: #555; font-size: 0.95rem; margin: 0; line-height: 1.5;">
                Senarai arkib keseluruhan transaksi tempahan yang telah selesai atau ditolak bagi kenderaan milik anda.
            </p>
        </div>

        <!-- STATISTIK RINGKAS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-top">
                    <span class="stat-title">Tempahan Selesai</span>
                    <div class="stat-icon-badge" style="background: var(--blue);">
                        <i class="bi bi-patch-check-fill"></i>
                    </div>
                </div>
                <div class="stat-number"><?php echo $completed_count; ?></div>
                <div class="stat-sub">Selesai & Dipulangkan</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-top">
                    <span class="stat-title">Jumlah Pendapatan</span>
                    <div class="stat-icon-badge" style="background: var(--green);">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
                <div class="stat-number">RM <?php echo number_format($total_earnings, 2); ?></div>
                <div class="stat-sub">Jumlah Bersih Diterima</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-top">
                    <span class="stat-title">Permohonan Ditolak</span>
                    <div class="stat-icon-badge" style="background: var(--pink);">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
                </div>
                <div class="stat-number"><?php echo $rejected_count; ?></div>
                <div class="stat-sub">Permohonan Tidak Berjaya</div>
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
                            if ($row['status'] === 'Completed') {
                                $badge_style = 'badge-completed';
                                $status_label = 'Selesai';
                            } elseif ($row['status'] === 'Cancelled') {
                                $badge_style = 'badge-rejected';
                                $status_label = 'Dibatalkan';
                            } else {
                                $badge_style = 'badge-rejected';
                                $status_label = 'Ditolak';
                            }
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
                    <h2 style="font-weight: 900; text-transform: uppercase;">Tiada Rekod Tempahan</h2>
                    <p style="font-weight: 700; color: #666; margin: 10px 0 0 0;">Belum ada rekod tempahan yang telah selesai, ditolak atau dibatalkan.</p>
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
