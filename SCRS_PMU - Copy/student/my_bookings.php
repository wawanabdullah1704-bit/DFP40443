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

// PROSES MUAT NAIK RESIT PEMBAYARAN SELEPAS DILULUSKAN
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_payment_receipt'])) {
    $booking_id = (int)$_POST['booking_id'];
    if (!empty($_FILES['payment_receipt']['name'])) {
        $targetDir = "../uploads/receipts/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $receiptName = basename($_FILES["payment_receipt"]["name"]);
        $newReceiptName = "Resit_" . $student_id . "_" . time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $receiptName);
        $targetPath = $targetDir . $newReceiptName;
        $dbPath = "uploads/receipts/" . $newReceiptName;

        if (move_uploaded_file($_FILES["payment_receipt"]["tmp_name"], $targetPath)) {
            $sql_pay = "UPDATE bookings SET payment_receipt = ? WHERE id = ? AND student_id = ?";
            $stmt_pay = $conn->prepare($sql_pay);
            $stmt_pay->bind_param("sii", $dbPath, $booking_id, $student_id);
            $stmt_pay->execute();
            $stmt_pay->close();

            $sql_prov_id = "SELECT c.provider_id FROM bookings b JOIN cars c ON b.car_id = c.id WHERE b.id = ?";
            $st_pr = $conn->prepare($sql_prov_id);
            $st_pr->bind_param("i", $booking_id);
            $st_pr->execute();
            $p_row = $st_pr->get_result()->fetch_assoc();
            $st_pr->close();
            if ($p_row) {
                create_notification($conn, 'provider', $p_row['provider_id'], 'Resit Bayaran Dimuat Naik #' . $booking_id, 'Pelajar telah memuat naik resit pembayaran bagi tempahan #' . $booking_id . '.', '../provider/provider_bookings.php', 'info');
            }
        }
    }
    header("Location: my_bookings.php");
    exit();
}

// PROSES MUAT NAIK GAMBAR PULANGAN KERETA
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_return_image'])) {
    $booking_id = (int)$_POST['booking_id'];
    if (!empty($_FILES['return_image']['name'])) {
        $targetDir = "../uploads/returns/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $imgName = basename($_FILES["return_image"]["name"]);
        $newImgName = "Return_" . $booking_id . "_" . time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $imgName);
        $targetPath = $targetDir . $newImgName;
        $dbPath = "uploads/returns/" . $newImgName;

        if (move_uploaded_file($_FILES["return_image"]["tmp_name"], $targetPath)) {
            $sql_ret = "UPDATE bookings SET return_image = ? WHERE id = ? AND student_id = ?";
            $stmt_ret = $conn->prepare($sql_ret);
            $stmt_ret->bind_param("sii", $dbPath, $booking_id, $student_id);
            $stmt_ret->execute();
            $stmt_ret->close();

            $sql_prov_id2 = "SELECT c.provider_id FROM bookings b JOIN cars c ON b.car_id = c.id WHERE b.id = ?";
            $st_pr2 = $conn->prepare($sql_prov_id2);
            $st_pr2->bind_param("i", $booking_id);
            $st_pr2->execute();
            $p_row2 = $st_pr2->get_result()->fetch_assoc();
            $st_pr2->close();
            if ($p_row2) {
                create_notification($conn, 'provider', $p_row2['provider_id'], 'Bukti Pemulangan Kereta #' . $booking_id, 'Pelajar telah memuat naik bukti pemulangan kenderaan bagi tempahan #' . $booking_id . '.', '../provider/provider_bookings.php', 'info');
            }
        }
    }
    header("Location: my_bookings.php");
    exit();
}

// PROSES SAHKAN KERETA TELAH DIKEMBALIKAN
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['complete_return'])) {
    $booking_id = (int)$_POST['booking_id'];
    
    // Semak jika gambar pulangan DAN resit bayaran telah dimuat naik
    $sql_chk = "SELECT return_image, payment_receipt FROM bookings WHERE id = ? AND student_id = ?";
    $stmt_chk = $conn->prepare($sql_chk);
    $stmt_chk->bind_param("ii", $booking_id, $student_id);
    $stmt_chk->execute();
    $res_chk = $stmt_chk->get_result()->fetch_assoc();
    $stmt_chk->close();

    if ($res_chk && !empty($res_chk['return_image']) && !empty($res_chk['payment_receipt'])) {
        $sql_comp = "UPDATE bookings SET status = 'Completed' WHERE id = ? AND student_id = ?";
        $stmt_comp = $conn->prepare($sql_comp);
        $stmt_comp->bind_param("ii", $booking_id, $student_id);
        $stmt_comp->execute();
        $stmt_comp->close();
    }
    header("Location: my_bookings.php");
    exit();
}

// PROSES BATALKAN TEMPAHAN PENDING OLEH PELAJAR
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_booking'])) {
    $booking_id = (int)$_POST['booking_id'];
    $sql_cancel = "UPDATE bookings SET status = 'Cancelled' WHERE id = ? AND student_id = ? AND status = 'Pending'";
    $stmt_cancel = $conn->prepare($sql_cancel);
    $stmt_cancel->bind_param("ii", $booking_id, $student_id);
    if ($stmt_cancel->execute() && $stmt_cancel->affected_rows > 0) {
        $_SESSION['flash_msg'] = "<div class='neo-alert alert-success mb-3'><i class='bi bi-check-circle-fill me-2'></i>Tempahan #{$booking_id} telah berjaya dibatalkan.</div>";
    } else {
        $_SESSION['flash_msg'] = "<div class='neo-alert alert-danger mb-3'><i class='bi bi-exclamation-triangle-fill me-2'></i>Gagal membatalkan tempahan.</div>";
    }
    $stmt_cancel->close();
    header("Location: my_bookings.php");
    exit();
}

