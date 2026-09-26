<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/notifications.php';

// Semak jika pengguna telah log masuk dan merupakan seorang pelajar
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/Exception.php';
require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';

$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['username'];
$message = "";

// ========================================================
// 1. PROSES PENGHANTARAN TEMPAHAN (POST)
// ========================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_booking'])) {
    $car_id = (int)$_POST['car_id'];
    $rent_type = htmlspecialchars($_POST['rent_type'] ?? 'Daily');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $total_price = (float)($_POST['total_price'] ?? 0);
    $status = 'Pending';

    // Semak jika pelajar sudah mempunyai tempahan aktif pada waktu bertindih
    $sql_check_student = "SELECT id FROM bookings 
                          WHERE student_id = ? 
                          AND status IN ('Pending', 'Approved') 
                          AND (start_date < ? AND end_date > ?)";
    $stmt_check = $conn->prepare($sql_check_student);
    $stmt_check->bind_param("iss", $student_id, $end_date, $start_date);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    if ($res_check->num_rows > 0) {
        $message = "<div class='neo-alert alert-danger'><i class='bi bi-exclamation-octagon-fill me-2'></i>Ralat: Anda sudah mempunyai tempahan aktif (Pending/Approved) pada waktu yang dipilih. Setiap pelajar hanya dibenarkan menyewa 1 kereta dalam satu masa sahaja.</div>";
        $stmt_check->close();
    } else {
        $stmt_check->close();
        
        $sql_book = "INSERT INTO bookings (student_id, car_id, rent_type, start_date, end_date, total_price, status) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_book = $conn->prepare($sql_book);
        $stmt_book->bind_param("iisssds", $student_id, $car_id, $rent_type, $start_date, $end_date, $total_price, $status);

        if ($stmt_book->execute()) {
            $new_booking_id = $stmt_book->insert_id;

            // HANTAR E-MEL & NOTIFIKASI SISTEM KEPADA PENYEDIA KERETA
            $sql_prov = "SELECT p.id AS provider_id, p.full_name AS provider_name, p.email AS provider_email, 
                                c.car_brand, c.car_model, c.car_plate,
                                s.full_name AS student_full_name, s.no_pendaftaran, s.phone_no AS student_phone
                         FROM cars c 
                         JOIN providers p ON c.provider_id = p.id 
                         JOIN students s ON s.id = ?
                         WHERE c.id = ?";
            $stmt_p = $conn->prepare($sql_prov);
            $stmt_p->bind_param("ii", $student_id, $car_id);
            $stmt_p->execute();
            $prov_data = $stmt_p->get_result()->fetch_assoc();
            $stmt_p->close();

            if ($prov_data) {
                create_notification($conn, 'provider', $prov_data['provider_id'], 'Tempahan Baharu #' . $new_booking_id, $prov_data['student_full_name'] . ' telah membuat tempahan bagi kereta ' . $prov_data['car_brand'] . ' ' . $prov_data['car_model'] . ' (' . $prov_data['car_plate'] . ').', '../provider/provider_bookings.php', 'info');
                create_notification($conn, 'student', $student_id, 'Tempahan Dihantar #' . $new_booking_id, 'Permohonan tempahan untuk ' . $prov_data['car_brand'] . ' ' . $prov_data['car_model'] . ' sedang menunggu kelulusan penyedia.', 'my_bookings.php', 'info');
            }

            if ($prov_data && !empty($prov_data['provider_email'])) {
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                $host = $_SERVER['HTTP_HOST'];
                $root_uri = rtrim(dirname(dirname($_SERVER['PHP_SELF'])), '/\\');
                if ($root_uri === '/' || $root_uri === '\\') $root_uri = '';
                $provider_page_link = $protocol . $host . $root_uri . "/provider/provider_bookings.php";

                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'scrspmu@gmail.com';
                    $mail->Password   = 'cnpriksgpjbbldvj';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom('scrspmu@gmail.com', 'SCRS PMU');
                    $mail->addAddress($prov_data['provider_email'], $prov_data['provider_name']);

                    $mail->isHTML(true);
                    $mail->Subject = "SCRS PMU - Permohonan Tempahan Kereta Baharu (#" . $new_booking_id . ")";
                    
                    $start_fmt = date("d/m/Y, h:i A", strtotime($start_date));
                    $end_fmt = date("d/m/Y, h:i A", strtotime($end_date));
                    $price_fmt = number_format($total_price, 2);

                    $mail->Body = "
                    <div style='font-family: Arial, sans-serif; background-color: #f4f4f0; padding: 25px;'>
                        <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border: 4px solid #000000; box-shadow: 6px 6px 0px #000000; padding: 25px;'>
                            <div style='background-color: #ffde59; border: 3px solid #000; padding: 12px; margin-bottom: 20px; text-align: center;'>
                                <h2 style='margin: 0; text-transform: uppercase; font-weight: 900; color: #000;'>SCRS PMU - TEMPAHAN BAHARU</h2>
                            </div>
                            <p style='font-size: 1rem; color: #333;'>Salam <strong>" . htmlspecialchars($prov_data['provider_name']) . "</strong>,</p>
                            <p style='color: #333;'>Seorang pelajar telah menghantar permohonan sewaan kenderaan anda. Butiran tempahan adalah seperti berikut:</p>
                            
                            <table style='width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 0.95rem;'>
                                <tr style='background: #f9f9f9; border-bottom: 2px solid #eee;'>
                                    <td style='padding: 8px; font-weight: bold; width: 40%;'>ID Tempahan:</td>
                                    <td style='padding: 8px;'>#" . $new_booking_id . "</td>
                                </tr>
                                <tr style='border-bottom: 2px solid #eee;'>
                                    <td style='padding: 8px; font-weight: bold;'>Kenderaan:</td>
                                    <td style='padding: 8px;'>" . htmlspecialchars($prov_data['car_brand']) . " " . htmlspecialchars($prov_data['car_model']) . " (" . htmlspecialchars($prov_data['car_plate']) . ")</td>
                                </tr>
                                <tr style='background: #f9f9f9; border-bottom: 2px solid #eee;'>
                                    <td style='padding: 8px; font-weight: bold;'>Nama Pelajar:</td>
                                    <td style='padding: 8px;'>#" . htmlspecialchars($prov_data['student_full_name']) . " (" . htmlspecialchars($prov_data['no_pendaftaran']) . ")</td>
                                </tr>
                                <tr style='border-bottom: 2px solid #eee;'>
                                    <td style='padding: 8px; font-weight: bold;'>No Telefon Pelajar:</td>
                                    <td style='padding: 8px;'>" . htmlspecialchars($prov_data['student_phone']) . "</td>
                                </tr>
                                <tr style='background: #f9f9f9; border-bottom: 2px solid #eee;'>
                                    <td style='padding: 8px; font-weight: bold;'>Tarikh Ambil:</td>
                                    <td style='padding: 8px;'>" . $start_fmt . "</td>
                                </tr>
                                <tr style='border-bottom: 2px solid #eee;'>
                                    <td style='padding: 8px; font-weight: bold;'>Tarikh Pulang:</td>
                                    <td style='padding: 8px;'>" . $end_fmt . "</td>
                                </tr>
                                <tr style='background: #f9f9f9; border-bottom: 2px solid #eee;'>
                                    <td style='padding: 8px; font-weight: bold;'>Jenis Sewaan & Jumlah:</td>
                                    <td style='padding: 8px; color: #007700; font-weight: bold;'>" . $rent_type . " - RM " . $price_fmt . "</td>
                                </tr>
                            </table>

                            <div style='text-align: center; margin: 25px 0;'>
                                <a href='" . $provider_page_link . "' style='background-color: #00e676; border: 3px solid #000; box-shadow: 4px 4px 0px #000; padding: 12px 24px; color: #000; font-weight: 900; text-decoration: none; text-transform: uppercase; display: inline-block;'>
                                    Urus & Luluskan Tempahan Di Sini &rarr;
                                </a>
                            </div>

                            <p style='font-size: 0.85rem; color: #777; border-top: 2px dashed #ccc; padding-top: 10px;'>
                                E-mel ini dijana secara automatik oleh Sistem Sewaan Kereta PMU (SCRS PMU).
                            </p>
                        </div>
                    </div>";

                    $mail->send();
                } catch (Exception $e) {
                    // Abaikan ralat e-mel jika gagal
                }
            }

            // Simpan mesej ke sesi dan halakan ke my_bookings.php
            $_SESSION['flash_msg'] = "<div class='neo-alert alert-success mb-3'><i class='bi bi-check-circle-fill me-2'></i>Permohonan tempahan kenderaan berjaya dihantar! Sila tunggu kelulusan daripada Penyedia Kereta.</div>";
            header("Location: my_bookings.php");
            exit();
        } else {
            $message = "<div class='neo-alert alert-danger mb-3'>Ralat pangkalan data: " . $stmt_book->error . "</div>";
        }
        $stmt_book->close();
    }
}

