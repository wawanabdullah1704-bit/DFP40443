<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/notifications.php';

// Semak jika pengguna telah log masuk dan merupakan Penyedia Kereta (Provider)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'provider') {
    header("Location: ../index.php");
    exit();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/Exception.php';
require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';

$provider_id = $_SESSION['provider_id'];
$provider_name = $_SESSION['username'];
$message = "";

// 1. PROSES LULUSKAN TEMPAHAN (APPROVE)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['approve_booking'])) {
    $booking_id = (int)$_POST['booking_id'];

    $sql_app = "UPDATE bookings SET status = 'Approved' WHERE id = ? AND car_id IN (SELECT id FROM cars WHERE provider_id = ?)";
    $stmt_app = $conn->prepare($sql_app);
    $stmt_app->bind_param("ii", $booking_id, $provider_id);
    
    if ($stmt_app->execute()) {
        $message = "<div class='neo-alert alert-success'><i class='bi bi-check-circle-fill me-2'></i>Berjaya: Permohonan tempahan telah <strong>DILULUSKAN</strong>. Pelajar kini boleh melihat Kod QR untuk membuat pembayaran.</div>";

        // ========================================================
        // HANTAR E-MEL NOTIFIKASI KEPADA PELAJAR (STUDENT)
        // ========================================================
        $sql_stu = "SELECT s.id AS student_id, s.full_name AS student_name, s.email AS student_email,
                           c.car_brand, c.car_model, c.car_plate,
                           b.start_date, b.end_date, b.total_price, b.rent_type,
                           p.full_name AS provider_name, p.phone_no AS provider_phone
                    FROM bookings b
                    JOIN students s ON b.student_id = s.id
                    JOIN cars c ON b.car_id = c.id
                    JOIN providers p ON c.provider_id = p.id
                    WHERE b.id = ?";
        $stmt_s = $conn->prepare($sql_stu);
        $stmt_s->bind_param("i", $booking_id);
        $stmt_s->execute();
        $stu_data = $stmt_s->get_result()->fetch_assoc();
        $stmt_s->close();

        if ($stu_data) {
            // Notifikasi dalam sistem kepada pelajar
            create_notification(
                $conn,
                'student',
                $stu_data['student_id'],
                'Tempahan Diluluskan!',
                'Permohonan tempahan #' . $booking_id . ' (' . $stu_data['car_brand'] . ' ' . $stu_data['car_model'] . ') telah diluluskan. Sila muat naik resit pembayaran.',
                'student/my_bookings.php',
                'success'
            );
        }

        if ($stu_data && !empty($stu_data['student_email'])) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $host = $_SERVER['HTTP_HOST'];
            $root_uri = rtrim(dirname(dirname($_SERVER['PHP_SELF'])), '/\\');
            if ($root_uri === '/' || $root_uri === '\\') $root_uri = '';
            $student_page_link = $protocol . $host . $root_uri . "/student/my_bookings.php";

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'chickenmasterz26@gmail.com';
                $mail->Password   = 'pcccoszzikvwmzsd';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('chickenmasterz26@gmail.com', 'SCRS PMU');
                $mail->addAddress($stu_data['student_email'], $stu_data['student_name']);

                $mail->isHTML(true);
                $mail->Subject = "SCRS PMU - Tempahan Kereta Anda Telah DILULUSKAN! (#" . $booking_id . ")";
                
                $start_fmt = date("d/m/Y, h:i A", strtotime($stu_data['start_date']));
                $end_fmt = date("d/m/Y, h:i A", strtotime($stu_data['end_date']));
                $price_fmt = number_format($stu_data['total_price'], 2);

                $mail->Body = "
                <div style='font-family: Arial, sans-serif; background-color: #f4f4f0; padding: 25px;'>
                    <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border: 4px solid #000000; box-shadow: 6px 6px 0px #000000; padding: 25px;'>
                        <div style='background-color: #00e676; border: 3px solid #000; padding: 12px; margin-bottom: 20px; text-align: center;'>
                            <h2 style='margin: 0; text-transform: uppercase; font-weight: 900; color: #000;'>TEMPAHAN DILULUSKAN!</h2>
                        </div>
                        <p style='font-size: 1rem; color: #333;'>Tahniah <strong>" . htmlspecialchars($stu_data['student_name']) . "</strong>,</p>
                        <p style='color: #333;'>Permohonan sewaan kereta anda telah <strong>DILULUSKAN</strong> oleh Penyedia Kenderaan. Sila lakukan pembayaran sewaan melalui Kod QR DuitNow dan muat naik resit pembayaran di sistem untuk meneruskan pengambilan kereta.</p>
                        
                        <table style='width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 0.95rem;'>
                            <tr style='background: #f9f9f9; border-bottom: 2px solid #eee;'>
                                <td style='padding: 8px; font-weight: bold; width: 40%;'>ID Tempahan:</td>
                                <td style='padding: 8px;'>#" . $booking_id . "</td>
                            </tr>
                            <tr style='border-bottom: 2px solid #eee;'>
                                <td style='padding: 8px; font-weight: bold;'>Kenderaan:</td>
                                <td style='padding: 8px;'>" . htmlspecialchars($stu_data['car_brand']) . " " . htmlspecialchars($stu_data['car_model']) . " (" . htmlspecialchars($stu_data['car_plate']) . ")</td>
                            </tr>
                            <tr style='background: #f9f9f9; border-bottom: 2px solid #eee;'>
                                <td style='padding: 8px; font-weight: bold;'>Penyedia Kereta:</td>
                                <td style='padding: 8px;'>" . htmlspecialchars($stu_data['provider_name']) . " (" . htmlspecialchars($stu_data['provider_phone']) . ")</td>
                            </tr>
                            <tr style='border-bottom: 2px solid #eee;'>
                                <td style='padding: 8px; font-weight: bold;'>Tarikh Ambil:</td>
                                <td style='padding: 8px;'>" . $start_fmt . "</td>
                            </tr>
                            <tr style='background: #f9f9f9; border-bottom: 2px solid #eee;'>
                                <td style='padding: 8px; font-weight: bold;'>Tarikh Pulang:</td>
                                <td style='padding: 8px;'>" . $end_fmt . "</td>
                            </tr>
                            <tr style='border-bottom: 2px solid #eee;'>
                                <td style='padding: 8px; font-weight: bold;'>Jumlah Bayaran:</td>
                                <td style='padding: 8px; color: #007700; font-weight: bold;'>RM " . $price_fmt . " (" . $stu_data['rent_type'] . ")</td>
                            </tr>
                        </table>

                        <div style='text-align: center; margin: 25px 0;'>
                            <a href='" . $student_page_link . "' style='background-color: #ffde59; border: 3px solid #000; box-shadow: 4px 4px 0px #000; padding: 12px 24px; color: #000; font-weight: 900; text-decoration: none; text-transform: uppercase; display: inline-block;'>
                                Bayar & Muat Naik Resit Sekarang &rarr;
                            </a>
                        </div>

                        <p style='font-size: 0.85rem; color: #777; border-top: 2px dashed #ccc; padding-top: 10px;'>
                            E-mel ini dijana secara automatik oleh Sistem Sewaan Kereta PMU (SCRS PMU).
                        </p>
                    </div>
                </div>";

                $mail->send();
                $message .= "<div class='neo-alert alert-success mt-2'><i class='bi bi-envelope-check-fill me-2'></i>Notifikasi e-mel kelulusan telah dihantar kepada pelajar (<strong>" . htmlspecialchars($stu_data['student_email']) . "</strong>).</div>";
            } catch (Exception $e) {
                // E-mel gagal tapi status tetap dikemaskini
            }
        }
    } else {
        $message = "<div class='neo-alert alert-danger'>Ralat pangkalan data: " . $stmt_app->error . "</div>";
    }
    $stmt_app->close();
}

