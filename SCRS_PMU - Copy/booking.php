<?php
session_start();
require 'db.php';

// Semak jika pengguna telah log masuk dan merupakan seorang pelajar
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['username'];
$message = "";

// 1. PROSES PENGHANTARAN TEMPAHAN (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_booking'])) {
    $car_id = (int)$_POST['car_id'];
    $rent_type = htmlspecialchars($_POST['rent_type']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $total_price = (float)$_POST['total_price'];
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
            $message = "<div class='neo-alert alert-success'><i class='bi bi-check-circle-fill me-2'></i>Permohonan tempahan berjaya dihantar! Sila tunggu kelulusan daripada Penyedia Kereta. Selepas diluluskan, anda boleh membuat bayaran menggunakan Kod QR di halaman <a href='my_bookings.php' style='text-decoration: underline; font-weight: 900;'>Status Tempahan</a>.</div>";

            // ========================================================
            // HANTAR E-MEL NOTIFIKASI KEPADA PENYEDIA KERETA (PROVIDER)
            // ========================================================
            $sql_prov = "SELECT p.full_name AS provider_name, p.email AS provider_email, 
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

            if ($prov_data && !empty($prov_data['provider_email'])) {
                // Bina URL pautan ke halaman penyedia
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                $host = $_SERVER['HTTP_HOST'];
                $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                $provider_page_link = $protocol . $host . $uri . "/provider_bookings.php";

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
                                    <td style='padding: 8px;'>" . htmlspecialchars($prov_data['student_full_name']) . " (" . htmlspecialchars($prov_data['no_pendaftaran']) . ")</td>
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
                    $message .= "<div class='neo-alert alert-success mt-2'><i class='bi bi-envelope-check-fill me-2'></i>Notifikasi e-mel telah dihantar kepada Penyedia Kereta.</div>";
                } catch (Exception $e) {
                    // E-mel gagal dihantar tapi rekod tempahan tetap berjaya
                }
            }
        } else {
            $message = "<div class='neo-alert alert-danger'>Ralat pangkalan data: " . $stmt_book->error . "</div>";
        }
        $stmt_book->close();
    }
}