// ========================================================
// 2. PARAMETER CARIAN & KELAYAKAN (GET)
// ========================================================
$search_start = $_GET['start_date'] ?? '';
$search_end = $_GET['end_date'] ?? '';
$rent_type = $_GET['rent_type'] ?? '';

// Sekiranya parameter tidak lengkap, halakan semula ke Langkah 1 (booking.php)
if (empty($search_start) || empty($search_end) || empty($rent_type)) {
    header("Location: booking.php");
    exit();
}

$start_ts = strtotime($search_start);
$end_ts = strtotime($search_end);

if ($start_ts === false || $end_ts === false || $start_ts >= $end_ts) {
    $_SESSION['flash_msg'] = "<div class='neo-alert alert-danger mb-3'>Tarikh/masa pemulangan mestilah selepas tarikh/masa pengambilan.</div>";
    header("Location: booking.php");
    exit();
}

// Semak jika pelajar sudah mempunyai tempahan aktif yang bertindih
$has_student_conflict = false;
$sql_check_student = "SELECT id FROM bookings 
                      WHERE student_id = ? 
                      AND status IN ('Pending', 'Approved') 
                      AND (start_date < ? AND end_date > ?)";
$stmt_check = $conn->prepare($sql_check_student);
$stmt_check->bind_param("iss", $student_id, $search_end, $search_start);
$stmt_check->execute();
$res_check = $stmt_check->get_result();
if ($res_check->num_rows > 0) {
    $has_student_conflict = true;
}
$stmt_check->close();

// Kira tempoh masa sewaan
if ($rent_type === 'Hourly') {
    $duration = ceil(($end_ts - $start_ts) / 3600); // Jam
    if ($duration < 1) $duration = 1;
} else {
    $duration = ceil(($end_ts - $start_ts) / 86400); // Hari
    if ($duration < 1) $duration = 1;
}

// Fungsi mendapatkan senarai kenderaan tersedia mengikut tapisan
function get_available_cars($conn, $search_start, $search_end, $rent_type, $keyword = '', $transmission = 'all', $seats = 'all', $sort = 'default') {
    $sql = "SELECT c.*, 
                   p.username AS provider_username, 
                   p.email AS provider_email, 
                   p.phone_no AS provider_phone, 
                   p.roadtax_file AS provider_roadtax, 
                   p.insurance_file AS provider_insurance, 
                   p.profile_picture AS provider_profile_picture, 
                   p.qr_code_image AS provider_qr_code,
                   p.full_name AS provider_name 
            FROM cars c 
            JOIN providers p ON c.provider_id = p.id
            WHERE c.status = 'Available' 
            AND c.id NOT IN (
                SELECT car_id FROM bookings 
                WHERE status IN ('Pending', 'Approved') 
                AND (start_date < ? AND end_date > ?)
            )";
            
    $params = [$search_end, $search_start];
    $types = "ss";

    // Filter Kata Kunci (Brand, Model, Plate, Provider - Sokong Kata Kunci Bergabung & Tanpa Jarak)
    if (!empty($keyword)) {
        $words = preg_split('/\s+/', trim($keyword));
        foreach ($words as $word) {
            if ($word !== '') {
                $word_kw = '%' . $word . '%';
                $word_nospace = '%' . str_replace(' ', '', $word) . '%';
                $sql .= " AND (
                    CONCAT_WS(' ', c.car_brand, c.car_model) LIKE ? 
                    OR c.car_plate LIKE ? 
                    OR REPLACE(c.car_plate, ' ', '') LIKE ? 
                    OR p.full_name LIKE ? 
                    OR p.username LIKE ?
                )";
                $params[] = $word_kw;
                $params[] = $word_kw;
                $params[] = $word_nospace;
                $params[] = $word_kw;
                $params[] = $word_kw;
                $types .= "sssss";
            }
        }
    }

    // Filter Transmisi
    if ($transmission === 'Auto' || $transmission === 'Manual') {
        $sql .= " AND c.transmission = ?";
        $params[] = $transmission;
        $types .= "s";
    }

    // Filter Bilangan Tempat Duduk
    if ($seats === '4') {
        $sql .= " AND c.seat_capacity = 4";
    } elseif ($seats === '5') {
        $sql .= " AND c.seat_capacity = 5";
    } elseif ($seats === '7') {
        $sql .= " AND c.seat_capacity >= 7";
    }

    // Susunan Harga / Lalai
    $price_col = ($rent_type === 'Daily') ? 'c.price_per_day' : 'c.price_per_hour';
    if ($sort === 'price_asc') {
        $sql .= " ORDER BY {$price_col} ASC";
    } elseif ($sort === 'price_desc') {
        $sql .= " ORDER BY {$price_col} DESC";
    } else {
        $sql .= " ORDER BY c.created_at DESC";
    }

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result();
}

