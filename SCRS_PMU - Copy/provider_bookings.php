<?php
session_start();
require 'db.php';

// Semak jika pengguna telah log masuk dan merupakan Penyedia Kereta (Provider)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'provider') {
    header("Location: index.php");
    exit();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

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
        $sql_stu = "SELECT s.full_name AS student_name, s.email AS student_email,
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

        if ($stu_data && !empty($stu_data['student_email'])) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $host = $_SERVER['HTTP_HOST'];
            $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
            $student_page_link = $protocol . $host . $uri . "/my_bookings.php";

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

    $sql_rej = "UPDATE bookings SET status = 'Rejected' WHERE id = ? AND car_id IN (SELECT id FROM cars WHERE provider_id = ?)";
    $stmt_rej = $conn->prepare($sql_rej);
    $stmt_rej->bind_param("ii", $booking_id, $provider_id);
    
    if ($stmt_rej->execute()) {
        $message = "<div class='neo-alert alert-danger'><i class='bi bi-x-circle-fill me-2'></i>Permohonan tempahan telah <strong>DITOLAK</strong>.</div>";
    } else {
        $message = "<div class='neo-alert alert-danger'>Ralat: " . $stmt_rej->error . "</div>";
    }
    $stmt_rej->close();
}

// 3. PROSES SAHKAN PEMULANGAN KERETA (COMPLETE)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['complete_booking'])) {
    $booking_id = (int)$_POST['booking_id'];

    $sql_comp = "UPDATE bookings SET status = 'Completed' WHERE id = ? AND car_id IN (SELECT id FROM cars WHERE provider_id = ?)";
    $stmt_comp = $conn->prepare($sql_comp);
    $stmt_comp->bind_param("ii", $booking_id, $provider_id);
    
    if ($stmt_comp->execute()) {
        $message = "<div class='neo-alert alert-success'><i class='bi bi-check-circle-fill me-2'></i>Berjaya: Tempahan telah ditandakan sebagai <strong>SELESAI (Completed)</strong>. Terima kasih!</div>";
    } else {
        $message = "<div class='neo-alert alert-danger'>Ralat: " . $stmt_comp->error . "</div>";
    }
    $stmt_comp->close();
}