// 2. PROSES CARIAN AJAX (GET)
// Bahagian ini hanya akan berjalan apabila dipanggil melalui JavaScript fetch()
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $search_start = $_GET['start_date'] ?? '';
    $search_end = $_GET['end_date'] ?? '';
    $rent_type = $_GET['rent_type'] ?? '';

    if (!$search_start || !$search_end || !$rent_type) {
        echo "<div class='neo-alert alert-danger' style='text-align: center;'>Sila lengkapkan semua medan carian.</div>";
        exit;
    }

    $start_ts = strtotime($search_start);
    $end_ts = strtotime($search_end);

    if ($start_ts >= $end_ts) {
        echo "<div class='neo-alert alert-danger' style='text-align: center;'>Tarikh/Masa pemulangan mestilah selepas tarikh/masa pengambilan.</div>";
        exit;
    }
    // Semak jika pelajar sudah mempunyai tempahan aktif pada waktu bertindih
    $sql_check_student = "SELECT id FROM bookings 
                          WHERE student_id = ? 
                          AND status IN ('Pending', 'Approved') 
                          AND (start_date < ? AND end_date > ?)";
    $stmt_check = $conn->prepare($sql_check_student);
    $stmt_check->bind_param("iss", $student_id, $search_end, $search_start);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    if ($res_check->num_rows > 0) {
        echo "<div class='neo-alert alert-danger' style='text-align: center;'><i class='bi bi-exclamation-octagon-fill me-2'></i>Ralat: Anda sudah mempunyai tempahan aktif (Pending/Approved) yang bertindih dengan waktu yang dipilih! Setiap pelajar hanya dibenarkan menyewa 1 kereta dalam satu masa sahaja.</div>";
        $stmt_check->close();
        exit;
    }
    $stmt_check->close();

    // Kira tempoh masa
    if ($rent_type === 'Hourly') {
        $duration = ceil(($end_ts - $start_ts) / 3600); // Jam
    } else {
        $duration = ceil(($end_ts - $start_ts) / 86400); // Hari
        if ($duration < 1) $duration = 1;
    }

    // Cari kereta tersedia beserta maklumat penuh penyedia
    $sql_cars = "SELECT c.*, 
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
                 )
                 ORDER BY c.created_at DESC";
                 
    $stmt_cars = $conn->prepare($sql_cars);
    $stmt_cars->bind_param("ss", $search_end, $search_start);
    $stmt_cars->execute();
    $result_cars = $stmt_cars->get_result();

    if ($result_cars->num_rows > 0) {
        echo '<h3 class="section-heading"><i class="bi bi-car-front-fill text-dark"></i> Langkah 2: Pilih Kereta</h3>';
        echo '<p class="section-desc">Menampilkan kereta yang tersedia untuk tempoh <strong>' . $duration . ' ' . ($rent_type === 'Daily' ? 'Hari' : 'Jam') . '</strong>.</p>';
        echo '<div class="cars-grid">';
        
        while ($car = $result_cars->fetch_assoc()) { 
            $price_rate = ($rent_type === 'Daily') ? $car['price_per_day'] : $car['price_per_hour'];
            $total_calc_price = $duration * $price_rate;
            ?>
            <div class="car-card">
                <img src="<?php echo htmlspecialchars($car['car_image']); ?>" class="car-img" alt="Kereta">
                <div class="car-body">
                    <h5 class="car-title"><?php echo (!empty($car['car_brand']) ? htmlspecialchars($car['car_brand']) . ' ' : '') . htmlspecialchars($car['car_model']); ?></h5>
                    <p class="car-plate"><?php echo htmlspecialchars($car['car_plate']); ?></p>
                    
                    <div class="badges-box">
                        <span class="neo-badge"><i class="bi bi-gear-fill"></i> <?php echo htmlspecialchars($car['transmission']); ?></span>
                        <span class="neo-badge"><i class="bi bi-people-fill"></i> <?php echo htmlspecialchars($car['seat_capacity']); ?> Tempat Duduk</span>
                    </div>

                    <!-- MAKLUMAT DOKUMEN & SAH LAKU KERETA -->
                    <div style="background: #fafafa; border: 2px solid var(--black); padding: 8px 10px; margin-bottom: 12px; font-size: 0.8rem; font-weight: 700;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
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

                    <!-- MAKLUMAT PENYEDIA KERETA -->
                    <div style="margin-bottom: 12px; font-size: 0.85rem; font-weight: 800; display: flex; align-items: center; justify-content: space-between; background: #f0f4f8; border: 2px solid var(--black); padding: 6px 10px;">
                        <span style="color: #444;"><i class="bi bi-person-badge text-primary me-1"></i> Penyedia:</span>
                        <a href="javascript:void(0)" onclick="showProviderModal('<?php echo htmlspecialchars($car['provider_username'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($car['provider_email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($car['provider_phone'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars(!empty($car['roadtax_file']) ? $car['roadtax_file'] : ($car['provider_roadtax'] ?? ''), ENT_QUOTES); ?>', '<?php echo htmlspecialchars(!empty($car['insurance_file']) ? $car['insurance_file'] : ($car['provider_insurance'] ?? ''), ENT_QUOTES); ?>', '<?php echo htmlspecialchars($car['provider_profile_picture'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($car['provider_qr_code'] ?? '', ENT_QUOTES); ?>')" style="color: #0055ff; font-weight: 900; text-decoration: underline; cursor: pointer;">
                            <?php echo htmlspecialchars($car['provider_name']); ?> <i class="bi bi-info-circle-fill ms-1"></i>
                        </a>
                    </div>
                    
                    <div class="price-box">
                        <div class="price-row">
                            <span>Kadar (<?php echo ($rent_type === 'Daily') ? 'Harian' : 'Jam'; ?>):</span>
                            <span>RM <?php echo number_format($price_rate, 2); ?></span>
                        </div>
                        <div class="price-total">
                            <span>Jumlah Anggaran:</span>
                            <span style="color: #007700;">RM <?php echo number_format($total_calc_price, 2); ?></span>
                        </div>
                    </div>

                    <button type="button" class="neo-btn btn-green" style="width: 100%; justify-content: center;" onclick="openModal('modal<?php echo $car['id']; ?>')">
                        <i class="bi bi-key-fill me-1"></i> Tempah Sekarang
                    </button>
                </div>
            </div>

            <!-- MODAL POPUP PENGESAHAN TEMPAHAN (Vanilla JS Modal) -->
            <div class="neo-modal-overlay" id="modal<?php echo $car['id']; ?>">
                <div class="neo-modal" style="max-width: 550px;">
                    <div class="modal-header">
                        <h3 class="modal-title"><i class="bi bi-file-earmark-check-fill me-1"></i> Pengesahan Tempahan</h3>
                    </div>
                    
                    <form action="booking.php" method="POST">
                        <div>
                            <h4 style="font-weight: 900; text-transform: uppercase; margin-bottom: 15px; color: var(--black);">
                                <?php echo !empty($car['car_brand']) ? htmlspecialchars($car['car_brand']) . ' ' : ''; ?><?php echo htmlspecialchars($car['car_model']); ?>
                                <span style="font-size: 0.9rem; color: #555;">(<?php echo htmlspecialchars($car['car_plate']); ?>)</span>
                            </h4>
                            <ul style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; font-weight: 700;">
                                <li style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ddd; padding-bottom: 5px; align-items: center;">
                                    <span style="color: #666;">Penyedia Kereta:</span> 
                                    <span>
                                        <a href="javascript:void(0)" onclick="showProviderModal('<?php echo htmlspecialchars($car['provider_username'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($car['provider_email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($car['provider_phone'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($car['provider_roadtax'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($car['provider_insurance'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($car['provider_profile_picture'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($car['provider_qr_code'] ?? '', ENT_QUOTES); ?>')" style="color: #0055ff; font-weight: 900; text-decoration: underline; cursor: pointer;">
                                            <?php echo htmlspecialchars($car['provider_name']); ?> <i class="bi bi-info-circle-fill ms-1"></i>
                                        </a>
                                    </span>
                                </li>
                                <li style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ddd; padding-bottom: 5px;">
                                    <span style="color: #666;">Tarikh & Masa Ambil:</span> 
                                    <span><?php echo date('d M Y, h:i A', strtotime($search_start)); ?></span>
                                </li>
                                <li style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ddd; padding-bottom: 5px;">
                                    <span style="color: #666;">Tarikh & Masa Pulang:</span> 
                                    <span><?php echo date('d M Y, h:i A', strtotime($search_end)); ?></span>
                                </li>
                                <li style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ddd; padding-bottom: 5px;">
                                    <span style="color: #666;">Tempoh:</span> 
                                    <span><?php echo $duration; ?> <?php echo ($rent_type === 'Daily') ? 'Hari' : 'Jam'; ?></span>
                                </li>
                                <li style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ddd; padding-bottom: 5px;">
                                    <span style="color: #666;">Cukai Jalan Sah Sehingga:</span> 
                                    <span style="font-weight: 800;"><?php echo !empty($car['roadtax_expiry']) ? date('d/m/Y', strtotime($car['roadtax_expiry'])) : 'Tidak Dinyatakan'; ?></span>
                                </li>
                                <li style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ddd; padding-bottom: 5px;">
                                    <span style="color: #666;">Insurans Sah Sehingga:</span> 
                                    <span style="font-weight: 800;"><?php echo !empty($car['insurance_expiry']) ? date('d/m/Y', strtotime($car['insurance_expiry'])) : 'Tidak Dinyatakan'; ?></span>
                                </li>
                            </ul>
                            <div style="background: var(--bg-color); border: 3px solid var(--black); padding: 15px; margin-bottom: 15px;">
                                <div style="display: flex; justify-content: space-between; font-weight: 900; font-size: 1.2rem;">
                                    <span>Jumlah Bayaran:</span>
                                    <span style="color: #008800;">RM <?php echo number_format($total_calc_price, 2); ?></span>
                                </div>
                            </div>

                            <div style="background: var(--yellow); border: 2px solid var(--black); box-shadow: 3px 3px 0px var(--black); border-radius: var(--radius-md); padding: 10px 14px; font-size: 0.85rem; font-weight: 700; text-transform: none; line-height: 1.4; display: flex; align-items: center; gap: 10px; text-align: left;">
                                <i class="bi bi-info-circle-fill fs-5" style="color: var(--black); flex-shrink: 0;"></i>
                                <div style="flex: 1;">
                                    <strong>Nota Pembayaran:</strong> Kod QR DuitNow disediakan di <strong>Status Tempahan</strong> selepas tempahan disahkan.
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                        <input type="hidden" name="rent_type" value="<?php echo htmlspecialchars($rent_type); ?>">
                        <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($search_start); ?>">
                        <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($search_end); ?>">
                        <input type="hidden" name="total_price" value="<?php echo $total_calc_price; ?>">

                        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 25px; border-top: 3px solid var(--black); padding-top: 20px; flex-wrap: wrap;">
                            <button type="button" class="neo-btn" style="background: #ccc; flex: 1;" onclick="closeModal('modal<?php echo $car['id']; ?>')">Batal</button>
                            <button type="submit" name="submit_booking" class="neo-btn btn-green" style="flex: 1.5;"><i class="bi bi-send-fill me-1"></i> Hantar Tempahan</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php
        }
        echo '</div>'; // Tutup cars-grid
    } else {
        echo '<div class="neo-alert alert-danger" style="text-align: center;">Maaf, tiada kereta yang tersedia untuk tarikh/masa yang dipilih. Sila cuba tarikh lain.</div>';
    }
    // Hentikan PHP di sini supaya ia hanya menghantar HTML hasil carian kepada fetch()
    exit;
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Tempahan Kereta - SCRS PMU</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Master Neo-Brutalism CSS -->
    <link rel="stylesheet" href="neo-style.css">

    <style>
        /* Borang Carian */
        .search-card { 
            background-color: var(--white); 
            border: var(--border-thick); 
            border-radius: var(--radius-xl); 
            box-shadow: var(--shadow-solid); 
            padding: 22px; 
            margin-bottom: 25px; 
        }
        .search-title { 
            font-weight: 900; 
            text-transform: uppercase; 
            font-size: 1.15rem; 
            margin-bottom: 16px; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }
        .rent-toggle-btn {
            background-color: var(--white);
            color: var(--black);
            padding: 9px 18px;
            font-size: 0.9rem;
            font-weight: 900;
            text-transform: uppercase;
            border: var(--border-thick);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-solid);
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .rent-toggle-btn.active {
            background-color: var(--yellow) !important;
            box-shadow: var(--shadow-sm);
            transform: translate(2px, 2px);
        }
        .rent-toggle-btn:hover:not(.active) {
            background-color: #f0f0f0;
            transform: translate(-2px, -2px);
            box-shadow: var(--shadow-lg);
        }
        .form-grid { 
            display: grid; 
            grid-template-columns: 1fr 1fr auto; 
            gap: 14px; 
            align-items: end; 
        }

        /* Senarai Kereta & Modal */
        .section-heading { 
            font-size: 1.15rem; 
            font-weight: 900; 
            text-transform: uppercase; 
            margin-bottom: 6px; 
            display: flex; 
            align-items: center; 
            gap: 8px; 
            border-bottom: 2px solid var(--black); 
            padding-bottom: 6px; 
        }
        .section-desc { 
            font-weight: 700; 
            color: #555; 
            margin-bottom: 18px; 
            font-size: 0.875rem; 
        }
        .cars-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); 
            gap: 18px; 
            margin-bottom: 35px; 
        }
        .car-card { 
            background: var(--white); 
            border: var(--border-thick); 
            border-radius: var(--radius-lg); 
            box-shadow: var(--shadow-solid); 
            display: flex; 
            flex-direction: column; 
            overflow: hidden; 
            transition: var(--transition);
        }
        .car-card:hover {
            transform: translate(-2px, -2px);
            box-shadow: var(--shadow-lg);
        }
        .car-img { 
            height: 180px; 
            width: 100%; 
            object-fit: cover; 
            border-bottom: var(--border-thick); 
            background-color: #eee;
        } 
        .car-body { 
            padding: 16px; 
            display: flex; 
            flex-direction: column; 
            flex: 1; 
        }
        .car-title { 
            font-weight: 900; 
            font-size: 1.15rem; 
            text-transform: uppercase; 
            margin-bottom: 4px; 
        } 
        .car-plate { 
            font-weight: 700; 
            color: #666; 
            margin-bottom: 12px; 
            font-size: 0.85rem;
        }
        .badges-box { 
            display: flex; 
            gap: 6px; 
            margin-bottom: 14px; 
            flex-wrap: wrap; 
        } 
        .price-box { 
            background: var(--bg-color); 
            border: var(--border-thin); 
            border-radius: var(--radius-md); 
            padding: 10px 12px; 
            margin-top: auto; 
            margin-bottom: 14px; 
        } 
        .price-row { 
            display: flex; 
            justify-content: space-between; 
            font-weight: 700; 
            font-size: 0.85rem; 
        } 
        .price-total { 
            display: flex; 
            justify-content: space-between; 
            font-weight: 900; 
            font-size: 1.05rem; 
            border-top: 2px dashed var(--black); 
            margin-top: 6px; 
            padding-top: 6px; 
        }

        .modal-grid { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 20px; 
        } 
        .modal-divider { 
            border-right: 3px solid var(--black); 
            padding-right: 18px; 
        }
        .qr-img { 
            max-height: 170px; 
            width: auto; 
            border: var(--border-thin); 
            border-radius: var(--radius-md); 
            object-fit: contain; 
            margin: 10px 0; 
        }

        /* Loader Animation */
        .spinner { 
            border: 4px solid #eee; 
            border-top: 4px solid var(--black); 
            border-radius: 50%; 
            width: 36px; 
            height: 36px; 
            animation: spin 0.8s linear infinite; 
            margin: 0 auto 10px auto; 
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        @media (max-width: 768px) {
            .form-grid, .form-grid.grid-hourly { grid-template-columns: 1fr !important; gap: 10px; } 
            .cars-grid { grid-template-columns: 1fr; gap: 14px; } 
            .modal-grid { grid-template-columns: 1fr; gap: 14px; }
            .modal-divider { border-right: none; padding-right: 0; border-bottom: 2px solid var(--black); padding-bottom: 14px; }
            .search-card { padding: 16px; } 
        }
    </style>
</head>
<body>

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

        <!-- HEADING PANDUAN PENGGUNA (DENGAN BUTANG KEMBALI) -->
        <div style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h1 style="font-size: 1.6rem; font-weight: 900; text-transform: uppercase; margin-bottom: 4px; color: var(--black); display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-car-front-fill text-dark"></i> Cari & Tempah Kenderaan
                </h1>
                <p style="font-weight: 700; color: #555; font-size: 0.95rem; margin: 0; line-height: 1.5;">
                    Pilih jenis sewaan (Harian atau Jam), tetapkan tarikh & masa sewaan, kemudian tekan butang <strong>"Cari Kereta"</strong> untuk melihat kenderaan yang tersedia.
                </p>
            </div>
            <a href="dashboard.php" class="neo-btn btn-sm btn-yellow">
                <i class="bi bi-arrow-left"></i> Papan Pemuka
            </a>
        </div>

        <!-- LANGKAH 1: BORANG CARIAN AJAX -->
        <div class="search-card">
            <div class="search-title"><i class="bi bi-calendar-check text-dark"></i> Langkah 1: Pilih Jenis Sewaan, Tarikh & Masa</div>
            
            <!-- Pilihan Butang Jenis Sewaan -->
            <div style="margin-bottom: 18px;">
                <label class="form-label" style="display: block; margin-bottom: 8px;">Pilih Mod Sewaan:</label>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <button type="button" class="rent-toggle-btn active" id="btnTypeDaily" onclick="switchRentType('Daily')">
                        <i class="bi bi-calendar-range-fill"></i> Sewaan Harian (Daily)
                    </button>
                    <button type="button" class="rent-toggle-btn" id="btnTypeHourly" onclick="switchRentType('Hourly')">
                        <i class="bi bi-clock-fill"></i> Sewaan Jam (Hourly)
                    </button>
                </div>
            </div>

            <form id="ajaxSearchForm">
                <input type="hidden" name="rent_type" id="rent_type_input" value="Daily">

                <!-- Borang Carian Harian -->
                <div class="form-grid" id="dailyFormGrid">
                    <div class="form-group">
                        <label class="form-label">Tarikh & Masa Ambil</label>
                        <input type="datetime-local" class="form-control" name="start_date_daily" id="daily_start" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tarikh & Masa Pulang</label>
                        <input type="datetime-local" class="form-control" name="end_date_daily" id="daily_end" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="neo-btn btn-yellow" style="width: 100%;"><i class="bi bi-search me-1"></i> Cari Kereta</button>
                    </div>
                </div>

                <!-- Borang Carian Jam (Hourly) -->
                <div class="form-grid" id="hourlyFormGrid" style="display: none; grid-template-columns: 1.2fr 1fr 1fr auto;">
                    <div class="form-group">
                        <label class="form-label">Tarikh Sewaan</label>
                        <input type="date" class="form-control" id="hourly_date">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Masa Ambil (Mula)</label>
                        <input type="time" class="form-control" id="hourly_start_time">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Masa Pulang (Tamat)</label>
                        <input type="time" class="form-control" id="hourly_end_time">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="neo-btn btn-yellow" style="width: 100%;"><i class="bi bi-search me-1"></i> Cari Kereta</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- CONTAINER UNTUK HASIL CARIAN AJAX -->
        <div id="searchResultsContainer">
            <div style="text-align: center; padding: 3.5rem 1rem; border: 3px dashed var(--black); border-radius: var(--radius-xl); background: var(--white); box-shadow: var(--shadow-solid);">
                <i class="bi bi-search" style="font-size: 3rem; color: #444;"></i>
                <h3 style="font-weight: 900; text-transform: uppercase; margin-top: 10px;">Sila Tetapkan Tarikh & Masa Sewaan</h3>
                <p style="font-weight: 700; color: #666;">Tekan butang <strong>"Cari Kereta"</strong> untuk melihat kenderaan yang sedia disewa.</p>
            </div>
        </div>

    </main>

    <footer>
        &copy; <?php echo date("Y"); ?> SCRS PMU. SISTEM SEWAAN KERETA.
    </footer>

    <!-- SKRIP ASLI (VANILLA JS & AJAX) -->
    <script>
        // --- 1. SWITCH JENIS SEWAAN (DAILY / HOURLY) ---
        function switchRentType(type) {
            const btnDaily = document.getElementById('btnTypeDaily');
            const btnHourly = document.getElementById('btnTypeHourly');
            const rentTypeInput = document.getElementById('rent_type_input');
            const dailyGrid = document.getElementById('dailyFormGrid');
            const hourlyGrid = document.getElementById('hourlyFormGrid');
            const dailyStart = document.getElementById('daily_start');
            const dailyEnd = document.getElementById('daily_end');
            const hourlyDate = document.getElementById('hourly_date');
            const hourlyStartTime = document.getElementById('hourly_start_time');
            const hourlyEndTime = document.getElementById('hourly_end_time');

            rentTypeInput.value = type;

            if (type === 'Hourly') {
                btnHourly.classList.add('active');
                btnDaily.classList.remove('active');
                
                dailyGrid.style.display = 'none';
                hourlyGrid.style.display = 'grid';
                
                dailyStart.required = false;
                dailyEnd.required = false;
                dailyStart.disabled = true;
                dailyEnd.disabled = true;

                hourlyDate.required = true;
                hourlyStartTime.required = true;
                hourlyEndTime.required = true;
                hourlyDate.disabled = false;
                hourlyStartTime.disabled = false;
                hourlyEndTime.disabled = false;
            } else {
                btnDaily.classList.add('active');
                btnHourly.classList.remove('active');
                
                hourlyGrid.style.display = 'none';
                dailyGrid.style.display = 'grid';

                dailyStart.required = true;
                dailyEnd.required = true;
                dailyStart.disabled = false;
                dailyEnd.disabled = false;

                hourlyDate.required = false;
                hourlyStartTime.required = false;
                hourlyEndTime.required = false;
                hourlyDate.disabled = true;
                hourlyStartTime.disabled = true;
                hourlyEndTime.disabled = true;
            }
        }

        // --- 2. AJAX FETCH UNTUK CARIAN KERETA ---
        const searchForm = document.getElementById('ajaxSearchForm');
        const resultsContainer = document.getElementById('searchResultsContainer');

        if (searchForm) {
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const rentType = document.getElementById('rent_type_input').value;
                let startDateVal = '';
                let endDateVal = '';

                if (rentType === 'Hourly') {
                    const dateVal = document.getElementById('hourly_date').value;
                    const startVal = document.getElementById('hourly_start_time').value;
                    const endVal = document.getElementById('hourly_end_time').value;

                    if (!dateVal || !startVal || !endVal) {
                        alert('Sila lengkapkan tarikh dan masa sewaan jam!');
                        return;
                    }
                    startDateVal = `${dateVal}T${startVal}`;
                    endDateVal = `${dateVal}T${endVal}`;
                } else {
                    const startVal = document.getElementById('daily_start').value;
                    const endVal = document.getElementById('daily_end').value;

                    if (!startVal || !endVal) {
                        alert('Sila lengkapkan tarikh dan masa sewaan harian!');
                        return;
                    }
                    startDateVal = startVal;
                    endDateVal = endVal;
                }

                // Tunjuk Animasi Loading
                resultsContainer.innerHTML = `
                    <div style="text-align: center; padding: 4rem 1rem; border: 3px solid var(--black); border-radius: var(--radius-xl); background: var(--white); box-shadow: var(--shadow-solid);">
                        <div class="spinner"></div>
                        <h3 style="font-weight: 900; text-transform: uppercase;">Sedang Mencari Kenderaan...</h3>
                        <p style="font-weight: 700; color: #666;">Sila tunggu sebentar.</p>
                    </div>
                `;

                const params = new URLSearchParams();
                params.append('rent_type', rentType);
                params.append('start_date', startDateVal);
                params.append('end_date', endDateVal);
                params.append('ajax', '1');

                fetch('booking.php?' + params.toString())
                    .then(response => response.text())
                    .then(htmlData => {
                        resultsContainer.innerHTML = htmlData;
                    })
                    .catch(error => {
                        resultsContainer.innerHTML = `
                            <div class="neo-alert alert-danger" style="text-align: center; border-radius: var(--radius-lg);">
                                Ralat sistem: Gagal menyambung ke pelayan. Sila cuba lagi.
                            </div>
                        `;
                    });
            });
        }

        // --- 2. FUNGSI UI BIASA ---
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

        // Fungsi buka dan tutup Modal (Perlu berada di scope global supaya boleh dipanggil oleh HTML hasil AJAX)
        window.openModal = function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.add('show');
        };

        window.closeModal = function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.remove('show');
        };

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