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

// PROSES MUAT NAIK RESIT PEMBAYARAN SELEPAS DILULUSKAN
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_payment_receipt'])) {
    $booking_id = (int)$_POST['booking_id'];
    if (!empty($_FILES['payment_receipt']['name'])) {
        $targetDir = "uploads/receipts/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $receiptName = basename($_FILES["payment_receipt"]["name"]);
        $newReceiptName = "Resit_" . $student_id . "_" . time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $receiptName);
        $targetPath = $targetDir . $newReceiptName;

        if (move_uploaded_file($_FILES["payment_receipt"]["tmp_name"], $targetPath)) {
            $sql_pay = "UPDATE bookings SET payment_receipt = ? WHERE id = ? AND student_id = ?";
            $stmt_pay = $conn->prepare($sql_pay);
            $stmt_pay->bind_param("sii", $targetPath, $booking_id, $student_id);
            $stmt_pay->execute();
            $stmt_pay->close();
        }
    }
    header("Location: my_bookings.php");
    exit();
}

// PROSES MUAT NAIK GAMBAR PULANGAN KERETA
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_return_image'])) {
    $booking_id = (int)$_POST['booking_id'];
    if (!empty($_FILES['return_image']['name'])) {
        $targetDir = "uploads/returns/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $imgName = basename($_FILES["return_image"]["name"]);
        $newImgName = "Return_" . $booking_id . "_" . time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $imgName);
        $targetPath = $targetDir . $newImgName;

        if (move_uploaded_file($_FILES["return_image"]["tmp_name"], $targetPath)) {
            $sql_ret = "UPDATE bookings SET return_image = ? WHERE id = ? AND student_id = ?";
            $stmt_ret = $conn->prepare($sql_ret);
            $stmt_ret->bind_param("sii", $targetPath, $booking_id, $student_id);
            $stmt_ret->execute();
            $stmt_ret->close();
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
    <link rel="stylesheet" href="neo-style.css">

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

        /* Kad Tempahan */
        .booking-card {
            background: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-solid);
            margin-bottom: 22px;
            display: flex;
            flex-direction: row;
            overflow: hidden;
            transition: var(--transition);
        }
        .booking-card:hover {
            box-shadow: var(--shadow-lg);
        }
        .booking-img {
            width: 260px;
            height: 100%;
            min-height: 200px;
            object-fit: cover;
            border-right: var(--border-thick);
            background-color: #eee;
        }
        .booking-body {
            padding: 18px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid var(--black);
            padding-bottom: 10px;
            margin-bottom: 14px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .car-name { font-size: 1.2rem; font-weight: 900; text-transform: uppercase; }
        .car-plate { font-weight: 700; color: #555; font-size: 0.85rem; }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 14px;
        }
        .detail-item { font-weight: 700; font-size: 0.875rem; }
        .detail-label { display: block; text-transform: uppercase; font-size: 0.75rem; color: #666; font-weight: 800; margin-bottom: 2px; }
        .detail-value { display: flex; align-items: center; gap: 6px; }

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
            .booking-card { flex-direction: column; }
            .booking-img { width: 100%; height: 190px; border-right: none; border-bottom: var(--border-thick); }
            .details-grid { grid-template-columns: 1fr; gap: 10px; }
            
            .header-flex { flex-direction: column; align-items: stretch !important; gap: 10px !important; }
            .header-flex .neo-btn { width: 100% !important; justify-content: center; }
            .mobile-btn-full { width: 100% !important; justify-content: center !important; text-align: center; }
            .mobile-form-stack { flex-direction: column !important; align-items: stretch !important; gap: 8px !important; }
            .mobile-form-stack input[type="file"] { width: 100% !important; }
            .mobile-form-stack .neo-btn { width: 100% !important; justify-content: center; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <header class="neo-navbar">
        <div class="neo-nav-left">
            <button class="menu-toggle-btn" id="open-sidebar"><i class="bi bi-list"></i></button>
            <a href="dashboard.php" class="neo-brand"><i class="bi bi-car-front-fill me-1"></i>SCRS <span>PMU</span></a>
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
            <div class="header-flex" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 6px;">
                <div>
                    <h1 style="font-size: 1.6rem; font-weight: 900; text-transform: uppercase; margin: 0; color: var(--black); display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-clipboard-check-fill text-dark"></i> Status Tempahan & Pembayaran
                    </h1>
                </div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <a href="dashboard.php" class="neo-btn btn-sm btn-yellow">
                        <i class="bi bi-arrow-left"></i> Papan Pemuka
                    </a>
                    <a href="booking_history.php" class="neo-btn btn-sm btn-white">
                        <i class="bi bi-clock-history"></i> Rekod Tempahan
                    </a>
                </div>
            </div>
            <p style="font-weight: 700; color: #555; font-size: 0.95rem; margin: 0; line-height: 1.5;">
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
            ?>
                <div class="booking-card">
                    <img src="<?php echo htmlspecialchars($booking['car_image']); ?>" class="booking-img" alt="Kereta">
                    <div class="booking-body">
                        <div>
                            <div class="booking-header">
                                <div>
                                    <h3 class="car-name"><?php echo (!empty($booking['car_brand']) ? htmlspecialchars($booking['car_brand']) . ' ' : '') . htmlspecialchars($booking['car_model']); ?></h3>
                                    <span class="car-plate"><?php echo htmlspecialchars($booking['car_plate']); ?></span>
                                </div>
                                <span class="neo-badge <?php echo $badge_class; ?>">
                                    <i class="bi <?php echo $status_icon; ?>"></i> <?php echo $status_text; ?>
                                </span>
                            </div>

                            <div class="details-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Tarikh & Masa Ambil</span>
                                    <div class="detail-value"><i class="bi bi-calendar-check text-success"></i> <?php echo date('d M Y, h:i A', strtotime($booking['start_date'])); ?></div>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Tarikh & Masa Pulang</span>
                                    <div class="detail-value"><i class="bi bi-calendar-x text-danger"></i> <?php echo date('d M Y, h:i A', strtotime($booking['end_date'])); ?></div>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Penyedia Kereta</span>
                                    <div class="detail-value">
                                        <i class="bi bi-person-badge text-primary"></i> 
                                        <a href="javascript:void(0)" onclick="showProviderModal('<?php echo htmlspecialchars($booking['provider_username'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($booking['provider_email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($booking['provider_phone'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars(!empty($booking['roadtax_file']) ? $booking['roadtax_file'] : ($booking['provider_roadtax'] ?? ''), ENT_QUOTES); ?>', '<?php echo htmlspecialchars(!empty($booking['insurance_file']) ? $booking['insurance_file'] : ($booking['provider_insurance'] ?? ''), ENT_QUOTES); ?>', '<?php echo htmlspecialchars($booking['provider_profile_picture'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($booking['provider_qr_code'] ?? '', ENT_QUOTES); ?>')" style="color: #0055ff; font-weight: 900; text-decoration: underline; cursor: pointer;">
                                            <?php echo htmlspecialchars($booking['provider_name']); ?> <i class="bi bi-info-circle-fill ms-1 fs-6"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Jenis Sewaan</span>
                                    <div class="detail-value"><i class="bi bi-clock"></i> <?php echo ($booking['rent_type'] == 'Daily') ? 'Harian (Daily)' : 'Jam (Hourly)'; ?></div>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Kadar Asas</span>
                                    <div class="detail-value">
                                        <i class="bi bi-tag-fill text-warning"></i> 
                                        RM <?php echo number_format(($booking['rent_type'] == 'Daily') ? $booking['price_per_day'] : $booking['price_per_hour'], 2); ?> / <?php echo ($booking['rent_type'] == 'Daily') ? 'Hari' : 'Jam'; ?>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Cukai Jalan Sah</span>
                                    <div class="detail-value">
                                        <i class="bi bi-calendar-event text-primary"></i> 
                                        <?php echo !empty($booking['roadtax_expiry']) ? date('d/m/Y', strtotime($booking['roadtax_expiry'])) : '<span style="color:#888;">-</span>'; ?>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Insurans Sah</span>
                                    <div class="detail-value">
                                        <i class="bi bi-shield-check text-success"></i> 
                                        <?php echo !empty($booking['insurance_expiry']) ? date('d/m/Y', strtotime($booking['insurance_expiry'])) : '<span style="color:#888;">-</span>'; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="price-box">
                                <span>Jumlah Bayaran:</span>
                                <span style="color: #007700;">RM <?php echo number_format($booking['total_price'], 2); ?></span>
                            </div>
                        </div>

                        <div>
                            <?php if ($is_approved): 
                                $phone = preg_replace('/[^0-9]/', '', $booking['provider_phone']);
                                if (strpos($phone, '0') === 0) {
                                    $phone = '6' . $phone;
                                }
                                $prov_qr = $booking['provider_qr_code'] ?? '';
                                $has_qr = (!empty($prov_qr) && file_exists($prov_qr));
                                $has_receipt = (!empty($booking['payment_receipt']) && file_exists($booking['payment_receipt']));
                                $has_return_img = (!empty($booking['return_image']) && file_exists($booking['return_image']));

                                // JSON data untuk popup modal
                                $bookingModalData = htmlspecialchars(json_encode([
                                    'id' => (int)$booking['id'],
                                    'carModel' => (!empty($booking['car_brand']) ? $booking['car_brand'] . ' ' : '') . $booking['car_model'],
                                    'carPlate' => $booking['car_plate'],
                                    'totalPrice' => (float)$booking['total_price'],
                                    'providerName' => $booking['provider_name'],
                                    'qrCode' => $has_qr ? $prov_qr : '',
                                    'receipt' => $has_receipt ? $booking['payment_receipt'] : '',
                                    'returnImage' => $has_return_img ? $booking['return_image'] : ''
                                ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
                            ?>
                                <div style="margin-top: 15px; border-top: 2px dashed var(--black); padding-top: 15px; text-align: left;">
                                    <?php
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
                                    ?>
                                    <!-- STATUS BADGES RINGKAS -->
                                    <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px;">
                                        <?php if ($has_receipt): ?>
                                            <span class="neo-badge" style="background: #e8f5e9; color: #2e7d32; border-color: #2e7d32;">
                                                <i class="bi bi-check-circle-fill"></i> Resit Bayaran: Sedia
                                            </span>
                                        <?php else: ?>
                                            <span class="neo-badge" style="background: #fff3e0; color: #e65100; border-color: #e65100;">
                                                <i class="bi bi-hourglass-split"></i> Resit: Belum Dimuat Naik
                                            </span>
                                        <?php endif; ?>

                                        <?php if ($has_return_img): ?>
                                            <span class="neo-badge" style="background: #e8f5e9; color: #2e7d32; border-color: #2e7d32;">
                                                <i class="bi bi-camera-fill"></i> Gambar Pulangan: Sedia
                                            </span>
                                        <?php else: ?>
                                            <span class="neo-badge" style="background: #f5f5f5; color: #666; border-color: #999;">
                                                <i class="bi bi-camera"></i> Gambar Pulangan: Belum
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- BUTANG-BUTANG TINDAKAN KEMAS -->
                                    <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 12px;">
                                        <button type="button" class="neo-btn btn-yellow mobile-btn-full" data-booking="<?php echo $bookingModalData; ?>" onclick="handlePaymentClick(this)">
                                            <i class="bi bi-qr-code-scan"></i> Bayaran Sewaan
                                        </button>
                                        <button type="button" class="neo-btn btn-blue mobile-btn-full" data-booking="<?php echo $bookingModalData; ?>" onclick="handleReturnClick(this)">
                                            <i class="bi bi-camera-fill"></i> Muat Naik Gambar
                                        </button>
                                        <a href="https://wa.me/<?php echo $phone; ?>?text=Hai,%20saya%20pelajar%20dari%20SCRS%20PMU.%20Tempahan%20kereta%20<?php echo urlencode((!empty($booking['car_brand']) ? $booking['car_brand'] . ' ' : '') . $booking['car_model']); ?>%20saya%20telah%20diluluskan." target="_blank" class="neo-btn btn-green mobile-btn-full">
                                            <i class="bi bi-whatsapp"></i> Hubungi Penyedia
                                        </a>
                                    </div>

                                    <!-- BUTANG SAHKAN PULANGAN KERETA (DI LUAR) -->
                                    <div style="border-top: 2px dashed var(--black); padding-top: 12px;">
                                        <?php if ($can_complete): ?>
                                            <form action="" method="POST" style="margin: 0; width: 100%;">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                <button type="submit" name="complete_return" class="neo-btn btn-green mobile-btn-full" style="width: 100%; justify-content: center; font-size: 0.95rem;" onclick="return confirm('Adakah anda pasti bahawa bayaran telah dibuat dan kenderaan telah dipulangkan dengan sempurna?');">
                                                    <i class="bi bi-check-circle-fill me-1"></i> Sahkan Kereta Telah Dikembalikan
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button type="button" class="neo-btn mobile-btn-full" style="width: 100%; justify-content: center; background-color: #e0e0e0; color: #777; border-color: #888; cursor: not-allowed; box-shadow: none; font-size: 0.85rem;" disabled title="<?php echo $disabled_text; ?>">
                                                <i class="bi bi-lock-fill me-1"></i> <?php echo $disabled_text; ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>

                            <?php else: ?>
                                <div style="margin-top: 15px; border-top: 2px dashed var(--black); padding-top: 15px; text-align: left; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                                    <div style="background: var(--yellow); border: 2px solid var(--black); padding: 10px 14px; font-weight: 800; font-size: 0.85rem; color: #000; flex: 1; min-width: 250px;">
                                        <i class="bi bi-hourglass-split me-1"></i> Permohonan tempahan anda sedang menunggu kelulusan daripada Penyedia Kereta.
                                    </div>
                                    <form action="" method="POST" style="margin: 0;" onsubmit="return confirm('Adakah anda pasti mahu membatalkan tempahan pending ini?');">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <button type="submit" name="cancel_booking" class="neo-btn btn-pink mobile-btn-full" style="padding: 10px 16px; font-size: 0.85rem; white-space: nowrap;">
                                            <i class="bi bi-x-circle-fill me-1"></i> Batalkan Tempahan
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
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
    <div class="neo-modal-overlay" id="providerModalOverlay" onclick="closeProviderModalOutside(event)">
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
    <div class="neo-modal-overlay" id="paymentModalOverlay" onclick="closePaymentModalOutside(event)">
        <div class="neo-modal" onclick="event.stopPropagation()">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid var(--black); padding-bottom: 10px; margin-bottom: 15px;">
                <h3 class="modal-title" style="font-weight: 900; text-transform: uppercase; font-size: 1.2rem; display: flex; align-items: center; gap: 8px; color: #0055ff;">
                    <i class="bi bi-qr-code-scan"></i> Bayaran Sewaan
                </h3>
            </div>
            <div class="modal-body" style="font-weight: 700; font-size: 0.95rem;">
                
                <!-- Info Tempahan -->
                <div style="background: var(--bg-color); border: 2px solid var(--black); padding: 12px; margin-bottom: 15px;">
                    <div id="payModalCarTitle" style="font-weight: 900; font-size: 1.1rem; text-transform: uppercase; margin-bottom: 5px;"></div>
                    <div style="font-size: 0.85rem; color: #555;">Penyedia: <strong id="payModalProvider" style="color: var(--black);"></strong></div>
                    <div style="background: var(--yellow); border: 2px solid var(--black); padding: 8px 12px; font-weight: 900; font-size: 1.15rem; margin-top: 8px; display: flex; justify-content: space-between; align-items: center;">
                        <span>Jumlah Perlu Dibayar:</span>
                        <span id="payModalAmount" style="color: #007700;"></span>
                    </div>
                </div>

                <!-- Bahagian Kod QR -->
                <div style="margin-bottom: 15px;">
                    <p style="font-size: 0.85rem; font-weight: 800; color: #333; margin-bottom: 8px;">
                        <i class="bi bi-info-circle-fill text-primary me-1"></i> Imbas Kod QR DuitNow di bawah untuk membuat bayaran:
                    </p>
                    
                    <div id="payModalQrContainer" style="display: flex; flex-direction: column; align-items: center; justify-content: center; background: #fff; border: 2px solid var(--black); padding: 12px; margin-bottom: 10px;">
                        <img id="payModalQrImg" src="" alt="QR DuitNow" style="max-height: 200px; max-width: 100%; border: 3px solid var(--black); box-shadow: 3px 3px 0 var(--black); object-fit: contain; background: #fff; padding: 6px; margin-bottom: 10px;">
                        <a id="payModalQrDownload" href="" download class="neo-btn mobile-btn-full" style="text-decoration: none; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; font-size: 0.85rem; background: var(--yellow); padding: 8px 16px;">
                            <i class="bi bi-download"></i> Muat Turun Kod QR
                        </a>
                    </div>

                    <div id="payModalQrEmpty" style="background: var(--pink); border: 2px solid var(--black); padding: 12px; font-weight: 800; font-size: 0.85rem; text-align: center; display: none;">
                        <i class="bi bi-exclamation-octagon-fill me-1"></i> Penyedia ini belum memuat naik Kod QR. Sila hubungi penyedia secara langsung untuk kaedah pembayaran.
                    </div>
                </div>

                <!-- Bahagian Muat Naik Resit -->
                <div style="border-top: 2px dashed #ccc; padding-top: 15px; margin-bottom: 15px;">
                    <div id="payModalReceiptExisting" style="display: none; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; background: #e8f5e9; border: 2px solid #2e7d32; padding: 8px 12px;">
                        <span style="font-weight: 900; text-transform: uppercase; font-size: 0.8rem; color: #2e7d32;">
                            <i class="bi bi-check-circle-fill me-1"></i> Resit Telah Dimuat Naik
                        </span>
                        <a id="payModalReceiptLink" href="" target="_blank" class="neo-badge" style="background: var(--green); text-decoration: none; font-size: 0.75rem;">
                            <i class="bi bi-receipt me-1"></i> Lihat Resit Semasa
                        </a>
                    </div>

                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="booking_id" id="payModalBookingId" value="">
                        <label style="display: block; font-weight: 800; font-size: 0.85rem; margin-bottom: 6px; text-transform: uppercase; color: #333;">
                            <i class="bi bi-receipt-cutoff me-1"></i> Muat Naik / Kemaskini Resit Pembayaran:
                        </label>
                        <div class="mobile-form-stack" style="display: flex; gap: 8px;">
                            <input type="file" name="payment_receipt" accept=".jpg,.jpeg,.png,.pdf" required
                                style="border: 2px solid var(--black); padding: 7px; font-weight: 700; background: var(--bg-color); flex: 1; min-width: 0; font-size: 0.85rem;">
                            <button type="submit" name="upload_payment_receipt" class="neo-btn btn-blue" style="padding: 8px 16px; font-size: 0.85rem; white-space: nowrap;">
                                <i class="bi bi-cloud-arrow-up-fill"></i> <span id="payModalReceiptBtnText">Hantar Resit</span>
                            </button>
                        </div>
                    </form>
                </div>

                <div style="text-align: center; margin-top: 15px;">
                    <button class="neo-btn btn-pink" style="width: 100%; justify-content: center;" onclick="closePaymentModal()">
                        <i class="bi bi-arrow-left-short me-1"></i> Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL PEMULANGAN KERETA & GAMBAR (POPUP) -->
    <div class="neo-modal-overlay" id="returnModalOverlay" onclick="closeReturnModalOutside(event)">
        <div class="neo-modal" onclick="event.stopPropagation()">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid var(--black); padding-bottom: 10px; margin-bottom: 15px;">
                <h3 class="modal-title" style="font-weight: 900; text-transform: uppercase; font-size: 1.2rem; display: flex; align-items: center; gap: 8px; color: #0088cc;">
                    <i class="bi bi-camera-fill"></i> Pemulangan Kenderaan
                </h3>
            </div>
            <div class="modal-body" style="font-weight: 700; font-size: 0.95rem;">
                
                <!-- Info Tempahan -->
                <div style="background: var(--bg-color); border: 2px solid var(--black); padding: 12px; margin-bottom: 15px;">
                    <div id="returnModalCarTitle" style="font-weight: 900; font-size: 1.1rem; text-transform: uppercase; margin-bottom: 4px;"></div>
                    <p style="font-size: 0.85rem; font-weight: 700; color: #555; margin: 0; line-height: 1.4;">
                        Sila muat naik gambar keadaan fizikal kereta selepas dipulangkan untuk rujukan penyedia.
                    </p>
                </div>

                <!-- Gambar Pulangan Semasa (Jika Ada) -->
                <div id="returnModalPreviewContainer" style="display: none; text-align: center; margin-bottom: 15px; background: #fff; border: 2px solid var(--black); padding: 12px;">
                    <div style="font-weight: 900; font-size: 0.8rem; color: #007700; margin-bottom: 8px; text-transform: uppercase;">
                        <i class="bi bi-check-circle-fill me-1"></i> Gambar Pulangan Telah Dimuat Naik
                    </div>
                    <img id="returnModalPreviewImg" src="" alt="Gambar Pulangan" style="max-width: 100%; max-height: 180px; border: 3px solid var(--black); box-shadow: 3px 3px 0 var(--black); object-fit: cover; margin-bottom: 6px;">
                </div>

                <!-- Form Muat Naik Gambar -->
                <div style="margin-bottom: 15px;">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="booking_id" id="returnModalBookingIdUpload" value="">
                        <label style="display: block; font-weight: 800; font-size: 0.85rem; margin-bottom: 6px; text-transform: uppercase; color: #333;">
                            <i class="bi bi-camera me-1"></i> Pilih Gambar Kereta Selepas Dipulangkan:
                        </label>
                        <div class="mobile-form-stack" style="display: flex; gap: 8px;">
                            <input type="file" name="return_image" accept=".jpg,.jpeg,.png" required
                                style="border: 2px solid var(--black); padding: 7px; font-weight: 700; background: var(--bg-color); flex: 1; min-width: 0; font-size: 0.85rem;">
                            <button type="submit" name="upload_return_image" class="neo-btn btn-blue" style="padding: 8px 16px; font-size: 0.85rem; white-space: nowrap;">
                                <i class="bi bi-cloud-arrow-up-fill"></i> <span id="returnModalUploadBtnText">Muat Naik</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Panduan Nota Pengesahan -->
                <div style="background: #fff8e1; border: 2px dashed #f57f17; padding: 8px 12px; font-size: 0.82rem; color: #5d4037; margin-bottom: 15px;">
                    <i class="bi bi-info-circle-fill text-warning me-1"></i> <strong>Nota:</strong> Sila muat naik kedua-dua <strong>Resit Bayaran</strong> dan <strong>Gambar Pulangan</strong> sebelum membuat pengesahan pulangan kereta.
                </div>

                <div style="text-align: center;">
                    <button class="neo-btn btn-pink" style="width: 100%; justify-content: center;" onclick="closeReturnModal()">
                        <i class="bi bi-arrow-left-short me-1"></i> Tutup
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
        }

        function closeProviderModal() {
            document.getElementById('providerModalOverlay').classList.remove('show');
        }

        function closeProviderModalOutside(e) {
            if (e.target.id === 'providerModalOverlay') {
                closeProviderModal();
            }
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
            document.getElementById('payModalCarTitle').textContent = data.carModel + ' (' + data.carPlate + ')';
            document.getElementById('payModalProvider').textContent = data.providerName;
            document.getElementById('payModalAmount').textContent = 'RM ' + parseFloat(data.totalPrice).toFixed(2);

            // Kawal paparan Kod QR
            const qrContainer = document.getElementById('payModalQrContainer');
            const qrImg = document.getElementById('payModalQrImg');
            const qrDownload = document.getElementById('payModalQrDownload');
            const qrEmpty = document.getElementById('payModalQrEmpty');

            if (data.qrCode && data.qrCode.trim() !== '') {
                qrImg.src = data.qrCode;
                qrDownload.href = data.qrCode;
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
                receiptLink.href = data.receipt;
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
            document.getElementById('returnModalCarTitle').textContent = data.carModel + ' (' + data.carPlate + ')';

            const previewContainer = document.getElementById('returnModalPreviewContainer');
            const previewImg = document.getElementById('returnModalPreviewImg');
            const uploadBtnText = document.getElementById('returnModalUploadBtnText');

            if (data.returnImage && data.returnImage.trim() !== '') {
                previewImg.src = data.returnImage;
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