// 2. PROSES TOLAK TEMPAHAN (REJECT)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reject_booking'])) {
    $booking_id = (int)$_POST['booking_id'];

    // Dapatkan maklumat pelajar sebelum update
    $sql_stu_rej = "SELECT b.student_id, c.car_brand, c.car_model FROM bookings b JOIN cars c ON b.car_id = c.id WHERE b.id = ?";
    $stmt_sr = $conn->prepare($sql_stu_rej);
    $stmt_sr->bind_param("i", $booking_id);
    $stmt_sr->execute();
    $rej_data = $stmt_sr->get_result()->fetch_assoc();
    $stmt_sr->close();

    $sql_rej = "UPDATE bookings SET status = 'Rejected' WHERE id = ? AND car_id IN (SELECT id FROM cars WHERE provider_id = ?)";
    $stmt_rej = $conn->prepare($sql_rej);
    $stmt_rej->bind_param("ii", $booking_id, $provider_id);
    
    if ($stmt_rej->execute()) {
        $message = "<div class='neo-alert alert-danger'><i class='bi bi-x-circle-fill me-2'></i>Permohonan tempahan telah <strong>DITOLAK</strong>.</div>";
        if ($rej_data) {
            create_notification(
                $conn,
                'student',
                $rej_data['student_id'],
                'Tempahan Ditolak',
                'Permohonan tempahan #' . $booking_id . ' (' . $rej_data['car_brand'] . ' ' . $rej_data['car_model'] . ') telah ditolak oleh penyedia kenderaan.',
                'student/booking_history.php',
                'danger'
            );
        }
    } else {
        $message = "<div class='neo-alert alert-danger'>Ralat: " . $stmt_rej->error . "</div>";
    }
    $stmt_rej->close();
}