// Ambil senarai tempahan yang MASIH AKTIF / DALAM PROGRESS (Pending atau Approved)
$sql_bookings = "SELECT b.*, c.car_brand, c.car_model, c.car_plate, c.car_image, c.transmission, c.seat_capacity, c.price_per_day, c.price_per_hour,
                 c.roadtax_expiry, c.insurance_expiry, c.roadtax_file, c.insurance_file, c.grant_file,
                 p.username AS provider_username, p.email AS provider_email,
                 p.full_name AS provider_name, p.phone_no AS provider_phone,
                 p.roadtax_file AS provider_roadtax, p.insurance_file AS provider_insurance,
                 p.profile_picture AS provider_profile_picture,
                 p.qr_code_image AS provider_qr_code
                 FROM bookings b
                 JOIN cars c ON b.car_id = c.id
                 JOIN providers p ON c.provider_id = p.id
                 WHERE b.student_id = ? AND b.status IN ('Pending', 'Approved')
                 ORDER BY b.created_at DESC";

$stmt = $conn->prepare($sql_bookings);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result_bookings = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Status Tempahan Saya - SCRS PMU</title>
    
    <!-- Ikon Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Master Neo-Brutalism CSS -->
    <link rel="stylesheet" href="../assets/css/neo-style.css">

    <style>
        .section-title-badge {
            display: inline-block;
            background: var(--black);
            color: var(--white);
            font-weight: 900;
            text-transform: uppercase;
            padding: 8px 16px;
            border-radius: var(--radius-full);
            box-shadow: 3px 3px 0px var(--yellow);
            font-size: 1.15rem;
        }

        /* Kad Tempahan Kompak (Sesuai untuk Mobile & Desktop) */
        .booking-card-compact {
            background: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-solid);
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            position: relative;
            user-select: none;
        }
        .booking-card-compact:hover {
            transform: translate(-2px, -2px);
            box-shadow: 6px 6px 0px var(--black);
        }
        .booking-card-compact:active {
            transform: translate(1px, 1px);
            box-shadow: 2px 2px 0px var(--black);
        }
        .compact-thumb-wrap {
            position: relative;
            width: 88px;
            height: 70px;
            min-width: 88px;
            border-radius: var(--radius-md);
            border: 2px solid var(--black);
            overflow: hidden;
            background: #f1f5f9;
            flex-shrink: 0;
        }
        .compact-thumb-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .compact-info {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .compact-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        .compact-car-title {
            font-size: 0.95rem;
            font-weight: 900;
            text-transform: uppercase;
            margin: 0;
            color: var(--black);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.2;
        }
        .compact-car-plate {
            background: var(--black);
            color: var(--white);
            font-size: 0.7rem;
            font-weight: 800;
            padding: 1px 6px;
            border-radius: 4px;
            white-space: nowrap;
            letter-spacing: 0.5px;
            flex-shrink: 0;
        }
        .compact-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .compact-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-top: 2px;
            padding-top: 3px;
            border-top: 1px dashed #ddd;
        }
        .compact-price {
            font-size: 0.92rem;
            font-weight: 900;
            color: #007700;
            line-height: 1.2;
        }
        .compact-detail-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.72rem;
            font-weight: 800;
            background: var(--yellow);
            color: var(--black);
            border: 1.5px solid var(--black);
            box-shadow: 1.5px 1.5px 0px var(--black);
            border-radius: var(--radius-sm);
            padding: 2px 8px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        /* Modal Butiran Tempahan */
        .booking-detail-modal-overlay {
            z-index: 2000;
        }
        .modal-detail-banner {
            display: flex;
            gap: 12px;
            background: #f8fafc;
            border: 2px solid var(--black);
            border-radius: var(--radius-md);
            padding: 10px;
            margin-bottom: 12px;
            align-items: center;
            overflow: hidden;
        }
        .modal-detail-banner img {
            width: 90px;
            height: 66px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            border: 2px solid var(--black);
            flex-shrink: 0;
        }
        .modal-car-chip {
            background: #e2e8f0;
            color: #334155;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .modal-info-panel {
            background: #fff;
            border: 2px solid var(--black);
            border-radius: var(--radius-md);
            padding: 8px 12px;
            margin-bottom: 12px;
        }
        .modal-info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 7px 0;
            border-bottom: 1px dashed #e2e8f0;
            font-size: 0.84rem;
        }
        .modal-info-row:last-child {
            border-bottom: none;
            padding-bottom: 2px;
        }
        .modal-info-row .info-lbl {
            color: #64748b;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .modal-info-row .info-val {
            color: var(--black);
            font-weight: 800;
            text-align: right;
        }
        .modal-price-box {
            background: var(--yellow);
            border: 2px solid var(--black);
            border-radius: var(--radius-md);
            padding: 9px 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 900;
            margin-bottom: 12px;
            box-shadow: 2px 2px 0px var(--black);
        }
        .modal-req-box {
            background: #f8fafc;
            border: 2px solid var(--black);
            border-radius: var(--radius-md);
            padding: 8px 12px;
            margin-bottom: 12px;
        }
        .price-box {
            background-color: var(--bg-color);
            border: var(--border-thin);
            border-radius: var(--radius-md);
            padding: 10px 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 900;
            font-size: 1.05rem;
            margin-top: 10px;
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
            .compact-thumb-wrap { width: 80px; height: 64px; min-width: 80px; }
            .compact-car-title { font-size: 0.88rem; }
            .header-flex { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px !important; }
            .mobile-btn-full { width: 100% !important; justify-content: center !important; text-align: center; }
            .mobile-form-stack { flex-direction: column !important; align-items: stretch !important; gap: 8px !important; }
            .mobile-form-stack input[type="file"] { width: 100% !important; }
            .mobile-form-stack .neo-btn { width: 100% !important; justify-content: center; }
            .modal-detail-banner { gap: 10px !important; padding: 8px 10px !important; }
            .modal-detail-banner img { width: 80px !important; height: 60px !important; }
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
            <a href="my_bookings.php" class="sidebar-link active"><i class="bi bi-calendar-check-fill"></i> Tempahan Saya</a>
            <a href="booking_history.php" class="sidebar-link"><i class="bi bi-clock-history"></i> Rekod Tempahan</a>
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
                    Status Tempahan & Pembayaran
                </h1>
            </div>
            <p style="font-weight: 700; color: #555; font-size: 0.92rem; margin: 0; line-height: 1.5;">
                Pantau permohonan kenderaan anda di sini. Apabila diluluskan (Approved), imbas Kod QR DuitNow untuk membuat bayaran, muat naik resit, dan muat naik gambar pemulangan kenderaan.
            </p>
        </div>

        <?php 
        if (isset($_SESSION['flash_msg'])) {
            echo $_SESSION['flash_msg'];
            unset($_SESSION['flash_msg']);
        }
        ?>

        <?php if ($result_bookings->num_rows > 0): ?>
            <?php while ($booking = $result_bookings->fetch_assoc()): 
                $is_approved = ($booking['status'] == 'Approved');
                $badge_class = $is_approved ? 'badge-approved' : 'badge-pending';
                $status_text = $is_approved ? 'Diluluskan' : 'Menunggu Pengesahan';
                $status_icon = $is_approved ? 'bi-check-circle-fill' : 'bi-hourglass-split';
                $car_display_name = (!empty($booking['car_brand']) ? htmlspecialchars($booking['car_brand']) . ' ' : '') . htmlspecialchars($booking['car_model']);
                $car_img_src = (strpos($booking['car_image'], 'http') === 0 || strpos($booking['car_image'], '../') === 0) ? htmlspecialchars($booking['car_image']) : '../' . htmlspecialchars($booking['car_image']);

                $phone = preg_replace('/[^0-9]/', '', $booking['provider_phone']);
                if (strpos($phone, '0') === 0) {
                    $phone = '6' . $phone;
                }
                $prov_qr = $booking['provider_qr_code'] ?? '';
                $has_qr = (!empty($prov_qr) && file_exists(__DIR__ . '/../' . $prov_qr));
                $has_receipt = (!empty($booking['payment_receipt']) && file_exists(__DIR__ . '/../' . $booking['payment_receipt']));
                $has_return_img = (!empty($booking['return_image']) && file_exists(__DIR__ . '/../' . $booking['return_image']));
                $can_complete = ($has_receipt && $has_return_img);

                $disabled_text = "";
                if (!$can_complete) {
                    if (!$has_receipt && !$has_return_img) {
                        $disabled_text = "Sahkan Pulangan (Muat Naik Resit & Gambar Dahulu)";
                    } elseif (!$has_receipt) {
                        $disabled_text = "Sahkan Pulangan (Muat Naik Resit Bayaran Dahulu)";
                    } else {
                        $disabled_text = "Sahkan Pulangan (Muat Naik Gambar Kereta Dahulu)";
                    }
                }

                // JSON data untuk popup modal bayaran & pulangan
                $bookingModalData = htmlspecialchars(json_encode([
                    'id' => (int)$booking['id'],
                    'carModel' => $car_display_name,
                    'carPlate' => $booking['car_plate'],
                    'carImage' => $car_img_src,
                    'totalPrice' => (float)$booking['total_price'],
                    'providerName' => $booking['provider_name'],
                    'qrCode' => $has_qr ? $prov_qr : '',
                    'receipt' => $has_receipt ? $booking['payment_receipt'] : '',
                    'returnImage' => $has_return_img ? $booking['return_image'] : ''
                ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
            ?>
                <!-- KAD TEMPAHAN KOMPAK (MESRA MOBILE) -->
                <div class="booking-card-compact" onclick="openBookingDetailModal('bookingModal<?php echo $booking['id']; ?>')" title="Tekan untuk lihat maklumat penuh & tindakan">
                    <div class="compact-thumb-wrap">
                        <img src="<?php echo $car_img_src; ?>" alt="<?php echo $car_display_name; ?>" loading="lazy">
                    </div>
                    <div class="compact-info">
                        <div class="compact-header">
                            <h4 class="compact-car-title"><?php echo $car_display_name; ?></h4>
                            <span class="compact-car-plate"><?php echo htmlspecialchars($booking['car_plate']); ?></span>
                        </div>
                        <div class="compact-meta">
                            <span class="badge-status <?php echo $badge_class; ?>" style="font-size: 0.8rem;">
                                <i class="bi <?php echo $status_icon; ?>"></i> <?php echo $status_text; ?>
                            </span>
                        </div>
                        <div class="compact-bottom">
                            <span class="compact-price">RM <?php echo number_format($booking['total_price'], 2); ?></span>
                            <span class="compact-detail-btn"><i class="bi bi-info-circle me-1"></i> Butiran & Tindakan <i class="bi bi-chevron-right ms-1"></i></span>
                        </div>
                    </div>
                </div>

                <!-- MODAL POPUP MAKLUMAT LENGKAP TEMPAHAN & TINDAKAN -->
                <div class="neo-modal-overlay booking-detail-modal-overlay" id="bookingModal<?php echo $booking['id']; ?>" onclick="if(event.target === this) closeBookingDetailModal('bookingModal<?php echo $booking['id']; ?>')">
                    <div class="neo-modal" onclick="event.stopPropagation()" style="max-width: 480px;">
                        <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid var(--black);">
                            <h3 class="modal-title" style="font-weight: 900; font-size: 1.15rem; margin: 0; text-transform: uppercase;">
                                Butiran Tempahan #<?php echo $booking['id']; ?>
                            </h3>
                            <button type="button" class="close-btn" style="background: none; border: none; font-size: 1.4rem; font-weight: 900; cursor: pointer; line-height: 1; padding: 0 4px;" onclick="closeBookingDetailModal('bookingModal<?php echo $booking['id']; ?>')">&times;</button>
                        </div>

                        <!-- PREVIEW KERETA & STATUS (SEJAJAR & KEMAS) -->
                        <div class="modal-detail-banner">
                            <img src="<?php echo $car_img_src; ?>" alt="<?php echo $car_display_name; ?>">
                            <div style="flex: 1; min-width: 0;">
                                <h4 style="font-weight: 900; text-transform: uppercase; margin: 0 0 3px 0; font-size: 0.92rem; color: var(--black); line-height: 1.25; word-break: break-word;">
                                    <?php echo $car_display_name; ?>
                                </h4>
                                <div style="margin-bottom: 5px;">
                                    <span class="badge-status <?php echo $badge_class; ?>" style="font-size: 0.78rem;">
                                        <i class="bi <?php echo $status_icon; ?>"></i> <?php echo $status_text; ?>
                                    </span>
                                </div>
                                <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                                    <span style="background: var(--black); color: var(--white); font-size: 0.72rem; font-weight: 800; padding: 2px 6px; border-radius: 4px;"><?php echo htmlspecialchars($booking['car_plate']); ?></span>
                                    <span class="modal-car-chip"><i class="bi bi-gear-fill"></i> <?php echo htmlspecialchars($booking['transmission']); ?></span>
                                    <span class="modal-car-chip"><i class="bi bi-people-fill"></i> <?php echo htmlspecialchars($booking['seat_capacity']); ?> Tempat</span>
                                </div>
                            </div>
                        </div>

                        <!-- PANEL MAKLUMAT TERPERINCI (DISATUKAN DALAM SATU KAD KEMAS) -->
                        <div class="modal-info-panel">
                            <div class="modal-info-row">
                                <span class="info-lbl"><i class="bi bi-person-badge text-primary"></i> Penyedia Kereta:</span>
                                <a href="javascript:void(0)" onclick="showProviderModal('<?php echo htmlspecialchars($booking['provider_username'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($booking['provider_email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($booking['provider_phone'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars(!empty($booking['roadtax_file']) ? $booking['roadtax_file'] : ($booking['provider_roadtax'] ?? ''), ENT_QUOTES); ?>', '<?php echo htmlspecialchars(!empty($booking['insurance_file']) ? $booking['insurance_file'] : ($booking['provider_insurance'] ?? ''), ENT_QUOTES); ?>', '<?php echo htmlspecialchars($booking['provider_profile_picture'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($booking['provider_qr_code'] ?? '', ENT_QUOTES); ?>')" style="color: #0055ff; font-weight: 900; text-decoration: underline; cursor: pointer;">
                                    <?php echo htmlspecialchars($booking['provider_name']); ?> <i class="bi bi-info-circle ms-1"></i>
                                </a>
                            </div>
                            <div class="modal-info-row">
                                <span class="info-lbl"><i class="bi bi-clock-history text-primary"></i> Tarikh & Masa Ambil:</span>
                                <span class="info-val"><?php echo date('d M Y, h:i A', strtotime($booking['start_date'])); ?></span>
                            </div>
                            <div class="modal-info-row">
                                <span class="info-lbl"><i class="bi bi-clock text-danger"></i> Tarikh & Masa Pulang:</span>
                                <span class="info-val"><?php echo date('d M Y, h:i A', strtotime($booking['end_date'])); ?></span>
                            </div>
                            <div class="modal-info-row">
                                <span class="info-lbl"><i class="bi bi-hourglass-split text-warning"></i> Jenis & Kadar Sewaan:</span>
                                <span class="info-val">
                                    <?php echo ($booking['rent_type'] == 'Daily') ? 'Harian' : 'Jam'; ?> (RM <?php echo number_format(($booking['rent_type'] == 'Daily') ? $booking['price_per_day'] : $booking['price_per_hour'], 2); ?>/<?php echo ($booking['rent_type'] == 'Daily') ? 'hari' : 'jam'; ?>)
                                </span>
                            </div>
                            <div class="modal-info-row">
                                <span class="info-lbl"><i class="bi bi-shield-check text-success"></i> Sah Cukai & Insurans:</span>
                                <span class="info-val" style="color: #334155; font-size: 0.8rem;">
                                    <?php echo !empty($booking['roadtax_expiry']) ? date('d/m/Y', strtotime($booking['roadtax_expiry'])) : 'Sah'; ?>
                                </span>
                            </div>
                        </div>

                        <!-- JUMLAH BAYARAN -->
                        <div class="modal-price-box">
                            <span style="font-size: 0.95rem; text-transform: uppercase;">Jumlah Bayaran:</span>
                            <span style="font-size: 1.25rem; color: #006400;">RM <?php echo number_format($booking['total_price'], 2); ?></span>
                        </div>

                        <!-- BAHAGIAN TINDAKAN (ACTIONS) -->
                        <div>
                            <?php if ($is_approved): ?>
                                <!-- STATUS KEPERLUAN PEMULANGAN (BUKAN BUTTON) -->
                                <div class="modal-req-box">
                                    <div style="font-size: 0.74rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 5px;">
                                        Status Keperluan Selesai:
                                    </div>
                                    <div style="display: flex; gap: 10px; justify-content: space-between; font-size: 0.82rem; font-weight: 800;">
                                        <span style="color: <?php echo $has_receipt ? '#15803d' : '#64748b'; ?>; display: inline-flex; align-items: center; gap: 5px;">
                                            <i class="bi <?php echo $has_receipt ? 'bi-check-circle-fill' : 'bi-file-earmark-text'; ?>"></i>
                                            Resit Bayaran: <?php echo $has_receipt ? 'Sedia' : 'Belum'; ?>
                                        </span>
                                        <span style="color: <?php echo $has_return_img ? '#15803d' : '#64748b'; ?>; display: inline-flex; align-items: center; gap: 5px;">
                                            <i class="bi <?php echo $has_return_img ? 'bi-check-circle-fill' : 'bi-camera'; ?>"></i>
                                            Gambar Kereta: <?php echo $has_return_img ? 'Sedia' : 'Belum'; ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- BUTANG-BUTANG TINDAKAN -->
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px;">
                                    <button type="button" class="neo-btn btn-yellow" style="justify-content: center; font-size: 0.84rem; padding: 10px 4px;" data-booking="<?php echo $bookingModalData; ?>" onclick="handlePaymentClick(this)">
                                        <i class="bi bi-qr-code-scan me-1"></i> Bayaran Sewaan
                                    </button>
                                    <button type="button" class="neo-btn btn-blue" style="justify-content: center; font-size: 0.84rem; padding: 10px 4px;" data-booking="<?php echo $bookingModalData; ?>" onclick="handleReturnClick(this)">
                                        <i class="bi bi-camera-fill me-1"></i> Muat Naik Gambar
                                    </button>
                                </div>
                                
                                <a href="https://wa.me/<?php echo $phone; ?>?text=Hai,%20saya%20pelajar%20dari%20SCRS%20PMU.%20Tempahan%20kereta%20<?php echo urlencode($car_display_name); ?>%20saya%20telah%20diluluskan." target="_blank" class="neo-btn btn-green" style="width: 100%; justify-content: center; font-size: 0.88rem; padding: 10px; margin-bottom: 10px;">
                                    <i class="bi bi-whatsapp me-2"></i> Hubungi Penyedia (WhatsApp)
                                </a>

                                <!-- BUTANG SAHKAN PULANGAN KERETA -->
                                <div style="border-top: 2px dashed #cbd5e1; padding-top: 10px;">
                                    <?php if ($can_complete): ?>
                                        <form action="" method="POST" style="margin: 0; width: 100%;">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <button type="submit" name="complete_return" class="neo-btn btn-green" style="width: 100%; justify-content: center; font-size: 0.92rem; padding: 10px;" onclick="return confirm('Adakah anda pasti bahawa bayaran telah dibuat dan kenderaan telah dipulangkan dengan sempurna?');">
                                                <i class="bi bi-check-circle-fill me-1"></i> Sahkan Kereta Telah Dikembalikan
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button type="button" class="neo-btn" style="width: 100%; justify-content: center; background-color: #f1f5f9; color: #94a3b8; border: 2px dashed #cbd5e1; cursor: not-allowed; box-shadow: none; font-size: 0.8rem; font-weight: 800; padding: 9px;" disabled title="<?php echo $disabled_text; ?>">
                                            <i class="bi bi-lock-fill me-1"></i> Sahkan Pulangan (Muat naik resit & gambar)
                                        </button>
                                    <?php endif; ?>
                                </div>

                            <?php else: ?>
                                <div style="background: var(--yellow); border: 2px solid var(--black); border-radius: var(--radius-md); padding: 12px; font-weight: 800; font-size: 0.85rem; color: var(--black); margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                                    <i class="bi bi-hourglass-split" style="font-size: 1.1rem;"></i>
                                    <span>Permohonan tempahan anda sedang menunggu kelulusan daripada Penyedia Kereta.</span>
                                </div>
                                <form action="" method="POST" style="margin: 0; width: 100%;">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                    <button type="submit" name="cancel_booking" class="neo-btn btn-pink" style="width: 100%; justify-content: center; padding: 10px 16px; font-size: 0.85rem;" onclick="return confirm('Adakah anda pasti mahu membatalkan tempahan pending ini?');">
                                        <i class="bi bi-x-circle-fill me-1"></i> Batalkan Tempahan Ini
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <!-- BUTANG TUTUP MODAL -->
                        <div style="margin-top: 10px;">
                            <button type="button" class="neo-btn" style="width: 100%; justify-content: center; background: #fff; font-size: 0.85rem; padding: 8px;" onclick="closeBookingDetailModal('bookingModal<?php echo $booking['id']; ?>')">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-box">
                <i class="bi bi-folder-x"></i>
                <h2 style="font-weight: 900; text-transform: uppercase;">Tiada Tempahan Aktif</h2>
                <p style="font-weight: 700; color: #666; margin: 10px 0 20px 0;">Anda belum membuat sebarang tempahan baru atau tempahan anda telah selesai.</p>
                <a href="booking.php" class="neo-btn btn-green">
                    <i class="bi bi-plus-lg me-1"></i> Mula Tempah Kereta
                </a>
            </div>
        <?php endif; ?>

    </main>

    <!-- MODAL MAKLUMAT PROVIDER (POPUP) -->
    <div class="neo-modal-overlay" id="providerModalOverlay" onclick="closeProviderModalOutside(event)" style="z-index: 2200;">
        <div class="neo-modal" onclick="event.stopPropagation()">
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
                        <a id="modalProviderRoadtax" href="" target="_blank" class="neo-badge bg-y" style="display: inline-block; cursor: pointer; text-decoration: none; border: 2px solid var(--black); padding: 2px 6px; font-size: 0.75rem; background: var(--yellow); font-weight: 800;"><i class="bi bi-file-earmark-image me-1"></i>Lihat Fail</a>
                        <span id="modalProviderNoRoadtax" style="color: #999; display: none;">Tiada Fail</span>
                    </span>
                </div>
                <div class="detail-row" style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 8px 0;">
                    <span style="color: #666;">Insurans (Insurance):</span>
                    <span>
                        <a id="modalProviderInsurance" href="" target="_blank" class="neo-badge bg-g" style="display: inline-block; cursor: pointer; text-decoration: none; border: 2px solid var(--black); padding: 2px 6px; font-size: 0.75rem; background: var(--green); font-weight: 800;"><i class="bi bi-file-earmark-image me-1"></i>Lihat Fail</a>
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
                    <button class="neo-btn btn-pink" style="width: 100%; justify-content: center;" onclick="closeProviderModal()"><i class="bi bi-arrow-left-short me-1"></i>Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL BAYARAN SEWAAN (POPUP QR & RESIT) -->
    <div class="neo-modal-overlay" id="paymentModalOverlay" onclick="closePaymentModalOutside(event)" style="z-index: 2200;">
        <div class="neo-modal" onclick="event.stopPropagation()" style="max-width: 480px; padding: 18px;">
            <!-- Header dengan Butang X Tutup Standard -->
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 8px; border-bottom: 2px solid var(--black);">
                <h3 class="modal-title" style="font-weight: 900; text-transform: uppercase; font-size: 1.15rem; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-qr-code-scan"></i> Bayaran Sewaan
                </h3>
                <button type="button" class="close-btn" style="background: none; border: none; font-size: 1.4rem; font-weight: 900; cursor: pointer; line-height: 1; padding: 0 4px;" onclick="closePaymentModal()">&times;</button>
            </div>

            <div class="modal-body" style="font-weight: 700; font-size: 0.92rem;">
                
                <!-- Banner Kereta Sejajar (Format Sama Seperti Butiran Tempahan) -->
                <div class="modal-detail-banner" style="margin-bottom: 12px;">
                    <img id="payModalCarImg" src="" alt="Kereta" style="width: 80px; height: 60px; object-fit: cover; border-radius: var(--radius-sm); border: 2px solid var(--black); flex-shrink: 0; background: #fff;">
                    <div style="flex: 1; min-width: 0;">
                        <h4 id="payModalCarTitle" style="font-weight: 900; text-transform: uppercase; margin: 0 0 4px 0; font-size: 0.95rem; color: var(--black); line-height: 1.2;"></h4>
                        <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                            <span id="payModalCarPlate" style="background: var(--black); color: var(--white); font-size: 0.72rem; font-weight: 800; padding: 2px 6px; border-radius: 4px;"></span>
                            <span style="font-size: 0.78rem; font-weight: 800; color: #475569;">
                                <i class="bi bi-person-fill text-muted"></i> <span id="payModalProvider"></span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Kotak Jumlah Bayaran (Standard Neo-Brutalist) -->
                <div class="modal-price-box" style="margin-bottom: 14px;">
                    <span style="font-size: 0.88rem; text-transform: uppercase; color: #1e293b; font-weight: 800;">
                        <i class="bi bi-cash-stack me-1"></i> Jumlah Perlu Dibayar:
                    </span>
                    <span id="payModalAmount" style="font-size: 1.25rem; font-weight: 900; color: #007700;"></span>
                </div>

                <!-- Kad Imbasan Kod QR DuitNow -->
                <div class="modal-info-panel" style="padding: 14px; margin-bottom: 14px; text-align: center; background: #fff;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 8px;">
                        <span style="font-size: 0.82rem; font-weight: 900; text-transform: uppercase; color: #334155; display: inline-flex; align-items: center; gap: 5px;">
                            <i class="bi bi-qr-code text-primary"></i> Kod QR DuitNow
                        </span>
                        <span style="background: #e11d48; color: #fff; font-size: 0.68rem; font-weight: 900; padding: 2px 8px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.5px;">
                            DuitNow / TNG
                        </span>
                    </div>

                    <div id="payModalQrContainer" style="display: flex; flex-direction: column; align-items: center;">
                        <div style="background: #fff; border: 2px solid var(--black); border-radius: var(--radius-md); box-shadow: 3px 3px 0px var(--black); padding: 8px; display: inline-block; margin-bottom: 10px;">
                            <img id="payModalQrImg" src="" alt="QR DuitNow" style="max-height: 180px; max-width: 100%; border-radius: 4px; object-fit: contain; display: block;">
                        </div>
                        <a id="payModalQrDownload" href="" download class="neo-btn btn-yellow" style="text-decoration: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 0.82rem; padding: 7px 16px;">
                            <i class="bi bi-download"></i> Muat Turun Kod QR
                        </a>
                        <p style="font-size: 0.75rem; color: #64748b; margin: 8px 0 0 0; font-weight: 700;">
                            Imbas kod QR di atas menggunakan aplikasi perbankan atau e-Dompet anda.
                        </p>
                    </div>

                    <div id="payModalQrEmpty" style="background: #fee2e2; border: 2px solid #ef4444; border-radius: var(--radius-sm); padding: 12px; font-weight: 800; font-size: 0.82rem; color: #991b1b; text-align: center; display: none;">
                        <i class="bi bi-exclamation-octagon-fill me-1"></i> Penyedia ini belum memuat naik Kod QR. Sila hubungi penyedia secara terus.
                    </div>
                </div>

                <!-- Kad Muat Naik Resit Pembayaran -->
                <div class="modal-info-panel" style="padding: 14px; margin-bottom: 12px; background: #f8fafc;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="font-size: 0.82rem; font-weight: 900; text-transform: uppercase; color: #334155; display: inline-flex; align-items: center; gap: 5px;">
                            <i class="bi bi-receipt-cutoff text-primary"></i> Resit Pembayaran
                        </span>
                        <div id="payModalReceiptExisting" style="display: none;">
                            <a id="payModalReceiptLink" href="" target="_blank" class="neo-badge bg-g" style="display: inline-flex; align-items: center; gap: 4px; cursor: pointer; text-decoration: none; border: 1.5px solid var(--black); padding: 3px 8px; font-size: 0.72rem; font-weight: 800; background: var(--green); color: #000;">
                                <i class="bi bi-file-earmark-check-fill"></i> Lihat Resit Semasa
                            </a>
                        </div>
                    </div>

                    <form action="" method="POST" enctype="multipart/form-data" style="margin: 0;">
                        <input type="hidden" name="booking_id" id="payModalBookingId" value="">
                        <label style="display: block; font-weight: 700; font-size: 0.78rem; margin-bottom: 6px; color: #475569;">
                            Pilih fail resit pemindahan / tangkap layar pembayaran:
                        </label>
                        <div class="mobile-form-stack" style="display: flex; gap: 8px;">
                            <input type="file" name="payment_receipt" accept=".jpg,.jpeg,.png,.pdf" required
                                style="border: 2px solid var(--black); border-radius: var(--radius-sm); padding: 7px 10px; font-weight: 700; background: #fff; flex: 1; min-width: 0; font-size: 0.82rem;">
                            <button type="submit" name="upload_payment_receipt" class="neo-btn btn-blue" style="padding: 7px 14px; font-size: 0.82rem; white-space: nowrap;">
                                <i class="bi bi-cloud-arrow-up-fill me-1"></i> <span id="payModalReceiptBtnText">Hantar Resit</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Butang Tutup Modal Bawah -->
                <div style="margin-top: 10px;">
                    <button type="button" class="neo-btn" style="width: 100%; justify-content: center; background: #fff; font-size: 0.85rem; padding: 8px;" onclick="closePaymentModal()">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL PEMULANGAN KERETA & GAMBAR (POPUP) -->
    <div class="neo-modal-overlay" id="returnModalOverlay" onclick="closeReturnModalOutside(event)" style="z-index: 2200;">
        <div class="neo-modal" onclick="event.stopPropagation()" style="max-width: 480px; padding: 18px;">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 8px; border-bottom: 2px solid var(--black);">
                <h3 class="modal-title" style="font-weight: 900; text-transform: uppercase; font-size: 1.15rem; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-camera-fill text-primary"></i> Pemulangan Kenderaan
                </h3>
                <button type="button" class="close-btn" style="background: none; border: none; font-size: 1.4rem; font-weight: 900; cursor: pointer; line-height: 1; padding: 0 4px;" onclick="closeReturnModal()">&times;</button>
            </div>
            <div class="modal-body" style="font-weight: 700; font-size: 0.92rem;">
                
                <!-- Banner Kereta Sejajar -->
                <div class="modal-detail-banner" style="margin-bottom: 12px;">
                    <img id="returnModalCarImg" src="" alt="Kereta" style="width: 80px; height: 60px; object-fit: cover; border-radius: var(--radius-sm); border: 2px solid var(--black); flex-shrink: 0; background: #fff;">
                    <div style="flex: 1; min-width: 0;">
                        <h4 id="returnModalCarTitle" style="font-weight: 900; text-transform: uppercase; margin: 0 0 4px 0; font-size: 0.95rem; color: var(--black); line-height: 1.2;"></h4>
                        <span id="returnModalCarPlate" style="background: var(--black); color: var(--white); font-size: 0.72rem; font-weight: 800; padding: 2px 6px; border-radius: 4px;"></span>
                    </div>
                </div>

                <!-- Gambar Pulangan Semasa (Jika Ada) -->
                <div id="returnModalPreviewContainer" style="display: none; text-align: center; margin-bottom: 12px;" class="modal-info-panel">
                    <div style="font-weight: 900; font-size: 0.78rem; color: #15803d; margin-bottom: 8px; text-transform: uppercase; display: flex; align-items: center; justify-content: center; gap: 5px;">
                        <i class="bi bi-check-circle-fill"></i> Gambar Pulangan Sedia Ada
                    </div>
                    <img id="returnModalPreviewImg" src="" alt="Gambar Pulangan" style="max-width: 100%; max-height: 180px; border: 2px solid var(--black); border-radius: var(--radius-sm); box-shadow: 2px 2px 0 var(--black); object-fit: cover; margin-bottom: 4px;">
                </div>

                <!-- Form Muat Naik Gambar -->
                <div class="modal-info-panel" style="padding: 14px; margin-bottom: 12px; background: #f8fafc;">
                    <form action="" method="POST" enctype="multipart/form-data" style="margin: 0;">
                        <input type="hidden" name="booking_id" id="returnModalBookingIdUpload" value="">
                        <label style="display: block; font-weight: 700; font-size: 0.78rem; margin-bottom: 6px; color: #475569;">
                            Pilih gambar keadaan fizikal kereta selepas dipulangkan:
                        </label>
                        <div class="mobile-form-stack" style="display: flex; gap: 8px;">
                            <input type="file" name="return_image" accept=".jpg,.jpeg,.png" required
                                style="border: 2px solid var(--black); border-radius: var(--radius-sm); padding: 7px 10px; font-weight: 700; background: #fff; flex: 1; min-width: 0; font-size: 0.82rem;">
                            <button type="submit" name="upload_return_image" class="neo-btn btn-blue" style="padding: 7px 14px; font-size: 0.82rem; white-space: nowrap;">
                                <i class="bi bi-cloud-arrow-up-fill me-1"></i> <span id="returnModalUploadBtnText">Muat Naik</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Panduan Nota Pengesahan -->
                <div style="background: #fff8e1; border: 2px dashed #f57f17; border-radius: var(--radius-sm); padding: 8px 12px; font-size: 0.8rem; color: #5d4037; margin-bottom: 14px;">
                    <i class="bi bi-info-circle-fill text-warning me-1"></i> <strong>Nota:</strong> Sila pastikan kedua-dua <strong>Resit Bayaran</strong> dan <strong>Gambar Pulangan</strong> telah dimuat naik sebelum mengesahkan pemulangan kenderaan.
                </div>

                <div style="margin-top: 10px;">
                    <button type="button" class="neo-btn" style="width: 100%; justify-content: center; background: #fff; font-size: 0.85rem; padding: 8px;" onclick="closeReturnModal()">
                        Tutup
                    </button>
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

        openSidebarBtn.addEventListener('click', openSidebar);
        closeSidebarBtn.addEventListener('click', closeSidebar);
        sidebarOverlay.addEventListener('click', closeSidebar);

        // POPUP PROVIDER MODAL CONTROL
        function showProviderModal(username, email, phone, roadtax, insurance, profilePic, qrCode) {
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
        }

        function closeProviderModal() {
            document.getElementById('providerModalOverlay').classList.remove('show');
        }

        function closeProviderModalOutside(e) {
            if (e.target.id === 'providerModalOverlay') {
                closeProviderModal();
            }
        }

        // POPUP MODAL BUTIRAN TEMPAHAN
        function openBookingDetailModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.add('show');
        }

        function closeBookingDetailModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.remove('show');
        }

        // POPUP MODAL BAYARAN (PAYMENT MODAL CONTROL)
        function handlePaymentClick(btn) {
            try {
                const data = JSON.parse(btn.getAttribute('data-booking'));
                openPaymentModal(data);
            } catch (e) {
                console.error("Ralat memuatkan data bayaran:", e);
            }
        }

        function openPaymentModal(data) {
            document.getElementById('payModalBookingId').value = data.id;
            document.getElementById('payModalCarTitle').textContent = data.carModel;
            if (document.getElementById('payModalCarPlate')) {
                document.getElementById('payModalCarPlate').textContent = data.carPlate;
            }
            if (document.getElementById('payModalCarImg')) {
                const imgUrl = (data.carImage && data.carImage.trim() !== '') ? data.carImage : '../uploads/cars/default.png';
                document.getElementById('payModalCarImg').src = imgUrl;
            }
            document.getElementById('payModalProvider').textContent = data.providerName;
            document.getElementById('payModalAmount').textContent = 'RM ' + parseFloat(data.totalPrice).toFixed(2);

            // Kawal paparan Kod QR
            const qrContainer = document.getElementById('payModalQrContainer');
            const qrImg = document.getElementById('payModalQrImg');
            const qrDownload = document.getElementById('payModalQrDownload');
            const qrEmpty = document.getElementById('payModalQrEmpty');

            if (data.qrCode && data.qrCode.trim() !== '') {
                const qrUrl = (data.qrCode.startsWith('http') || data.qrCode.startsWith('../')) ? data.qrCode : ('../' + data.qrCode);
                qrImg.src = qrUrl;
                qrDownload.href = qrUrl;
                qrContainer.style.display = 'flex';
                qrEmpty.style.display = 'none';
            } else {
                qrContainer.style.display = 'none';
                qrEmpty.style.display = 'block';
            }

            // Kawal paparan Resit Sedia Ada
            const receiptExisting = document.getElementById('payModalReceiptExisting');
            const receiptLink = document.getElementById('payModalReceiptLink');
            const receiptBtnText = document.getElementById('payModalReceiptBtnText');

            if (data.receipt && data.receipt.trim() !== '') {
                receiptExisting.style.display = 'flex';
                receiptLink.href = (data.receipt.startsWith('http') || data.receipt.startsWith('../')) ? data.receipt : ('../' + data.receipt);
                receiptBtnText.textContent = 'Kemaskini Resit';
            } else {
                receiptExisting.style.display = 'none';
                receiptBtnText.textContent = 'Hantar Resit';
            }

            document.getElementById('paymentModalOverlay').classList.add('show');
        }

        function closePaymentModal() {
            document.getElementById('paymentModalOverlay').classList.remove('show');
        }

        function closePaymentModalOutside(e) {
            if (e.target.id === 'paymentModalOverlay') {
                closePaymentModal();
            }
        }

        // POPUP MODAL PULANG KERETA & GAMBAR (RETURN MODAL CONTROL)
        function handleReturnClick(btn) {
            try {
                const data = JSON.parse(btn.getAttribute('data-booking'));
                openReturnModal(data);
            } catch (e) {
                console.error("Ralat memuatkan data pulangan:", e);
            }
        }

        function openReturnModal(data) {
            document.getElementById('returnModalBookingIdUpload').value = data.id;
            document.getElementById('returnModalCarTitle').textContent = data.carModel;
            if (document.getElementById('returnModalCarPlate')) {
                document.getElementById('returnModalCarPlate').textContent = data.carPlate;
            }
            if (document.getElementById('returnModalCarImg')) {
                const imgUrl = (data.carImage && data.carImage.trim() !== '') ? data.carImage : '../uploads/cars/default.png';
                document.getElementById('returnModalCarImg').src = imgUrl;
            }

            const previewContainer = document.getElementById('returnModalPreviewContainer');
            const previewImg = document.getElementById('returnModalPreviewImg');
            const uploadBtnText = document.getElementById('returnModalUploadBtnText');

            if (data.returnImage && data.returnImage.trim() !== '') {
                previewImg.src = (data.returnImage.startsWith('http') || data.returnImage.startsWith('../')) ? data.returnImage : ('../' + data.returnImage);
                previewContainer.style.display = 'block';
                uploadBtnText.textContent = 'Kemaskini Gambar';
            } else {
                previewContainer.style.display = 'none';
                uploadBtnText.textContent = 'Muat Naik Gambar';
            }

            document.getElementById('returnModalOverlay').classList.add('show');
        }

        function closeReturnModal() {
            document.getElementById('returnModalOverlay').classList.remove('show');
        }

        function closeReturnModalOutside(e) {
            if (e.target.id === 'returnModalOverlay') {
                closeReturnModal();
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