// Fungsi menjana HTML kad kenderaan dan modal popup
function render_car_cards_html($result_cars, $rent_type, $duration, $search_start, $search_end, $start_ts, $end_ts) {
    ob_start();
    if (!$result_cars || $result_cars->num_rows == 0) {
        ?>
        <div class="no-cars-box">
            <i class="bi bi-car-front" style="font-size: 3.5rem; color: #777;"></i>
            <h3 style="font-weight: 900; text-transform: uppercase; margin-top: 12px;">Tiada Kereta Ditemui</h3>
            <p style="font-weight: 700; color: #555; max-width: 480px; margin: 8px auto 0 auto; font-size: 0.95rem;">
                Tiada kenderaan yang sepadan dengan kriteria carian atau penapis anda. Sila cuba tukar kata kunci atau pilih penapis lain.
            </p>
        </div>
        <?php
    } else {
        ?>
        <div class="cars-grid">
            <?php while ($car = $result_cars->fetch_assoc()): 
                $price_rate = ($rent_type === 'Daily') ? $car['price_per_day'] : $car['price_per_hour'];
                $total_calc_price = $duration * $price_rate;
                $car_display_name = (!empty($car['car_brand']) ? htmlspecialchars($car['car_brand']) . ' ' : '') . htmlspecialchars($car['car_model']);
                $car_img_src = (strpos($car['car_image'], 'http') === 0 || strpos($car['car_image'], '../') === 0) ? htmlspecialchars($car['car_image']) : '../' . htmlspecialchars($car['car_image']);
            ?>
                <!-- KAD KERETA -->
                <div class="car-card" onclick="openModal('modal<?php echo $car['id']; ?>')" title="Tekan untuk lihat maklumat & tempah">
                    <div class="car-thumb-wrap">
                        <img src="<?php echo $car_img_src; ?>" class="car-img" alt="<?php echo $car_display_name; ?>" loading="lazy">
                        <span class="car-rate-pill">RM <?php echo number_format($price_rate, 2); ?> / <?php echo ($rent_type === 'Daily') ? 'Hari' : 'Jam'; ?></span>
                    </div>
                    
                    <div class="car-body">
                        <div class="car-title-wrap">
                            <h3 class="car-name"><?php echo $car_display_name; ?></h3>
                            <span class="car-plate-pill"><?php echo htmlspecialchars($car['car_plate']); ?></span>
                        </div>

                        <div class="car-specs-row">
                            <span class="car-spec-item"><i class="bi bi-gear-fill"></i> <?php echo htmlspecialchars($car['transmission']); ?></span>
                            <span class="car-spec-dot">•</span>
                            <span class="car-spec-item"><i class="bi bi-people-fill"></i> <?php echo htmlspecialchars($car['seat_capacity']); ?> Tempat</span>
                        </div>

                        <!-- MAKLUMAT PENYEDIA KERETA -->
                        <div class="car-provider-box">
                            <span class="provider-lbl"><i class="bi bi-person-badge-fill text-primary"></i> Penyedia:</span>
                            <a href="javascript:void(0)" onclick="event.stopPropagation(); showProviderModal('<?php echo htmlspecialchars($car['provider_username'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($car['provider_email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($car['provider_phone'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars(!empty($car['roadtax_file']) ? $car['roadtax_file'] : ($car['provider_roadtax'] ?? ''), ENT_QUOTES); ?>', '<?php echo htmlspecialchars(!empty($car['insurance_file']) ? $car['insurance_file'] : ($car['provider_insurance'] ?? ''), ENT_QUOTES); ?>', '<?php echo htmlspecialchars($car['provider_profile_picture'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($car['provider_qr_code'] ?? '', ENT_QUOTES); ?>')" class="provider-link">
                                <?php echo htmlspecialchars($car['provider_name']); ?> <i class="bi bi-info-circle-fill ms-1"></i>
                            </a>
                        </div>

                        <!-- KOTAK JUMLAH ANGGARAN (KADAR HARIAN DI ATAS SAHAJA) -->
                        <div class="car-price-box">
                            <span class="price-total-lbl">Jumlah Anggaran:</span>
                            <span class="price-total-val">RM <?php echo number_format($total_calc_price, 2); ?></span>
                        </div>

                        <!-- BUTANG TINDAKAN -->
                        <button type="button" class="neo-btn btn-green car-action-btn" onclick="event.stopPropagation(); openModal('modal<?php echo $car['id']; ?>')">
                            Tempah Sekarang
                        </button>
                    </div>
                </div>

                <!-- MODAL POPUP MAKLUMAT LENGKAP & PENGESAHAN TEMPAHAN -->
                <div class="neo-modal-overlay" id="modal<?php echo $car['id']; ?>" onclick="if(event.target === this) closeModal('modal<?php echo $car['id']; ?>')">
                    <div class="neo-modal" onclick="event.stopPropagation()" style="max-width: 520px;">
                        <div class="modal-header">
                            <h3 class="modal-title" style="font-weight: 900; font-size: 1.15rem; margin: 0;">
                                <i class="bi bi-file-earmark-check-fill me-1"></i> Butiran & Pengesahan
                            </h3>
                            <button type="button" class="close-btn" style="background: none; border: none; font-size: 1.4rem; cursor: pointer; line-height: 1;" onclick="closeModal('modal<?php echo $car['id']; ?>')">&times;</button>
                        </div>

                        <!-- RINGKASAN KERETA DALAM MODAL -->
                        <div class="modal-car-preview" style="display: flex; gap: 12px; background: #f8fafc; border: 2px solid var(--black); border-radius: var(--radius-md); padding: 10px; margin-bottom: 14px; align-items: center;">
                            <img src="<?php echo $car_img_src; ?>" style="width: 96px; aspect-ratio: 16 / 9; object-fit: cover; border-radius: var(--radius-sm); border: 2px solid var(--black); flex-shrink: 0;" alt="Kereta">
                            <div style="flex: 1; min-width: 0;">
                                <h4 style="font-weight: 900; text-transform: uppercase; margin: 0 0 4px 0; font-size: 0.95rem; color: var(--black); line-height: 1.2;">
                                    <?php echo $car_display_name; ?>
                                </h4>
                                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                    <span style="background: #000; color: #fff; font-size: 0.72rem; font-weight: 800; padding: 2px 6px; border-radius: 4px;"><?php echo htmlspecialchars($car['car_plate']); ?></span>
                                    <span class="car-spec-item" style="font-size: 0.76rem;"><i class="bi bi-gear-fill"></i> <?php echo htmlspecialchars($car['transmission']); ?></span>
                                    <span class="car-spec-dot">•</span>
                                    <span class="car-spec-item" style="font-size: 0.76rem;"><i class="bi bi-people-fill"></i> <?php echo htmlspecialchars($car['seat_capacity']); ?> Tempat</span>
                                </div>
                            </div>
                        </div>
                        
                        <form action="booking_cars.php" method="POST">
                            <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 14px;">
                                
                                <!-- MAKLUMAT PENYEDIA KERETA -->
                                <div style="display: flex; justify-content: space-between; align-items: center; background: #f0f4f8; border: 2px solid var(--black); padding: 8px 12px; border-radius: var(--radius-md); font-weight: 800; font-size: 0.85rem;">
                                    <span style="color: #444;"><i class="bi bi-person-badge text-primary me-1"></i> Penyedia Kereta:</span>
                                    <a href="javascript:void(0)" onclick="showProviderModal('<?php echo htmlspecialchars($car['provider_username'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($car['provider_email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($car['provider_phone'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars(!empty($car['roadtax_file']) ? $car['roadtax_file'] : ($car['provider_roadtax'] ?? ''), ENT_QUOTES); ?>', '<?php echo htmlspecialchars(!empty($car['insurance_file']) ? $car['insurance_file'] : ($car['provider_insurance'] ?? ''), ENT_QUOTES); ?>', '<?php echo htmlspecialchars($car['provider_profile_picture'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($car['provider_qr_code'] ?? '', ENT_QUOTES); ?>')" style="color: #0055ff; font-weight: 900; text-decoration: underline; cursor: pointer;">
                                        <?php echo htmlspecialchars($car['provider_name']); ?> <i class="bi bi-info-circle-fill ms-1"></i>
                                    </a>
                                </div>

                                <!-- MAKLUMAT DOKUMEN & SAH LAKU -->
                                <div style="background: #fafafa; border: 2px solid var(--black); border-radius: var(--radius-md); padding: 9px 12px; font-size: 0.82rem; font-weight: 700;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px; border-bottom: 1px dashed #ddd; padding-bottom: 4px;">
                                        <span style="color: #555;"><i class="bi bi-calendar-event me-1 text-dark"></i> Cukai Jalan Sah Sehingga:</span>
                                        <span style="color: var(--black); font-weight: 800;">
                                            <?php echo !empty($car['roadtax_expiry']) ? date('d/m/Y', strtotime($car['roadtax_expiry'])) : '<span style="color:#888;">Tidak Dinyatakan</span>'; ?>
                                        </span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between;">
                                        <span style="color: #555;"><i class="bi bi-shield-check me-1 text-success"></i> Insurans Sah Sehingga:</span>
                                        <span style="color: var(--black); font-weight: 800;">
                                            <?php echo !empty($car['insurance_expiry']) ? date('d/m/Y', strtotime($car['insurance_expiry'])) : '<span style="color:#888;">Tidak Dinyatakan</span>'; ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- MAKLUMAT SEWAAN -->
                                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 7px; font-weight: 700; font-size: 0.85rem;">
                                    <li style="display: flex; justify-content: space-between; border-bottom: 1.5px dashed #ddd; padding-bottom: 4px;">
                                        <span style="color: #666;"><i class="bi bi-clock-history me-1"></i> Tarikh & Masa Ambil:</span> 
                                        <span><?php echo date('d M Y, h:i A', $start_ts); ?></span>
                                    </li>
                                    <li style="display: flex; justify-content: space-between; border-bottom: 1.5px dashed #ddd; padding-bottom: 4px;">
                                        <span style="color: #666;"><i class="bi bi-clock me-1"></i> Tarikh & Masa Pulang:</span> 
                                        <span><?php echo date('d M Y, h:i A', $end_ts); ?></span>
                                    </li>
                                    <li style="display: flex; justify-content: space-between; border-bottom: 1.5px dashed #ddd; padding-bottom: 4px;">
                                        <span style="color: #666;"><i class="bi bi-hourglass-split me-1"></i> Tempoh Sewaan:</span> 
                                        <span><?php echo $duration; ?> <?php echo ($rent_type === 'Daily') ? 'Hari' : 'Jam'; ?></span>
                                    </li>
                                    <li style="display: flex; justify-content: space-between; border-bottom: 1.5px dashed #ddd; padding-bottom: 4px;">
                                        <span style="color: #666;"><i class="bi bi-tag-fill me-1"></i> Kadar (<?php echo ($rent_type === 'Daily') ? 'Harian' : 'Jam'; ?>):</span> 
                                        <span>RM <?php echo number_format($price_rate, 2); ?></span>
                                    </li>
                                </ul>

                                <!-- JUMLAH ANGGARAN -->
                                <div style="background: var(--bg-color); border: 2px solid var(--black); border-radius: var(--radius-md); padding: 10px 14px; display: flex; justify-content: space-between; align-items: center; font-weight: 900;">
                                    <span style="font-size: 0.95rem;">Jumlah Bayaran:</span>
                                    <span style="color: #008800; font-size: 1.25rem;">RM <?php echo number_format($total_calc_price, 2); ?></span>
                                </div>

                                <!-- NOTA PEMBAYARAN -->
                                <div style="background: var(--yellow); border: 2px solid var(--black); box-shadow: 2px 2px 0px var(--black); border-radius: var(--radius-md); padding: 8px 12px; font-size: 0.8rem; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                                    <i class="bi bi-info-circle-fill fs-6" style="color: var(--black); flex-shrink: 0;"></i>
                                    <div>
                                        <strong>Nota:</strong> Kod QR DuitNow disediakan di <strong>Status Tempahan</strong> selepas tempahan disahkan oleh penyedia.
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                            <input type="hidden" name="rent_type" value="<?php echo htmlspecialchars($rent_type); ?>">
                            <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($search_start); ?>">
                            <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($search_end); ?>">
                            <input type="hidden" name="total_price" value="<?php echo $total_calc_price; ?>">

                            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 15px; border-top: 2px solid var(--black); padding-top: 15px;">
                                <button type="button" class="neo-btn" style="background: #eee; flex: 1; justify-content: center;" onclick="closeModal('modal<?php echo $car['id']; ?>')">Tutup</button>
                                <button type="submit" name="submit_booking" class="neo-btn btn-green" style="flex: 1.5; justify-content: center;"><i class="bi bi-send-fill me-1"></i> Hantar Tempahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <?php
    }
    return ob_get_clean();
}