// 3. PROSES SAHKAN PEMULANGAN KERETA (COMPLETE)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['complete_booking'])) {
    $booking_id = (int)$_POST['booking_id'];

    // Dapatkan maklumat pelajar sebelum update
    $sql_stu_cmp = "SELECT b.student_id, c.car_brand, c.car_model FROM bookings b JOIN cars c ON b.car_id = c.id WHERE b.id = ?";
    $stmt_sc = $conn->prepare($sql_stu_cmp);
    $stmt_sc->bind_param("i", $booking_id);
    $stmt_sc->execute();
    $cmp_data = $stmt_sc->get_result()->fetch_assoc();
    $stmt_sc->close();

    $sql_comp = "UPDATE bookings SET status = 'Completed' WHERE id = ? AND car_id IN (SELECT id FROM cars WHERE provider_id = ?)";
    $stmt_comp = $conn->prepare($sql_comp);
    $stmt_comp->bind_param("ii", $booking_id, $provider_id);
    
    if ($stmt_comp->execute()) {
        $message = "<div class='neo-alert alert-success'><i class='bi bi-check-circle-fill me-2'></i>Berjaya: Tempahan telah ditandakan sebagai <strong>SELESAI (Completed)</strong>. Terima kasih!</div>";
        if ($cmp_data) {
            create_notification(
                $conn,
                'student',
                $cmp_data['student_id'],
                'Tempahan Selesai',
                'Tempahan #' . $booking_id . ' (' . $cmp_data['car_brand'] . ' ' . $cmp_data['car_model'] . ') telah disahkan selesai. Terima kasih!',
                'student/booking_history.php',
                'info'
            );
        }
    } else {
        $message = "<div class='neo-alert alert-danger'>Ralat: " . $stmt_comp->error . "</div>";
    }
    $stmt_comp->close();
}

// AMBIL TEMPAHAN SEMASA (PENDING & APPROVED)
$sql_bookings = "SELECT b.*, c.car_brand, c.car_model, c.car_plate, c.car_image, c.price_per_day, c.price_per_hour, c.seat_capacity, c.transmission,
                        s.username as student_username, s.full_name as student_name, s.email as student_email,
                        s.phone_no as student_phone, s.no_pendaftaran as student_matrix,
                        s.student_id_file as student_id_file, s.driving_license_file as student_license_file,
                        s.profile_picture as student_profile_pic
                 FROM bookings b
                 JOIN cars c ON b.car_id = c.id
                 JOIN students s ON b.student_id = s.id
                 WHERE c.provider_id = ? AND b.status IN ('Pending', 'Approved')
                 ORDER BY b.created_at DESC";

$stmt_b = $conn->prepare($sql_bookings);
$stmt_b->bind_param("i", $provider_id);
$stmt_b->execute();
$result_bookings = $stmt_b->get_result();

$pending_bookings = [];
$approved_bookings = [];

while ($row = $result_bookings->fetch_assoc()) {
    if ($row['status'] == 'Approved') {
        $approved_bookings[] = $row;
    } else {
        $pending_bookings[] = $row;
    }
}
$total_bookings_count = count($pending_bookings) + count($approved_bookings);