// AMBIL TEMPAHAN SEMASA (PENDING & APPROVED)
$sql_bookings = "SELECT b.*, c.car_model, c.car_plate, c.car_image, c.price_per_day, c.price_per_hour,
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
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Tempahan Semasa - SCRS PMU</title>
    
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

        .neo-alert {
            border: var(--border-thick); box-shadow: 4px 4px 0px var(--black);
            padding: 15px 20px; font-weight: 800; margin-bottom: 25px; text-transform: uppercase;
        }
        .alert-success { background-color: var(--green); }
        .alert-danger { background-color: var(--pink); }

        /* Kad Tempahan */
        .booking-card {
            background: var(--white);
            border: var(--border-thick);
            box-shadow: var(--shadow-solid);
            margin-bottom: 25px;
            display: flex;
            flex-direction: row;
            overflow: hidden;
        }
        .booking-img {
            width: 280px;
            height: 100%;
            min-height: 220px;
            object-fit: cover;
            border-right: var(--border-thick);
        }
        .booking-body {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid var(--black);
            padding-bottom: 12px;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .car-name { font-size: 1.3rem; font-weight: 900; text-transform: uppercase; }
        .car-plate { font-weight: 700; color: #555; }

        .neo-badge {
            border: 2px solid var(--black);
            padding: 4px 10px;
            font-weight: 900;
            text-transform: uppercase;
            font-size: 0.8rem;
            box-shadow: none;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .badge-pending { background-color: var(--yellow); }
        .badge-approved { background-color: var(--green); }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }
        .detail-item { font-weight: 700; font-size: 0.9rem; }
        .detail-label { display: block; text-transform: uppercase; font-size: 0.75rem; color: #666; font-weight: 800; }
        .detail-value { display: flex; align-items: center; gap: 6px; margin-top: 2px; }

        .price-box {
            background-color: var(--bg-color);
            border: 3px solid var(--black);
            padding: 10px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 900;
            font-size: 1.1rem;
            margin-top: 10px;
        }

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
            .booking-card { flex-direction: column; }
            .booking-img { width: 100%; height: 200px; border-right: none; border-bottom: var(--border-thick); }
            .details-grid { grid-template-columns: 1fr; gap: 10px; }
            .main-content { padding: 1rem 10px; }
            .neo-brand { font-size: 1.2rem; }
            .profile-btn { padding: 6px 10px; font-size: 0.85rem; }
            .header-flex { flex-direction: column; align-items: stretch !important; gap: 10px !important; }
            .mobile-btn-full { width: 100% !important; justify-content: center !important; text-align: center; }
            .mobile-btn-group { flex-direction: column !important; }
            .mobile-btn-group button, .mobile-btn-group a { width: 100% !important; justify-content: center !important; }
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
            <a href="provider_bookings.php" class="sidebar-link active"><i class="bi bi-clipboard-check-fill"></i> Tempahan Semasa</a>
            <a href="provider_history.php" class="sidebar-link"><i class="bi bi-clock-history"></i> Sejarah Rekod</a>
        </nav>
    </aside>

    <!-- KANDUNGAN UTAMA -->
    <main class="main-content">
        
        <?php echo $message; ?>

        <!-- HEADING PANDUAN PENGGUNA (TANPA KOTAK) -->
        <div style="margin-bottom: 25px;">
            <div class="header-flex" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 6px;">
                <h1 style="font-size: 1.6rem; font-weight: 900; text-transform: uppercase; margin: 0; color: var(--black); display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-clipboard-check-fill text-dark"></i> Tempahan Semasa Pelajar
                </h1>
                <a href="provider_history.php" class="neo-btn mobile-btn-full" style="background: var(--yellow); padding: 8px 16px; font-size: 0.85rem;">
                    <i class="bi bi-clock-history me-1"></i> Sejarah Rekod
                </a>
            </div>
            <p style="font-weight: 700; color: #555; font-size: 0.95rem; margin: 0; line-height: 1.5;">
                Semak permohonan tempahan kenderaan yang masuk daripada pelajar. Anda boleh meluluskan atau menolak permohonan, menyemak resit bayaran, dan mengesahkan pemulangan kenderaan.
            </p>
        </div>

        <?php if ($result_bookings->num_rows > 0): ?>
            <?php while ($booking = $result_bookings->fetch_assoc()): 
                $is_approved = ($booking['status'] == 'Approved');
                $badge_class = $is_approved ? 'badge-approved' : 'badge-pending';
                $status_text = $is_approved ? 'Diluluskan (Sedang Berjalan)' : 'Menunggu Kelulusan Anda';
                $status_icon = $is_approved ? 'bi-check-circle-fill' : 'bi-hourglass-split';
                
                $phone = preg_replace('/[^0-9]/', '', $booking['student_phone']);
                if (strpos($phone, '0') === 0) {
                    $phone = '6' . $phone;
                }
                $has_receipt = (!empty($booking['payment_receipt']) && file_exists($booking['payment_receipt']));
                $has_return_img = (!empty($booking['return_image']) && file_exists($booking['return_image']));
            ?>
                <div class="booking-card">
                    <img src="<?php echo htmlspecialchars($booking['car_image']); ?>" class="booking-img" alt="Kereta">
                    <div class="booking-body">
                        <div>
                            <div class="booking-header">
                                <div>
                                    <h3 class="car-name"><?php echo htmlspecialchars($booking['car_model']); ?></h3>
                                    <span class="car-plate"><i class="bi bi-123 me-1"></i> <?php echo htmlspecialchars($booking['car_plate']); ?></span>
                                </div>
                                <span class="neo-badge <?php echo $badge_class; ?>">
                                    <i class="bi <?php echo $status_icon; ?>"></i> <?php echo $status_text; ?>
                                </span>
                            </div>

                            <div class="details-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Pemohon (Pelajar)</span>
                                    <div class="detail-value">
                                        <i class="bi bi-person-fill text-primary"></i> 
                                        <a href="javascript:void(0)" onclick="showStudentModal('<?php echo htmlspecialchars($booking['student_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($booking['student_username'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($booking['student_email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($booking['student_phone'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($booking['student_matrix'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($booking['student_id_file'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($booking['student_license_file'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($booking['student_profile_pic'] ?? '', ENT_QUOTES); ?>')" style="color: #0055ff; font-weight: 900; text-decoration: underline; cursor: pointer;">
                                            <?php echo htmlspecialchars($booking['student_name']); ?> <i class="bi bi-info-circle-fill ms-1 fs-6"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Jenis Sewaan</span>
                                    <div class="detail-value"><i class="bi bi-clock"></i> <?php echo ($booking['rent_type'] == 'Daily') ? 'Harian (Daily)' : 'Jam (Hourly)'; ?></div>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Tarikh & Masa Ambil</span>
                                    <div class="detail-value"><i class="bi bi-calendar-check text-success"></i> <?php echo date('d M Y, h:i A', strtotime($booking['start_date'])); ?></div>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Tarikh & Masa Pulang</span>
                                    <div class="detail-value"><i class="bi bi-calendar-x text-danger"></i> <?php echo date('d M Y, h:i A', strtotime($booking['end_date'])); ?></div>
                                </div>
                            </div>

                            <div class="price-box">
                                <span>Jumlah Bayaran:</span>
                                <span style="color: #007700;">RM <?php echo number_format($booking['total_price'], 2); ?></span>
                            </div>
                        </div>

                        <!-- TINDAKAN PENYEDIA BERDASARKAN STATUS -->
                        <div style="margin-top: 20px; border-top: 2px dashed var(--black); padding-top: 15px;">
                            
                            <?php if (!$is_approved): ?>
                                <!-- STATUS PENDING: BUTANG LULUS / TOLAK -->
                                <div style="display: flex; gap: 10px; flex-wrap: wrap;" class="mobile-btn-group">
                                    <form action="" method="POST" style="flex: 1;" onsubmit="return confirm('Adakah anda pasti ingin meluluskan tempahan ini?');">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <button type="submit" name="approve_booking" class="neo-btn btn-green" style="width: 100%;">
                                            <i class="bi bi-check-circle-fill me-1"></i> Luluskan Tempahan
                                        </button>
                                    </form>

                                    <form action="" method="POST" style="flex: 1;" onsubmit="return confirm('Adakah anda pasti ingin menolak tempahan ini?');">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <button type="submit" name="reject_booking" class="neo-btn btn-pink" style="width: 100%;">
                                            <i class="bi bi-x-circle-fill me-1"></i> Tolak Permohonan
                                        </button>
                                    </form>
                                </div>

                            <?php else: ?>
                                <!-- STATUS APPROVED: PANTAU BAYARAN, WHATSAPP & PEMULANGAN -->
                                <div style="display: flex; flex-direction: column; gap: 12px;">
                                    
                                    <!-- RESIT BAYARAN -->
                                    <div style="background: var(--white); border: 2px solid var(--black); padding: 12px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                                        <div>
                                            <span style="font-weight: 800; font-size: 0.85rem; display: block;">Bukti Pembayaran Pelajar:</span>
                                            <?php if ($has_receipt): ?>
                                                <span style="color: #2e7d32; font-weight: 900; font-size: 0.8rem;"><i class="bi bi-check-circle-fill me-1"></i> Resit telah dihantar oleh pelajar</span>
                                            <?php else: ?>
                                                <span style="color: #c62828; font-weight: 800; font-size: 0.8rem;"><i class="bi bi-hourglass-split me-1"></i> Menunggu pelajar memuat naik resit</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($has_receipt): ?>
                                            <a href="<?php echo htmlspecialchars($booking['payment_receipt']); ?>" target="_blank" class="neo-btn btn-blue" style="padding: 6px 12px; font-size: 0.8rem;">
                                                <i class="bi bi-receipt me-1"></i> Lihat Resit Bayaran
                                            </a>
                                        <?php endif; ?>
                                    </div>

                                    <!-- GAMBAR PULANGAN -->
                                    <div style="background: var(--white); border: 2px solid var(--black); padding: 12px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                                        <div>
                                            <span style="font-weight: 800; font-size: 0.85rem; display: block;">Gambar Pemulangan Kenderaan:</span>
                                            <?php if ($has_return_img): ?>
                                                <span style="color: #2e7d32; font-weight: 900; font-size: 0.8rem;"><i class="bi bi-check-circle-fill me-1"></i> Gambar telah dimuat naik</span>
                                            <?php else: ?>
                                                <span style="color: #666; font-weight: 800; font-size: 0.8rem;"><i class="bi bi-clock me-1"></i> Kereta sedang dalam tempoh penggunaan</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($has_return_img): ?>
                                            <a href="<?php echo htmlspecialchars($booking['return_image']); ?>" target="_blank" class="neo-btn btn-yellow" style="padding: 6px 12px; font-size: 0.8rem;">
                                                <i class="bi bi-image me-1"></i> Lihat Gambar Pulangan
                                            </a>
                                        <?php endif; ?>
                                    </div>

                                    <!-- BUTANG WHATSAPP & SELESAIKAN TEMPAHAN -->
                                    <div style="display: flex; gap: 10px; flex-wrap: wrap;" class="mobile-btn-group">
                                        <a href="https://wa.me/<?php echo $phone; ?>?text=Hai%20<?php echo urlencode($booking['student_name']); ?>,%20saya%20penyedia%20kereta%20<?php echo urlencode($booking['car_model']); ?>%20SCRS%20PMU." target="_blank" class="neo-btn btn-green" style="flex: 1;">
                                            <i class="bi bi-whatsapp"></i> Hubungi Pelajar (WhatsApp)
                                        </a>

                                        <form action="" method="POST" style="flex: 1;" onsubmit="return confirm('Adakah anda pasti ingin menandakan tempahan ini sebagai SELESAI?');">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <button type="submit" name="complete_booking" class="neo-btn btn-blue" style="width: 100%;">
                                                <i class="bi bi-check-all me-1"></i> Sahkan Pemulangan / Selesai
                                            </button>
                                        </form>
                                    </div>

                                </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
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