// PENGENDALIAN PERMINTAAN AJAX (SEARCH & FILTER)
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json; charset=utf-8');

    if ($has_student_conflict) {
        echo json_encode([
            'status' => 'conflict',
            'count' => 0,
            'html' => '<div style="background: #ffebee; border: 3px solid var(--black); border-radius: var(--radius-xl); box-shadow: var(--shadow-solid); padding: 30px 20px; text-align: center; margin-bottom: 30px;"><i class="bi bi-exclamation-octagon-fill" style="font-size: 3rem; color: #d32f2f;"></i><h3 style="font-weight: 900; text-transform: uppercase; margin-top: 12px;">Tempahan Bertindih Dikesan!</h3><p style="font-weight: 700; color: #555; max-width: 500px; margin: 8px auto 20px auto; font-size: 0.95rem;">Anda sudah mempunyai permohonan tempahan aktif yang bertindih dengan masa ini.</p></div>'
        ]);
        exit;
    }

    $keyword = trim($_GET['keyword'] ?? '');
    $transmission = trim($_GET['transmission'] ?? 'all');
    $seats = trim($_GET['seats'] ?? 'all');
    $sort = trim($_GET['sort'] ?? 'default');

    $ajax_result_cars = get_available_cars($conn, $search_start, $search_end, $rent_type, $keyword, $transmission, $seats, $sort);
    $cars_count = $ajax_result_cars ? $ajax_result_cars->num_rows : 0;
    $cars_html = render_car_cards_html($ajax_result_cars, $rent_type, $duration, $search_start, $search_end, $start_ts, $end_ts);

    echo json_encode([
        'status' => 'success',
        'count' => $cars_count,
        'html' => $cars_html
    ]);
    exit;
}