function renderBookingCardAndModal($booking) {
    $is_approved = ($booking['status'] == 'Approved');
    $badge_class = $is_approved ? 'badge-approved' : 'badge-pending';
    $status_text = $is_approved ? 'Diluluskan (Aktif)' : 'Menunggu Kelulusan';
    $status_icon = $is_approved ? 'bi-check-circle-fill' : 'bi-hourglass-split';
    $card_class = $is_approved ? 'card-approved' : 'card-pending';
    $car_display_name = (!empty($booking['car_brand']) ? htmlspecialchars($booking['car_brand']) . ' ' : '') . htmlspecialchars($booking['car_model']);
    $car_img_src = (strpos($booking['car_image'], 'http') === 0 || strpos($booking['car_image'], '../') === 0) ? htmlspecialchars($booking['car_image']) : '../' . htmlspecialchars($booking['car_image']);
    
    $phone = preg_replace('/[^0-9]/', '', $booking['student_phone']);
    if (strpos($phone, '0') === 0) {
        $phone = '6' . $phone;
    }
    $has_receipt = (!empty($booking['payment_receipt']) && file_exists(__DIR__ . '/../' . $booking['payment_receipt']));
    $has_return_img = (!empty($booking['return_image']) && file_exists(__DIR__ . '/../' . $booking['return_image']));
?>
    <!-- KAD TEMPAHAN KOMPAK (MESRA MOBILE) -->
    <div class="booking-card-compact <?php echo $card_class; ?>" onclick="openBookingDetailModal('bookingModal<?php echo $booking['id']; ?>')" title="Tekan untuk lihat maklumat penuh & tindakan">
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
                <?php if ($is_approved): ?>
                    <span style="font-size: 0.74rem; font-weight: 800; color: <?php echo $has_receipt ? '#15803d' : '#b45309'; ?>; display: inline-flex; align-items: center; gap: 3px;">
                        <i class="bi <?php echo $has_receipt ? 'bi-receipt-cutoff text-success' : 'bi-hourglass-split text-warning'; ?>"></i> Resit: <?php echo $has_receipt ? 'Dihantar' : 'Menunggu'; ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="compact-student">
                <i class="bi bi-person-fill text-primary"></i> <?php echo htmlspecialchars($booking['student_name']); ?>
            </div>
            <div class="compact-bottom">
                <span class="compact-price">RM <?php echo number_format($booking['total_price'], 2); ?></span>
                <?php if (!$is_approved): ?>
                    <span class="compact-detail-btn" style="background: var(--yellow);"><i class="bi bi-pencil-square me-1"></i> Tindakan <i class="bi bi-chevron-right ms-1"></i></span>
                <?php else: ?>
                    <span class="compact-detail-btn" style="background: #e0f2fe; color: #0284c7; border-color: var(--black);"><i class="bi bi-sliders me-1"></i> Butiran & Urus <i class="bi bi-chevron-right ms-1"></i></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- MODAL POPUP BUTIRAN LENGKAP TEMPAHAN & TINDAKAN PENYEDIA -->
    <div class="neo-modal-overlay booking-detail-modal-overlay" id="bookingModal<?php echo $booking['id']; ?>" onclick="if(event.target === this) closeBookingDetailModal('bookingModal<?php echo $booking['id']; ?>')">
        <div class="neo-modal" onclick="event.stopPropagation()" style="max-width: 480px; padding: 18px;">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid var(--black);">
                <h3 class="modal-title" style="font-weight: 900; font-size: 1.15rem; margin: 0; text-transform: uppercase;">
                    <?php echo $is_approved ? 'Urus Tempahan' : 'Semakan Permohonan'; ?> #<?php echo $booking['id']; ?>
                </h3>
                <button type="button" class="close-btn" style="background: none; border: none; font-size: 1.4rem; font-weight: 900; cursor: pointer; line-height: 1; padding: 0 4px;" onclick="closeBookingDetailModal('bookingModal<?php echo $booking['id']; ?>')">&times;</button>
            </div>

            <!-- BANNER PREVIEW KERETA -->
            <div class="modal-detail-banner">
                <img src="<?php echo $car_img_src; ?>" alt="<?php echo $car_display_name; ?>">
                <div style="flex: 1; min-width: 0;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 5px;">
                        <h4 style="font-weight: 900; text-transform: uppercase; margin: 0; font-size: 0.95rem; color: var(--black); line-height: 1.2;">
                            <?php echo $car_display_name; ?>
                        </h4>
                        <span class="badge-status <?php echo $badge_class; ?>" style="font-size: 0.8rem; flex-shrink: 0;">
                            <i class="bi <?php echo $status_icon; ?>"></i> <?php echo $status_text; ?>
                        </span>
                    </div>
                    <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                        <span style="background: var(--black); color: var(--white); font-size: 0.72rem; font-weight: 800; padding: 2px 6px; border-radius: 4px;"><?php echo htmlspecialchars($booking['car_plate']); ?></span>
                        <?php if (!empty($booking['transmission'])): ?>
                            <span class="modal-car-chip"><i class="bi bi-gear-fill"></i> <?php echo htmlspecialchars($booking['transmission']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($booking['seat_capacity'])): ?>
                            <span class="modal-car-chip"><i class="bi bi-people-fill"></i> <?php echo htmlspecialchars($booking['seat_capacity']); ?> Tempat</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- MAKLUMAT TERPERINCI -->
            <div class="modal-info-panel">
                <div class="modal-info-row">
                    <span class="info-lbl"><i class="bi bi-person-badge text-primary"></i> Pemohon (Pelajar):</span>
                    <a href="javascript:void(0)" onclick="showStudentModal('<?php echo htmlspecialchars($booking['student_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($booking['student_username'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($booking['student_email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($booking['student_phone'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($booking['student_matrix'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($booking['student_id_file'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($booking['student_license_file'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($booking['student_profile_pic'] ?? '', ENT_QUOTES); ?>')" style="color: #0055ff; font-weight: 900; text-decoration: underline; cursor: pointer;">
                        <?php echo htmlspecialchars($booking['student_name']); ?> <i class="bi bi-info-circle ms-1"></i>
                    </a>
                </div>
                <div class="modal-info-row">
                    <span class="info-lbl"><i class="bi bi-telephone text-success"></i> No. Telefon:</span>
                    <span class="info-val"><?php echo htmlspecialchars($booking['student_phone']); ?></span>
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
                    <span class="info-lbl"><i class="bi bi-hourglass-split text-warning"></i> Jenis Sewaan:</span>
                    <span class="info-val">
                        <?php echo ($booking['rent_type'] == 'Daily') ? 'Harian (Daily)' : 'Jam (Hourly)'; ?>
                    </span>
                </div>
            </div>

            <!-- JUMLAH BAYARAN -->
            <div class="modal-price-box">
                <span style="font-size: 0.95rem; text-transform: uppercase;">Jumlah Bayaran:</span>
                <span style="font-size: 1.25rem; color: #006400;">RM <?php echo number_format($booking['total_price'], 2); ?></span>
            </div>

            <!-- TINDAKAN PENYEDIA -->
            <div>
                <?php if (!$is_approved): ?>
                    <!-- STATUS PENDING: LULUS / TOLAK -->
                    <div style="background: #fffbeb; border: 2px solid #f59e0b; border-radius: var(--radius-md); padding: 10px; font-weight: 800; font-size: 0.82rem; color: #92400e; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-hourglass-split"></i>
                        <span>Permohonan baharu memerlukan tindakan kelulusan anda.</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 10px;">
                        <form action="" method="POST" style="margin: 0;" onsubmit="return confirm('Adakah anda pasti ingin meluluskan tempahan ini?');">
                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                            <button type="submit" name="approve_booking" class="neo-btn btn-green" style="width: 100%; justify-content: center; padding: 10px 6px; font-size: 0.85rem;">
                                <i class="bi bi-check-circle-fill me-1"></i> Luluskan
                            </button>
                        </form>

                        <form action="" method="POST" style="margin: 0;" onsubmit="return confirm('Adakah anda pasti ingin menolak tempahan ini?');">
                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                            <button type="submit" name="reject_booking" class="neo-btn btn-pink" style="width: 100%; justify-content: center; padding: 10px 6px; font-size: 0.85rem;">
                                <i class="bi bi-x-circle-fill me-1"></i> Tolak
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- STATUS APPROVED: RESIT, GAMBAR & PEMULANGAN -->
                    
                    <!-- BUKTI PEMBAYARAN -->
                    <div class="modal-doc-status-box" style="background: <?php echo $has_receipt ? '#f0fdf4' : '#fffbeb'; ?>; border-color: <?php echo $has_receipt ? '#16a34a' : '#d97706'; ?>; display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                        <div>
                            <span style="font-size: 0.74rem; font-weight: 800; color: #475569; text-transform: uppercase; display: block;">Bukti Pembayaran Pelajar:</span>
                            <?php if ($has_receipt): ?>
                                <span style="color: #15803d; font-weight: 800; font-size: 0.82rem;"><i class="bi bi-check-circle-fill me-1"></i> Resit telah dihantar</span>
                            <?php else: ?>
                                <span style="color: #b45309; font-weight: 800; font-size: 0.82rem;"><i class="bi bi-hourglass-split me-1"></i> Menunggu muat naik resit</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($has_receipt): ?>
                            <a href="../<?php echo htmlspecialchars($booking['payment_receipt']); ?>" target="_blank" class="neo-btn btn-blue" style="padding: 4px 10px; font-size: 0.78rem;">
                                <i class="bi bi-receipt me-1"></i> Lihat Resit
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- GAMBAR PEMULANGAN -->
                    <div class="modal-doc-status-box" style="background: <?php echo $has_return_img ? '#f0fdf4' : '#f8fafc'; ?>; border-color: <?php echo $has_return_img ? '#16a34a' : '#cbd5e1'; ?>; display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                        <div>
                            <span style="font-size: 0.74rem; font-weight: 800; color: #475569; text-transform: uppercase; display: block;">Gambar Pemulangan Kereta:</span>
                            <?php if ($has_return_img): ?>
                                <span style="color: #15803d; font-weight: 800; font-size: 0.82rem;"><i class="bi bi-check-circle-fill me-1"></i> Gambar telah dimuat naik</span>
                            <?php else: ?>
                                <span style="color: #64748b; font-weight: 800; font-size: 0.82rem;"><i class="bi bi-clock me-1"></i> Sedang dalam penggunaan</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($has_return_img): ?>
                            <a href="../<?php echo htmlspecialchars($booking['return_image']); ?>" target="_blank" class="neo-btn btn-yellow" style="padding: 4px 10px; font-size: 0.78rem;">
                                <i class="bi bi-image me-1"></i> Lihat Gambar
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- WHATSAPP & SAHKAN SELESAI -->
                    <a href="https://wa.me/<?php echo $phone; ?>?text=Hai%20<?php echo urlencode($booking['student_name']); ?>,%20saya%20penyedia%20kereta%20<?php echo urlencode($car_display_name); ?>%20SCRS%20PMU." target="_blank" class="neo-btn btn-green" style="width: 100%; justify-content: center; font-size: 0.88rem; padding: 10px; margin-bottom: 8px;">
                        <i class="bi bi-whatsapp me-2"></i> Hubungi Pelajar (WhatsApp)
                    </a>

                    <form action="" method="POST" style="margin: 0; width: 100%;" onsubmit="return confirm('Adakah anda pasti ingin menandakan tempahan ini sebagai SELESAI?');">
                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                        <button type="submit" name="complete_booking" class="neo-btn btn-blue" style="width: 100%; justify-content: center; font-size: 0.88rem; padding: 10px;">
                            <i class="bi bi-check-all me-1"></i> Sahkan Pemulangan / Selesai
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- TUTUP MODAL -->
            <div style="margin-top: 10px;">
                <button type="button" class="neo-btn" style="width: 100%; justify-content: center; background: #fff; font-size: 0.85rem; padding: 8px;" onclick="closeBookingDetailModal('bookingModal<?php echo $booking['id']; ?>')">
                    Tutup
                </button>
            </div>
        </div>
    </div>
<?php
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Tempahan Semasa - SCRS PMU</title>
    
    <!-- Ikon Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Master Neo-Brutalism CSS -->
    <link rel="stylesheet" href="../assets/css/neo-style.css">

    <style>
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
        .compact-student {
            font-size: 0.78rem;
            font-weight: 800;
            color: #475569;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .compact-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-top: 2px;
            padding-top: 4px;
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
        .modal-doc-status-box {
            border-radius: var(--radius-md);
            padding: 10px 12px;
            margin-bottom: 10px;
            border: 2px solid var(--black);
        }

        /* Filter Tabs */
        .booking-filter-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 18px;
            overflow-x: auto;
            padding-bottom: 4px;
        }
        .filter-tab-btn {
            padding: 7px 14px;
            font-size: 0.82rem;
            font-weight: 800;
            border: 2px solid var(--black);
            box-shadow: 2px 2px 0px var(--black);
            border-radius: var(--radius-full);
            background: var(--white);
            color: var(--black);
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .filter-tab-btn:hover {
            transform: translate(-1px, -1px);
            box-shadow: 3px 3px 0px var(--black);
        }
        .filter-tab-btn.active {
            background: var(--black);
            color: var(--white);
            box-shadow: none;
            transform: translate(2px, 2px);
        }
        .filter-tab-btn.active .tab-count {
            background: var(--yellow);
            color: var(--black);
        }
        .tab-count {
            background: #e2e8f0;
            color: #334155;
            font-size: 0.72rem;
            font-weight: 800;
            padding: 1px 7px;
            border-radius: 10px;
        }

        /* Seksyen Heading */
        .booking-section {
            margin-bottom: 24px;
        }
        .booking-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 6px;
            border-bottom: 2px solid #e2e8f0;
        }
        .section-heading-title {
            font-size: 0.98rem;
            font-weight: 900;
            text-transform: uppercase;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--black);
        }
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }
        .status-dot-pending {
            background: #f59e0b;
        }
        .status-dot-approved {
            background: #10b981;
        }
        .section-count-badge {
            font-size: 0.75rem;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 12px;
        }
        .count-badge-pending {
            background: #fef3c7;
            color: #92400e;
        }
        .count-badge-approved {
            background: #dcfce7;
            color: #166534;
        }

        /* Aksen Kad Mengikut Status */
        .card-pending {
            border-left: 6px solid #f59e0b;
        }
        .card-approved {
            border-left: 6px solid #10b981;
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
            .modal-detail-banner img { width: 85px; }
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
            <a href="provider_bookings.php" class="sidebar-link active"><i class="bi bi-clipboard-check-fill"></i> Senarai Permohonan</a>
            <a href="provider_history.php" class="sidebar-link"><i class="bi bi-clock-history"></i> Rekod Tempahan</a>
        </nav>
    </aside>

    <!-- KANDUNGAN UTAMA -->
    <main class="main-content">
        
        <?php echo $message; ?>

        <!-- HEADING PANDUAN PENGGUNA (DENGAN BUTANG KEMBALI) -->
        <div style="margin-bottom: 25px;">
            <div class="page-title-row" style="margin-bottom: 8px;">
                <a href="provider_dashboard.php" class="neo-btn btn-yellow btn-arrow-back" title="Papan Pemuka" aria-label="Kembali ke Papan Pemuka">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h1 style="font-size: 1.55rem; font-weight: 900; text-transform: uppercase; margin: 0; color: var(--black);">
                    Senarai Permohonan & Tempahan Semasa
                </h1>
            </div>
            <p style="font-weight: 700; color: #555; font-size: 0.92rem; margin: 0; line-height: 1.5;">
                Semak permohonan tempahan kenderaan yang masuk daripada pelajar. Anda boleh meluluskan atau menolak permohonan, menyemak resit bayaran, dan mengesahkan pemulangan kenderaan.
            </p>
        </div>

        <?php if ($total_bookings_count > 0): ?>
            <!-- FILTER TABS PENYEDIA -->
            <div class="booking-filter-tabs">
                <button type="button" class="filter-tab-btn active" id="tab-all" onclick="setBookingFilter('all')">
                    Semua <span class="tab-count"><?php echo $total_bookings_count; ?></span>
                </button>
                <button type="button" class="filter-tab-btn" id="tab-pending" onclick="setBookingFilter('pending')">
                    <span class="status-dot status-dot-pending"></span>
                    Menunggu Kelulusan <span class="tab-count"><?php echo count($pending_bookings); ?></span>
                </button>
                <button type="button" class="filter-tab-btn" id="tab-approved" onclick="setBookingFilter('approved')">
                    <span class="status-dot status-dot-approved"></span>
                    Diluluskan <span class="tab-count"><?php echo count($approved_bookings); ?></span>
                </button>
            </div>

            <!-- SEKSYEN 1: MENUNGGU KELULUSAN -->
            <section class="booking-section" id="section-pending">
                <div class="booking-section-header">
                    <h3 class="section-heading-title">
                        <span class="status-dot status-dot-pending"></span>
                        Menunggu Kelulusan Anda
                        <span class="section-count-badge count-badge-pending"><?php echo count($pending_bookings); ?> Permohonan</span>
                    </h3>
                </div>
                <?php if (count($pending_bookings) > 0): ?>
                    <?php foreach ($pending_bookings as $b): ?>
                        <?php renderBookingCardAndModal($b); ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="background: #fff; border: 2px dashed #cbd5e1; border-radius: var(--radius-md); padding: 16px; text-align: center; color: #64748b; font-weight: 700; font-size: 0.85rem; margin-bottom: 15px;">
                        <i class="bi bi-check2-circle text-success me-1"></i> Tiada permohonan baharu yang menunggu kelulusan anda.
                    </div>
                <?php endif; ?>
            </section>

            <!-- SEKSYEN 2: TEMPAHAN DILULUSKAN / AKTIF -->
            <section class="booking-section" id="section-approved">
                <div class="booking-section-header">
                    <h3 class="section-heading-title">
                        <span class="status-dot status-dot-approved"></span>
                        Tempahan Diluluskan & Aktif
                        <span class="section-count-badge count-badge-approved"><?php echo count($approved_bookings); ?> Tempahan</span>
                    </h3>
                </div>
                <?php if (count($approved_bookings) > 0): ?>
                    <?php foreach ($approved_bookings as $b): ?>
                        <?php renderBookingCardAndModal($b); ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="background: #fff; border: 2px dashed #cbd5e1; border-radius: var(--radius-md); padding: 16px; text-align: center; color: #64748b; font-weight: 700; font-size: 0.85rem; margin-bottom: 15px;">
                        <i class="bi bi-info-circle me-1"></i> Tiada tempahan yang sedang aktif pada masa ini.
                    </div>
                <?php endif; ?>
            </section>

        <?php else: ?>
            <div class="empty-box">
                <i class="bi bi-clipboard-check"></i>
                <h2 style="font-weight: 900; text-transform: uppercase;">Tiada Tempahan Semasa</h2>
                <p style="font-weight: 700; color: #666; margin: 10px 0 20px 0;">Tiada permohonan tempahan baharu atau aktif pada masa ini.</p>
                <a href="provider_cars.php" class="neo-btn btn-green">
                    <i class="bi bi-car-front-fill me-1"></i> Lihat Senarai Kereta Anda
                </a>
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
                        <a id="modalStudentIdDoc" href="" target="_blank" class="neo-btn btn-sm btn-yellow" style="display: inline-flex; align-items: center; padding: 3px 8px; font-size: 0.75rem; text-decoration: none;"><i class="bi bi-file-earmark-image me-1"></i>Lihat Dokumen</a>
                        <span id="modalStudentNoIdDoc" style="color: #999; display: none;">Tiada Fail</span>
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 8px 0; align-items: center;">
                    <span style="color: #666;">Lesen Memandu:</span>
                    <span>
                        <a id="modalStudentLicenseDoc" href="" target="_blank" class="neo-btn btn-sm btn-green" style="display: inline-flex; align-items: center; padding: 3px 8px; font-size: 0.75rem; text-decoration: none;"><i class="bi bi-file-earmark-image me-1"></i>Lihat Lesen</a>
                        <span id="modalStudentNoLicenseDoc" style="color: #999; display: none;">Tiada Fail</span>
                    </span>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <button type="button" class="neo-btn btn-white" style="width: 100%; justify-content: center;" onclick="closeStudentModal()"><i class="bi bi-arrow-left-short me-1"></i>Tutup</button>
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

        // FILTER TAB PENYEDIA
        function setBookingFilter(type) {
            const tabs = document.querySelectorAll('.filter-tab-btn');
            tabs.forEach(tab => tab.classList.remove('active'));

            const activeTab = document.getElementById('tab-' + type);
            if (activeTab) activeTab.classList.add('active');

            const pendingSection = document.getElementById('section-pending');
            const approvedSection = document.getElementById('section-approved');

            if (type === 'all') {
                if (pendingSection) pendingSection.style.display = 'block';
                if (approvedSection) approvedSection.style.display = 'block';
            } else if (type === 'pending') {
                if (pendingSection) pendingSection.style.display = 'block';
                if (approvedSection) approvedSection.style.display = 'none';
            } else if (type === 'approved') {
                if (pendingSection) pendingSection.style.display = 'none';
                if (approvedSection) approvedSection.style.display = 'block';
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
                idElem.style.display = 'inline-flex';
                noIdElem.style.display = 'none';
            } else {
                idElem.style.display = 'none';
                noIdElem.style.display = 'inline-block';
            }
            
            const licElem = document.getElementById('modalStudentLicenseDoc');
            const noLicElem = document.getElementById('modalStudentNoLicenseDoc');
            if (licenseDoc && licenseDoc.trim() !== '') {
                licElem.href = (licenseDoc.startsWith('http') || licenseDoc.startsWith('../')) ? licenseDoc : ('../' + licenseDoc);
                licElem.style.display = 'inline-flex';
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