// Carian kereta untuk muatan asal (tanpa AJAX)
$result_cars = null;
if (!$has_student_conflict) {
    $result_cars = get_available_cars($conn, $search_start, $search_end, $rent_type);
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pilih Kenderaan (Langkah 2) - SCRS PMU</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Master Neo-Brutalism CSS -->
    <link rel="stylesheet" href="../assets/css/neo-style.css">

    <style>
        /* Tajuk Halaman Mengikut Standard booking.php */
        .booking-page-header {
            margin-bottom: 20px;
        }
        .booking-title-bar {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-bottom: 8px;
        }
        .booking-title {
            font-size: 1.55rem;
            font-weight: 900;
            text-transform: uppercase;
            margin: 0;
            color: var(--black);
            line-height: 1.2;
        }
        .booking-subtitle {
            font-weight: 700;
            color: #555;
            font-size: 0.92rem;
            margin: 0;
            line-height: 1.5;
            width: 100%;
        }

        /* Bar Ringkasan Carian Terpilih (Kemas & Berstruktur) */
        .summary-card {
            background: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-solid);
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .summary-top-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .summary-badges {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .summary-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: 0.2px;
        }
        .summary-pill.pill-mode {
            background: #fef08a;
            color: #854d0e;
            border: 1px solid #facc15;
        }
        .summary-pill.pill-duration {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
        .summary-edit-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.82rem;
            font-weight: 800;
            padding: 7px 14px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .summary-btn-desktop {
            display: inline-flex;
        }
        .summary-btn-mobile {
            display: none;
        }

        /* Kotak Tarikh & Masa Ambil / Pulang (Bukan Butang) */
        .summary-dates-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .date-tile {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: var(--radius-md);
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .date-icon-box {
            width: 36px;
            height: 36px;
            min-width: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
        }
        .date-icon-box.ambil {
            background: #dcfce7;
            color: #15803d;
        }
        .date-icon-box.pulang {
            background: #fee2e2;
            color: #b91c1c;
        }
        .date-content {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }
        .date-label {
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.5px;
            line-height: 1.1;
        }
        .date-value {
            font-size: 0.92rem;
            font-weight: 900;
            color: var(--black);
            white-space: nowrap;
            line-height: 1.3;
        }

        /* Senarai Kereta & Kad Paparan Penuh */
        .cars-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); 
            gap: 20px; 
            margin-bottom: 35px; 
        }
        .car-card { 
            background: var(--white); 
            border: var(--border-thick); 
            border-radius: var(--radius-xl); 
            box-shadow: var(--shadow-solid); 
            display: flex; 
            flex-direction: column; 
            overflow: hidden; 
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            position: relative;
            user-select: none;
        }
        .car-card:hover {
            transform: translate(-3px, -3px);
            box-shadow: 6px 6px 0px var(--black);
        }
        .car-card:active {
            transform: translate(0px, 0px);
            box-shadow: 2px 2px 0px var(--black);
        }
        .car-thumb-wrap { 
            position: relative;
            width: 100%;
            height: 190px; 
            background-color: #f1f5f9;
            border-bottom: var(--border-thick); 
            overflow: hidden;
        }
        .car-thumb-wrap .car-img { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
            display: block;
            transition: transform 0.25s ease;
        } 
        .car-card:hover .car-img {
            transform: scale(1.04);
        }
        .car-rate-pill {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--yellow);
            color: var(--black);
            font-weight: 900;
            font-size: 0.8rem;
            padding: 4px 9px;
            border: 1.5px solid var(--black);
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .car-body { 
            padding: 16px 18px; 
            display: flex; 
            flex-direction: column; 
            gap: 12px;
            flex: 1; 
        }
        .car-title-wrap {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            min-height: 48px; /* Menyelaraskan ketinggian kawasan tajuk (1 atau 2 baris) supaya semua kad sekata */
        }
        .car-name { 
            font-weight: 900; 
            font-size: 1.12rem; 
            text-transform: uppercase; 
            margin: 0;
            line-height: 1.25;
            color: var(--black);
            flex: 1;
        } 
        .car-plate-pill { 
            font-weight: 800; 
            background: var(--black);
            color: var(--white); 
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.78rem;
            letter-spacing: 0.5px;
            white-space: nowrap;
            flex-shrink: 0;
        }
        /* Spesifikasi Kenderaan (Teks Maklumat, Bukan Butang) */
        .car-specs-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 2px 0;
        }
        .car-spec-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.82rem;
            font-weight: 800;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .car-spec-item i {
            color: #1e293b;
            font-size: 0.88rem;
        }
        .car-spec-dot {
            color: #94a3b8;
            font-weight: 900;
            font-size: 0.8rem;
        }
        .car-provider-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: var(--radius-sm);
            padding: 7px 10px;
            font-size: 0.82rem;
            font-weight: 800;
            margin-top: auto; /* Tolak maklumat penyedia & harga ke bawah secara seragam */
        }
        .provider-lbl {
            color: #475569;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .provider-link {
            color: #0055ff;
            font-weight: 900;
            text-decoration: underline;
            cursor: pointer;
        }
        .car-price-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: var(--radius-md);
            padding: 10px 14px;
            margin-top: 0; /* Kekalkan jarak konsisten dengan kotak penyedia mengikut gap */
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .price-total-lbl {
            font-size: 0.9rem;
            font-weight: 900;
            color: var(--black);
        }
        .price-total-val {
            color: #00873e;
            font-size: 1.25rem;
            font-weight: 900;
        }
        .car-action-btn {
            width: 100%;
            justify-content: center;
            font-size: 0.95rem;
            font-weight: 900;
            padding: 10px 14px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* ========================================================
           BAR CARIAN & PENAPIS (AJAX FILTER BAR - MELINTANG KOMPAK)
           ======================================================== */
        .cars-filter-bar {
            background: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-solid);
            padding: 10px 14px;
            margin-bottom: 20px;
        }
        .filter-horizontal-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
        }
        .filter-search-wrap {
            position: relative;
            display: flex;
            align-items: center;
            flex: 2 1 260px;
            min-width: 180px;
        }
        .filter-search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 1.05rem;
            pointer-events: none;
            z-index: 5;
            line-height: 1;
        }
        .filter-input-search,
        input[type="text"].filter-input-search {
            width: 100% !important;
            height: 42px !important;
            min-height: 42px !important;
            padding-left: 48px !important; /* Jarak 48px memastikan langsung tidak bertindih dengan ikon kanta pada 14px */
            padding-right: 36px !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            background: #f8fafc !important;
            border: 2px solid var(--black) !important;
            border-radius: var(--radius-md) !important;
            box-shadow: none !important;
            font-family: inherit;
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--black);
            outline: none;
            transition: background 0.15s ease, border-color 0.15s ease;
        }
        .filter-input-search:focus,
        input[type="text"].filter-input-search:focus {
            background: #ffffff !important;
            border-color: var(--black) !important;
            box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.12) !important;
        }
        .filter-input-search::placeholder,
        input[type="text"].filter-input-search::placeholder {
            color: #94a3b8;
            font-weight: 600;
            font-size: 0.83rem;
        }
        .search-clear-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            padding: 0;
            color: #94a3b8;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            line-height: 1;
            z-index: 5;
            transition: color 0.15s ease;
        }
        .search-clear-btn:hover {
            color: #334155;
        }
        .filter-selects-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 3 1 auto;
        }
        .filter-select-wrap {
            position: relative;
            display: flex;
            align-items: center;
            flex: 1 1 0;
            min-width: 130px;
        }
        .filter-select-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #475569;
            font-size: 0.9rem;
            pointer-events: none;
            z-index: 5;
            line-height: 1;
        }
        .filter-select,
        select.filter-select {
            width: 100% !important;
            height: 42px !important;
            min-height: 42px !important;
            padding-left: 36px !important; /* Ruang 36px di kiri untuk ikon filter */
            padding-right: 12px !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            background: #f8fafc !important;
            border: 2px solid var(--black) !important;
            border-radius: var(--radius-md) !important;
            box-shadow: none !important;
            font-family: inherit;
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--black);
            cursor: pointer;
            outline: none;
            transition: background 0.15s ease, border-color 0.15s ease;
            appearance: auto;
            white-space: nowrap;
        }
        .filter-select:focus,
        select.filter-select:focus {
            background: #ffffff !important;
            box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.12) !important;
        }
        /* Loading Overlay & Container */
        .cars-list-container {
            position: relative;
            min-height: 240px;
        }
        .cars-loading-overlay {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(2px);
            z-index: 40;
            display: none;
            align-items: flex-start;
            justify-content: center;
            padding-top: 60px;
            border-radius: var(--radius-lg);
            transition: opacity 0.2s ease;
        }
        .cars-loading-overlay.active {
            display: flex;
        }
        .cars-loading-badge {
            background: var(--white);
            border: 2.5px solid var(--black);
            border-radius: var(--radius-md);
            box-shadow: 4px 4px 0px var(--black);
            padding: 10px 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 900;
            font-size: 0.88rem;
            color: var(--black);
            text-transform: uppercase;
        }
        .cars-spinner {
            width: 16px;
            height: 16px;
            border: 2.5px solid #cbd5e1;
            border-top-color: var(--black);
            border-radius: 50%;
            animation: spinCars 0.6s linear infinite;
        }
        .no-cars-box {
            background: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-solid);
            padding: 40px 20px;
            text-align: center;
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .booking-page-header { margin-bottom: 18px; }
            .booking-title-bar { gap: 16px; margin-bottom: 8px; }
            .booking-title { font-size: 1.22rem; }
            .booking-subtitle { font-size: 0.88rem; }
            .btn-arrow-back { width: 36px; height: 36px; min-width: 36px; font-size: 1.15rem; }
            .cars-grid { grid-template-columns: 1fr; gap: 18px; } 
            .car-thumb-wrap { height: 185px; }
            .cars-filter-bar {
                padding: 10px;
                margin-bottom: 16px;
            }
            .filter-horizontal-wrap {
                flex-direction: column;
                gap: 8px;
                width: 100%;
            }
            .filter-search-wrap {
                width: 100%;
                flex: none;
            }
            .filter-search-icon {
                left: 14px;
                font-size: 1rem;
            }
            .filter-input-search,
            input[type="text"].filter-input-search {
                height: 40px !important;
                min-height: 40px !important;
                padding-left: 48px !important;
                padding-right: 34px !important;
                font-size: 0.84rem;
            }
            .filter-selects-group {
                width: 100%;
                display: grid;
                grid-template-columns: 1fr 1fr 1fr;
                gap: 6px;
            }
            .filter-select-wrap {
                width: 100%;
                min-width: 0;
            }
            .filter-select-icon {
                left: 8px;
                font-size: 0.82rem;
            }
            .filter-select,
            select.filter-select {
                width: 100% !important;
                height: 38px !important;
                min-height: 38px !important;
                padding-left: 28px !important;
                padding-right: 4px !important;
                font-size: 0.72rem;
            }
            .summary-card { 
                padding: 14px; 
                gap: 12px; 
            }
            .summary-top-row {
                flex-direction: row;
                align-items: center;
                justify-content: flex-start;
                gap: 8px;
            }
            .summary-badges {
                gap: 6px;
                width: 100%;
            }
            .summary-pill {
                padding: 4px 10px;
                font-size: 0.74rem;
            }
            .summary-dates-grid { 
                grid-template-columns: 1fr; 
                gap: 8px; 
            }
            .date-tile {
                padding: 9px 12px;
                gap: 10px;
            }
            .date-icon-box {
                width: 32px;
                height: 32px;
                min-width: 32px;
                font-size: 0.95rem;
            }
            .date-value {
                font-size: 0.88rem;
                white-space: normal;
            }
            .summary-btn-desktop {
                display: none;
            }
            .summary-btn-mobile {
                display: flex;
                width: 100%;
                justify-content: center;
                padding: 9px 14px;
            }
            .neo-modal { padding: 16px; }
            footer {
                display: block !important;
                position: relative !important;
                flex-shrink: 0 !important;
                padding: 14px 16px !important;
                padding-bottom: calc(14px + env(safe-area-inset-bottom, 0px)) !important;
                font-size: 0.78rem !important;
                width: 100% !important;
            }
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
            <a href="booking.php" class="sidebar-link active"><i class="bi bi-car-front-fill"></i> Cari & Tempah</a>
            <a href="my_bookings.php" class="sidebar-link"><i class="bi bi-calendar-check-fill"></i> Tempahan Saya</a>
            <a href="booking_history.php" class="sidebar-link"><i class="bi bi-clock-history"></i> Rekod Tempahan</a>
        </nav>
    </aside>

    <main class="main-content">
        
        <?php echo $message; ?>

        <!-- HEADING HALAMAN (MENGIKUT FORMAT KONSISTEN booking.php) -->
        <div class="booking-page-header">
            <div class="booking-title-bar">
                <a href="booking.php" class="neo-btn btn-yellow btn-arrow-back" title="Kembali ke Carian" aria-label="Kembali ke Carian">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h1 class="booking-title">Pilih Kenderaan</h1>
            </div>
            <p class="booking-subtitle">
                Sila pilih kenderaan yang sesuai mengikut bajet dan keperluan anda. Tekan butang 'Pilih Kereta' untuk butiran lengkap &amp; pengesahan tempahan.
            </p>
        </div>

        <!-- BAR RINGKASAN CARIAN (KEMAS & BERSTRUKTUR) -->
        <div class="summary-card">
            <div class="summary-top-row">
                <div class="summary-badges">
                    <span class="summary-pill pill-mode">
                        <i class="bi bi-tag-fill"></i> <?php echo ($rent_type === 'Daily') ? 'Harian (Daily)' : 'Jam (Hourly)'; ?>
                    </span>
                    <span class="summary-pill pill-duration">
                        <i class="bi bi-hourglass-split"></i> <?php echo $duration; ?> <?php echo ($rent_type === 'Daily') ? 'Hari' : 'Jam'; ?>
                    </span>
                </div>
                <a href="booking.php" class="neo-btn btn-yellow summary-edit-btn summary-btn-desktop">
                    <i class="bi bi-pencil-square me-1"></i> Ubah Carian
                </a>
            </div>

            <div class="summary-dates-grid">
                <div class="date-tile">
                    <div class="date-icon-box ambil">
                        <i class="bi bi-calendar-check-fill"></i>
                    </div>
                    <div class="date-content">
                        <span class="date-label">Tarikh &amp; Masa Ambil</span>
                        <span class="date-value"><?php echo date('d M Y, h:i A', $start_ts); ?></span>
                    </div>
                </div>
                <div class="date-tile">
                    <div class="date-icon-box pulang">
                        <i class="bi bi-calendar-x-fill"></i>
                    </div>
                    <div class="date-content">
                        <span class="date-label">Tarikh &amp; Masa Pulang</span>
                        <span class="date-value"><?php echo date('d M Y, h:i A', $end_ts); ?></span>
                    </div>
                </div>
            </div>

            <a href="booking.php" class="neo-btn btn-yellow summary-edit-btn summary-btn-mobile">
                <i class="bi bi-pencil-square me-1"></i> Ubah Carian
            </a>
        </div>

        <!-- SEMAKAN JIKA ADA TEMPAHAN BERTINDIH OLEH PELAJAR -->
        <?php if ($has_student_conflict): ?>
            <div style="background: #ffebee; border: 3px solid var(--black); border-radius: var(--radius-xl); box-shadow: var(--shadow-solid); padding: 30px 20px; text-align: center; margin-bottom: 30px;">
                <i class="bi bi-exclamation-octagon-fill" style="font-size: 3rem; color: #d32f2f;"></i>
                <h3 style="font-weight: 900; text-transform: uppercase; margin-top: 12px;">Tempahan Bertindih Dikesan!</h3>
                <p style="font-weight: 700; color: #555; max-width: 500px; margin: 8px auto 20px auto; font-size: 0.95rem;">
                    Anda sudah mempunyai permohonan tempahan aktif (Pending/Approved) yang bertindih dengan masa ini. Setiap pelajar hanya dibenarkan menyewa <strong>1 kenderaan sahaja</strong> dalam satu masa.
                </p>
                <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                    <a href="booking.php" class="neo-btn btn-yellow">
                        <i class="bi bi-calendar-event me-1"></i> Pilih Waktu Lain
                    </a>
                    <a href="my_bookings.php" class="neo-btn btn-blue">
                        <i class="bi bi-clipboard-check me-1"></i> Lihat Status Tempahan
                    </a>
                </div>
            </div>

        <?php else: ?>
            <!-- BAR CARIAN & PENAPIS (AJAX FILTER BAR - MELINTANG KOMPAK) -->
            <div class="cars-filter-bar">
                <div class="filter-horizontal-wrap">
                    <!-- INPUT CARIAN KATA KUNCI -->
                    <div class="filter-search-wrap">
                        <i class="bi bi-search filter-search-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); pointer-events: none; z-index: 5; color: #64748b; font-size: 1.05rem;"></i>
                        <input type="text" id="filter-keyword" class="filter-input-search" placeholder="Cari model, jenama, no plat, penyedia..." autocomplete="off" style="padding-left: 48px !important;">
                        <button type="button" id="filter-clear-keyword" class="search-clear-btn" title="Padam carian">
                            <i class="bi bi-x-circle-fill"></i>
                        </button>
                    </div>

                    <!-- KUMPULAN PILIHAN TAPISAN (MELINTANG) -->
                    <div class="filter-selects-group">
                        <!-- DROPDOWN TRANSMISI -->
                        <div class="filter-select-wrap">
                            <i class="bi bi-gear-fill filter-select-icon" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); pointer-events: none; z-index: 5; color: #475569; font-size: 0.9rem;"></i>
                            <select id="filter-transmission" class="filter-select" aria-label="Tapis transmisi" style="padding-left: 36px !important;">
                                <option value="all">Semua Transmisi</option>
                                <option value="Auto">Auto</option>
                                <option value="Manual">Manual</option>
                            </select>
                        </div>

                        <!-- DROPDOWN TEMPAT DUDUK -->
                        <div class="filter-select-wrap">
                            <i class="bi bi-people-fill filter-select-icon" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); pointer-events: none; z-index: 5; color: #475569; font-size: 0.9rem;"></i>
                            <select id="filter-seats" class="filter-select" aria-label="Tapis kapasiti tempat duduk" style="padding-left: 36px !important;">
                                <option value="all">Semua Tempat</option>
                                <option value="4">4 Tempat</option>
                                <option value="5">5 Tempat</option>
                                <option value="7">7+ Tempat</option>
                            </select>
                        </div>

                        <!-- DROPDOWN SUSUNAN HARGA -->
                        <div class="filter-select-wrap">
                            <i class="bi bi-tag-fill filter-select-icon" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); pointer-events: none; z-index: 5; color: #475569; font-size: 0.9rem;"></i>
                            <select id="filter-sort" class="filter-select" aria-label="Tapis harga" style="padding-left: 36px !important;">
                                <option value="default">Harga: Lalai</option>
                                <option value="price_asc">Harga: Rendah ke Tinggi</option>
                                <option value="price_desc">Harga: Tinggi ke Rendah</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BEKAS SENARAI KENDERAAN (DIKEMASKINI MELALUI AJAX) -->
            <div id="cars-list-container" class="cars-list-container">
                <!-- INDIKATOR MEMUATKAN (AJAX OVERLAY) -->
                <div id="cars-loading-overlay" class="cars-loading-overlay">
                    <div class="cars-loading-badge">
                        <div class="cars-spinner"></div>
                        <span>Memuatkan kenderaan...</span>
                    </div>
                </div>

                <!-- KANDUNGAN KAD KERETA & MODAL -->
                <div id="cars-cards-content">
                    <?php echo render_car_cards_html($result_cars, $rent_type, $duration, $search_start, $search_end, $start_ts, $end_ts); ?>
                </div>
            </div>
        <?php endif; ?>

    </main>

    <footer>
        &copy; <?php echo date("Y"); ?> SCRS PMU. SISTEM SEWAAN KERETA.
    </footer>

    <!-- SKRIP ASLI (VANILLA JS) -->
    <script>
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

        const openSidebarBtn = document.getElementById('open-sidebar');
        const closeSidebarBtn = document.getElementById('close-sidebar');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');

        function openSidebar() { sidebar.classList.add('open'); sidebarOverlay.classList.add('show'); }
        function closeSidebar() { sidebar.classList.remove('open'); sidebarOverlay.classList.remove('show'); }

        openSidebarBtn.addEventListener('click', openSidebar);
        closeSidebarBtn.addEventListener('click', closeSidebar);
        sidebarOverlay.addEventListener('click', closeSidebar);

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

        // Fungsi buka dan tutup Modal
        window.openModal = function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.add('show');
        };

        window.closeModal = function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.remove('show');
        };

        // ========================================================
        // LOGIK CARIAN & TAPISAN AJAX (SEARCH & FILTER)
        // ========================================================
        (function() {
            const searchStart = <?php echo json_encode($search_start); ?>;
            const searchEnd = <?php echo json_encode($search_end); ?>;
            const rentType = <?php echo json_encode($rent_type); ?>;

            const keywordInput = document.getElementById('filter-keyword');
            const clearKeywordBtn = document.getElementById('filter-clear-keyword');
            const transSelect = document.getElementById('filter-transmission');
            const seatsSelect = document.getElementById('filter-seats');
            const sortSelect = document.getElementById('filter-sort');
            const countNum = document.getElementById('filter-count-num');
            const cardsContent = document.getElementById('cars-cards-content');
            const loadingOverlay = document.getElementById('cars-loading-overlay');

            if (!keywordInput || !cardsContent) {
                return; // Tamat awal jika komponen tiada (cth: tempahan bertindih)
            }

            let searchDebounceTimer = null;
            let currentAbortCtrl = null;

            function updateClearBtnVisibility() {
                if (clearKeywordBtn) {
                    clearKeywordBtn.style.display = (keywordInput.value.trim().length > 0) ? 'flex' : 'none';
                }
            }

            function fetchFilteredCars() {
                if (currentAbortCtrl) {
                    currentAbortCtrl.abort();
                }
                currentAbortCtrl = new AbortController();

                if (loadingOverlay) {
                    loadingOverlay.classList.add('active');
                }

                const params = new URLSearchParams({
                    ajax: '1',
                    start_date: searchStart,
                    end_date: searchEnd,
                    rent_type: rentType,
                    keyword: keywordInput.value.trim(),
                    transmission: transSelect.value,
                    seats: seatsSelect.value,
                    sort: sortSelect.value
                });

                fetch('booking_cars.php?' + params.toString(), {
                    method: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    signal: currentAbortCtrl.signal
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP error ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data && data.status === 'success') {
                        cardsContent.innerHTML = data.html;
                        if (countNum) {
                            countNum.textContent = data.count;
                        }
                    }
                })
                .catch(err => {
                    if (err.name !== 'AbortError') {
                        console.error('Ralat memuatkan senarai kereta:', err);
                    }
                })
                .finally(() => {
                    if (loadingOverlay) {
                        loadingOverlay.classList.remove('active');
                    }
                });
            }

            // Dengar perubahan input kata kunci dengan Debounce 280ms
            keywordInput.addEventListener('input', function() {
                updateClearBtnVisibility();
                clearTimeout(searchDebounceTimer);
                searchDebounceTimer = setTimeout(fetchFilteredCars, 280);
            });

            // Tekan Enter untuk carian pantas serta-merta
            keywordInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(searchDebounceTimer);
                    fetchFilteredCars();
                }
            });

            // Butang padam input kata kunci
            if (clearKeywordBtn) {
                clearKeywordBtn.addEventListener('click', function() {
                    keywordInput.value = '';
                    updateClearBtnVisibility();
                    keywordInput.focus();
                    fetchFilteredCars();
                });
            }

            // Dengar perubahan dropdown tapisan
            if (transSelect) transSelect.addEventListener('change', fetchFilteredCars);
            if (seatsSelect) seatsSelect.addEventListener('change', fetchFilteredCars);
            if (sortSelect) sortSelect.addEventListener('change', fetchFilteredCars);

            // Fungsi reset semua penapis
            window.resetAllFilters = function() {
                keywordInput.value = '';
                updateClearBtnVisibility();
                if (transSelect) transSelect.value = 'all';
                if (seatsSelect) seatsSelect.value = 'all';
                if (sortSelect) sortSelect.value = 'default';
                fetchFilteredCars();
            };
        })();

        // Cegah paparan semula melalui butang Back selepas log keluar
        window.addEventListener('pageshow', function(event) {
            if (event.persisted || (window.performance && window.performance.navigation && window.performance.navigation.type === 2)) {
                window.location.reload();
            }
        });
    </script>

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
                    <button class="neo-btn bg-p" style="width: 100%; justify-content: center;" onclick="closeProviderModal()"><i class="bi bi-arrow-left-short me-1"></i>Tutup</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
