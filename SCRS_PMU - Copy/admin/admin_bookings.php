<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/notifications.php';

// Semak jika admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$admin_id = $_SESSION['admin_id'] ?? 0;
$admin_username = $_SESSION['username'] ?? 'Admin';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/Exception.php';
require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';

$message = "";
$message_type = "";

// 1. CIPTA TEMPAHAN (CREATE)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_create_booking'])) {
    $student_id = (int)$_POST['student_id'];
    $car_id = (int)$_POST['car_id'];
    $rent_type = $_POST['rent_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $total_price = (float)$_POST['total_price'];
    $status = $_POST['status'] ?? 'Pending';

    $sql_ins = "INSERT INTO bookings (student_id, car_id, rent_type, start_date, end_date, total_price, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_ins = $conn->prepare($sql_ins);
    $stmt_ins->bind_param("iisssds", $student_id, $car_id, $rent_type, $start_date, $end_date, $total_price, $status);
    
    if ($stmt_ins->execute()) {
        $message = "Tempahan baharu berjaya dicipta!";
        $message_type = "success";
    } else {
        $message = "Ralat pangkalan data: " . $stmt_ins->error;
        $message_type = "danger";
    }
    $stmt_ins->close();
}

// 2. KEMASKINI TEMPAHAN (UPDATE)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_update_booking'])) {
    $booking_id = (int)$_POST['booking_id'];
    $rent_type = $_POST['rent_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $total_price = (float)$_POST['total_price'];
    $status = $_POST['status'];

    $sql_upd = "UPDATE bookings SET rent_type = ?, start_date = ?, end_date = ?, total_price = ?, status = ? WHERE id = ?";
    $stmt_upd = $conn->prepare($sql_upd);
    $stmt_upd->bind_param("sssdsi", $rent_type, $start_date, $end_date, $total_price, $status, $booking_id);

    if ($stmt_upd->execute()) {
        $message = "Maklumat tempahan berjaya dikemaskini!";
        $message_type = "success";

        // Jika status ditukar kepada Approved, hantar e-mel kepada pelajar
        if ($status === 'Approved') {
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
                    $mail->Username   = 'scrspmu@gmail.com';
                    $mail->Password   = 'cnpriksgpjbbldvj';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom('scrspmu@gmail.com', 'SCRS PMU');
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
                            <p style='color: #333;'>Permohonan sewaan kereta anda telah <strong>DILULUSKAN</strong>. Sila lakukan pembayaran sewaan dan muat naik resit pembayaran di sistem untuk meneruskan pengambilan kereta.</p>
                            
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
                                    <td style='padding: 8px; font-weight: bold;'>Tarikh Ambil:</td>
                                    <td style='padding: 8px;'>" . $start_fmt . "</td>
                                </tr>
                                <tr style='border-bottom: 2px solid #eee;'>
                                    <td style='padding: 8px; font-weight: bold;'>Tarikh Pulang:</td>
                                    <td style='padding: 8px;'>" . $end_fmt . "</td>
                                </tr>
                                <tr style='background: #f9f9f9; border-bottom: 2px solid #eee;'>
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
                } catch (Exception $e) {
                    // E-mel gagal
                }
            }
        }
    } else {
        $message = "Ralat mengemaskini tempahan: " . $stmt_upd->error;
        $message_type = "danger";
    }
    $stmt_upd->close();
}

// 3. PADAM TEMPAHAN (DELETE)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_delete_booking'])) {
    $booking_id = (int)$_POST['booking_id'];
    
    $stmt_del = $conn->prepare("DELETE FROM bookings WHERE id = ?");
    $stmt_del->bind_param("i", $booking_id);
    if ($stmt_del->execute()) {
        $message = "Rekod tempahan berjaya dipadam daripada sistem!";
        $message_type = "success";
    } else {
        $message = "Ralat memadam tempahan: " . $stmt_del->error;
        $message_type = "danger";
    }
    $stmt_del->close();
}

// Ambil Senarai Pelajar & Kereta untuk Borang Cipta Tempahan
$students_list = $conn->query("SELECT id, full_name, username, no_pendaftaran FROM students WHERE status = 'approved' ORDER BY full_name ASC");
$all_students = [];
while ($s = $students_list->fetch_assoc()) {
    $all_students[] = $s;
}

$cars_list = $conn->query("SELECT c.id, c.car_brand, c.car_model, c.car_plate, c.price_per_day, c.price_per_hour, p.full_name AS provider_name FROM cars c JOIN providers p ON c.provider_id = p.id ORDER BY c.car_model ASC");
$all_cars = [];
while ($c = $cars_list->fetch_assoc()) {
    $all_cars[] = $c;
}

// 4. BACA SENARAI TEMPAHAN (READ DENGAN CARIAN & PENAPIS)
$search = trim($_GET['search'] ?? '');
$filter_status = trim($_GET['filter_status'] ?? 'all');

$sql_query = "SELECT b.*, s.full_name AS student_name, s.username AS student_username, s.phone_no AS student_phone, s.no_pendaftaran,
                     c.car_brand, c.car_model, c.car_plate, c.car_image, c.price_per_day, c.price_per_hour,
                     p.full_name AS provider_name, p.phone_no AS provider_phone, p.qr_code_image AS provider_qr
              FROM bookings b
              JOIN students s ON b.student_id = s.id
              JOIN cars c ON b.car_id = c.id
              JOIN providers p ON c.provider_id = p.id
              WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $sql_query .= " AND (s.full_name LIKE ? OR s.username LIKE ? OR c.car_model LIKE ? OR c.car_plate LIKE ? OR p.full_name LIKE ?)";
    $searchTerm = "%" . $search . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sssss";
}

if ($filter_status !== 'all' && in_array($filter_status, ['Pending', 'Approved', 'Completed', 'Rejected'])) {
    $sql_query .= " AND b.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

$sql_query .= " ORDER BY b.id DESC";

$stmt_list = $conn->prepare($sql_query);
if (!empty($params)) {
    $stmt_list->bind_param($types, ...$params);
}
$stmt_list->execute();
$bookings_result = $stmt_list->get_result();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pengurusan Tempahan - Panel Admin</title>
    
    <!-- Ikon Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Master Neo-Brutalism CSS -->
    <link rel="stylesheet" href="../assets/css/neo-style.css">

    <style>
        /* PURE TEXT STATUS INDICATORS (NO BUTTON LOOK) */
        .badge-status {
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            padding: 0 !important;
            border-radius: 0 !important;
            font-size: 0.85rem !important;
            font-weight: 800 !important;
            cursor: default !important;
            user-select: none !important;
            box-shadow: none !important;
            border: none !important;
            background: transparent !important;
            background-color: transparent !important;
            text-transform: none !important;
        }
        .badge-status i {
            font-size: 1rem !important;
        }
        .badge-approved { 
            color: #15803d !important; 
            background: transparent !important;
        }
        .badge-pending { 
            color: #b45309 !important; 
            background: transparent !important;
        }
        .badge-rejected { 
            color: #dc2626 !important; 
            background: transparent !important;
        }
        .badge-completed { 
            color: #1d4ed8 !important; 
            background: transparent !important;
        }

        .page-header {
            margin-bottom: 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        /* FILTER CARD */
        .filter-card {
            background-color: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-solid);
            padding: 14px 18px;
            margin-bottom: 22px;
        }
        .filter-form {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .filter-input {
            border: var(--border-thin);
            border-radius: var(--radius-md);
            padding: 9px 12px;
            font-weight: 700;
            background-color: var(--bg-color);
            flex: 1;
            min-width: 200px;
        }
        .filter-select {
            border: var(--border-thin);
            border-radius: var(--radius-md);
            padding: 9px 12px;
            font-weight: 800;
            background-color: var(--bg-color);
        }

        /* TABLE */
        .table-card {
            background-color: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-solid);
            padding: 18px;
        }
        .neo-table-wrapper { overflow-x: auto; }
        .neo-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: var(--border-thin);
            border-radius: var(--radius-md);
            overflow: hidden;
            text-align: left;
            font-weight: 700;
            font-size: 0.85rem;
        }
        .neo-table th {
            background-color: var(--pink);
            border-bottom: var(--border-thin);
            border-right: var(--border-thin);
            padding: 9px 12px;
            text-transform: uppercase;
            font-weight: 900;
            font-size: 0.8rem;
            white-space: nowrap;
        }
        .neo-table td {
            border-bottom: var(--border-thin);
            border-right: var(--border-thin);
            padding: 9px 12px;
            vertical-align: middle;
        }
        .neo-table th:last-child, .neo-table td:last-child {
            border-right: none;
        }
        .neo-table tr:last-child td {
            border-bottom: none;
        }
        .neo-table tr:nth-child(even) { background-color: #fafafa; }

        .action-btns { display: flex; gap: 6px; flex-wrap: wrap; }
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
            box-shadow: var(--shadow-sm);
        }

        @media (max-width: 768px) {
            .filter-form { flex-direction: column; align-items: stretch; }
            .filter-input, .filter-select { width: 100%; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <header class="neo-navbar">
        <div class="neo-nav-left">
            <button class="menu-toggle-btn" id="open-sidebar" aria-label="Buka Menu"><i class="bi bi-list"></i></button>
            <a href="admin_dashboard.php" class="neo-brand">SCRS PMU</a>
        </div>
        <?php render_navbar_actions($conn, 'admin', $admin_id, $admin_username, '../'); ?>
    </header>

    <!-- SIDEBAR -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Panel Admin</h2>
            <button class="close-btn" id="close-sidebar" aria-label="Tutup Menu"><i class="bi bi-x-lg"></i></button>
        </div>
        <nav class="sidebar-nav">
            <a href="admin_dashboard.php" class="sidebar-link"><i class="bi bi-speedometer2"></i> Papan Pemuka</a>
            <a href="admin_students.php" class="sidebar-link"><i class="bi bi-mortarboard-fill"></i> Urus Pelajar</a>
            <a href="admin_providers.php" class="sidebar-link"><i class="bi bi-people-fill"></i> Urus Penyedia</a>
            <a href="admin_cars.php" class="sidebar-link"><i class="bi bi-car-front-fill"></i> Urus Kenderaan</a>
            <a href="admin_bookings.php" class="sidebar-link active"><i class="bi bi-calendar-check-fill"></i> Urus Tempahan</a>
            <a href="admin_staff.php" class="sidebar-link"><i class="bi bi-shield-shaded"></i> Urus Admin & JHEPP</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
            <div>
                <div class="page-title-row">
                    <a href="admin_dashboard.php" class="neo-btn btn-yellow btn-arrow-back" title="Papan Pemuka" aria-label="Kembali ke Papan Pemuka">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <h1 style="font-size: 1.55rem; font-weight: 900; text-transform: uppercase; margin: 0; color: var(--black); display: flex; align-items: center; gap: 10px;">
                        <i class="bi bi-calendar-check-fill"></i> Pengurusan Tempahan
                    </h1>
                </div>
                <p style="font-weight: 700; color: #555; margin: 5px 0 0 0; font-size: 0.92rem;">
                    Pantau semua rekod tempahan, cipta tempahan baharu, kemaskini status & bayaran, dan padam rekod.
                </p>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button class="neo-btn btn-sm btn-green" onclick="openCreateModal()">
                    <i class="bi bi-plus-circle-fill"></i> Cipta Tempahan Baharu
                </button>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="neo-alert alert-<?php echo $message_type; ?>">
                <i class="bi <?php echo ($message_type === 'success') ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <!-- FILTER & SEARCH -->
        <div class="filter-card">
            <form action="" method="GET" class="filter-form">
                <input type="text" name="search" class="filter-input" placeholder="Cari nama pelajar, username, model kereta, no plat, penyedia..." value="<?php echo htmlspecialchars($search); ?>">
                
                <select name="filter_status" class="filter-select">
                    <option value="all" <?php if ($filter_status === 'all') echo 'selected'; ?>>Semua Status</option>
                    <option value="Pending" <?php if ($filter_status === 'Pending') echo 'selected'; ?>>Pending (Menunggu)</option>
                    <option value="Approved" <?php if ($filter_status === 'Approved') echo 'selected'; ?>>Approved (Diluluskan)</option>
                    <option value="Completed" <?php if ($filter_status === 'Completed') echo 'selected'; ?>>Completed (Selesai)</option>
                    <option value="Rejected" <?php if ($filter_status === 'Rejected') echo 'selected'; ?>>Rejected (Ditolak)</option>
                </select>

                <button type="submit" class="neo-btn btn-blue" style="padding: 9px 16px;">
                    <i class="bi bi-search"></i> Cari
                </button>
                <a href="admin_bookings.php" class="neo-btn" style="background: #e0e0e0; padding: 9px 14px;">
                    <i class="bi bi-arrow-clockwise"></i> Reset
                </a>
            </form>
        </div>

        <!-- JADUAL SENARAI TEMPAHAN (READ) -->
        <div class="table-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="font-size: 1.1rem; font-weight: 900; text-transform: uppercase;">
                    Senarai Rekod Tempahan (<?php echo $bookings_result->num_rows; ?> rekod)
                </h3>
            </div>

            <div class="neo-table-wrapper">
                <table class="neo-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pelajar</th>
                            <th>Kenderaan</th>
                            <th>Penyedia</th>
                            <th>Tarikh Ambil & Pulang</th>
                            <th>Kadar & Jumlah (RM)</th>
                            <th>Resit & Gambar</th>
                            <th>Status</th>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($bookings_result->num_rows > 0): ?>
                            <?php while ($b = $bookings_result->fetch_assoc()): 
                                $b_status = $b['status'];
                                $badge_cls = 'badge-pending';
                                if ($b_status == 'Approved') $badge_cls = 'badge-approved';
                                elseif ($b_status == 'Completed') $badge_cls = 'badge-completed';
                                elseif ($b_status == 'Rejected') $badge_cls = 'badge-rejected';
                                $b_json = htmlspecialchars(json_encode($b), ENT_QUOTES, 'UTF-8');
                            ?>
                                <tr>
                                    <td>#<?php echo $b['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($b['student_name']); ?></strong><br>
                                        <small style="color: #666;">@<?php echo htmlspecialchars($b['student_username']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo (!empty($b['car_brand']) ? htmlspecialchars($b['car_brand']) . ' ' : '') . htmlspecialchars($b['car_model']); ?></strong><br>
                                        <small style="color: #666;"><code><?php echo htmlspecialchars($b['car_plate']); ?></code></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($b['provider_name']); ?></strong>
                                    </td>
                                    <td>
                                        <small>Ambil: <?php echo date('d/m/y h:iA', strtotime($b['start_date'])); ?></small><br>
                                        <small>Pulang: <?php echo date('d/m/y h:iA', strtotime($b['end_date'])); ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo $b['rent_type']; ?></small><br>
                                        <strong>RM <?php echo number_format($b['total_price'], 2); ?></strong>
                                    </td>
                                    <td>
                                        <?php if (!empty($b['payment_receipt'])): ?>
                                            <?php 
                                            $rec_url = $b['payment_receipt'];
                                            if (!str_starts_with($rec_url, 'http') && !str_starts_with($rec_url, '../')) {
                                                $rec_url = '../' . ltrim($rec_url, '/');
                                            }
                                            ?>
                                            <a href="<?php echo htmlspecialchars($rec_url); ?>" target="_blank" class="neo-badge badge-approved" style="margin-bottom: 2px;">
                                                <i class="bi bi-receipt"></i> Resit
                                            </a><br>
                                        <?php endif; ?>
                                        <?php if (!empty($b['return_image'])): ?>
                                            <?php 
                                            $ret_url = $b['return_image'];
                                            if (!str_starts_with($ret_url, 'http') && !str_starts_with($ret_url, '../')) {
                                                $ret_url = '../' . ltrim($ret_url, '/');
                                            }
                                            ?>
                                            <a href="<?php echo htmlspecialchars($ret_url); ?>" target="_blank" class="neo-badge badge-completed">
                                                <i class="bi bi-camera"></i> Pulangan
                                            </a>
                                        <?php endif; ?>
                                        <?php if (empty($b['payment_receipt']) && empty($b['return_image'])): ?>
                                            <small style="color: #999;">Tiada Fail</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($b['status'] === 'Approved'): ?>
                                            <span class="badge-status badge-approved"><i class="bi bi-check-circle-fill"></i> Diluluskan</span>
                                        <?php elseif ($b['status'] === 'Pending'): ?>
                                            <span class="badge-status badge-pending"><i class="bi bi-hourglass-split"></i> Menunggu</span>
                                        <?php elseif ($b['status'] === 'Completed'): ?>
                                            <span class="badge-status badge-completed"><i class="bi bi-flag-fill"></i> Selesai</span>
                                        <?php else: ?>
                                            <span class="badge-status badge-rejected"><i class="bi bi-x-circle-fill"></i> Ditolak</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="neo-btn btn-sm btn-blue" data-booking='<?php echo $b_json; ?>' onclick="viewBooking(this)" title="Lihat Penuh">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                            <button class="neo-btn btn-sm btn-yellow" data-booking='<?php echo $b_json; ?>' onclick="editBooking(this)" title="Kemaskini">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <form action="" method="POST" style="margin: 0;" onsubmit="return confirm('Adakah anda pasti ingin memadam rekod tempahan ini?');">
                                                <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                                <button type="submit" name="action_delete_booking" class="neo-btn btn-sm btn-pink" title="Padam">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 25px; color: #777;">Tiada rekod tempahan dijumpai.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- MODAL CREATE TEMPAHAN -->
    <div class="neo-modal-overlay" id="createModalOverlay" onclick="closeCreateModalOutside(event)">
        <div class="neo-modal" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 style="font-weight: 900; text-transform: uppercase; font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-plus-circle-fill text-success"></i> Cipta Tempahan Baharu
                </h3>
            </div>
            
            <form action="" method="POST">
                <input type="hidden" name="action_create_booking" value="1">
                
                <div class="form-group">
                    <label class="form-label">Pilih Pelajar:</label>
                    <select name="student_id" class="form-control" required>
                        <option value="">-- Pilih Pelajar --</option>
                        <?php foreach ($all_students as $st): ?>
                            <option value="<?php echo $st['id']; ?>"><?php echo htmlspecialchars($st['full_name']); ?> (<?php echo htmlspecialchars($st['no_pendaftaran']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Pilih Kenderaan:</label>
                    <select name="car_id" class="form-control" required>
                        <option value="">-- Pilih Kenderaan --</option>
                        <?php foreach ($all_cars as $cr): ?>
                            <option value="<?php echo $cr['id']; ?>">
                                <?php echo (!empty($cr['car_brand']) ? htmlspecialchars($cr['car_brand']) . ' ' : '') . htmlspecialchars($cr['car_model']); ?> (<?php echo htmlspecialchars($cr['car_plate']); ?>) - RM<?php echo number_format($cr['price_per_day'], 2); ?>/hari
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label">Jenis Sewaan:</label>
                        <select name="rent_type" class="form-control" required>
                            <option value="Daily">Daily (Harian)</option>
                            <option value="Hourly">Hourly (Jam)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jumlah Bayaran (RM):</label>
                        <input type="number" step="0.01" name="total_price" class="form-control" required placeholder="Contoh: 100.00">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label">Tarikh & Masa Ambil:</label>
                        <input type="datetime-local" name="start_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tarikh & Masa Pulang:</label>
                        <input type="datetime-local" name="end_date" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Status Tempahan:</label>
                    <select name="status" class="form-control" required>
                        <option value="Pending">Pending (Menunggu)</option>
                        <option value="Approved">Approved (Diluluskan)</option>
                        <option value="Completed">Completed (Selesai)</option>
                        <option value="Rejected">Rejected (Ditolak)</option>
                    </select>
                </div>

                <div style="margin-top: 15px; display: flex; gap: 10px;">
                    <button type="submit" class="neo-btn btn-green" style="flex: 1; justify-content: center;">
                        <i class="bi bi-check-lg"></i> Simpan Tempahan
                    </button>
                    <button type="button" class="neo-btn btn-pink" onclick="closeCreateModal()">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT TEMPAHAN (UPDATE) -->
    <div class="neo-modal-overlay" id="editModalOverlay" onclick="closeEditModalOutside(event)">
        <div class="neo-modal" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 style="font-weight: 900; text-transform: uppercase; font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-pencil-square text-warning"></i> Kemaskini Tempahan
                </h3>
            </div>
            
            <form action="" method="POST">
                <input type="hidden" name="action_update_booking" value="1">
                <input type="hidden" name="booking_id" id="editBookingId">

                <div style="background: var(--bg-color); border: 2px solid var(--black); padding: 10px; margin-bottom: 12px; font-size: 0.85rem;">
                    <div>Pelajar: <strong id="editStudentLabel"></strong></div>
                    <div>Kenderaan: <strong id="editCarLabel"></strong></div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label">Jenis Sewaan:</label>
                        <select name="rent_type" id="editRentType" class="form-control" required>
                            <option value="Daily">Daily (Harian)</option>
                            <option value="Hourly">Hourly (Jam)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jumlah Bayaran (RM):</label>
                        <input type="number" step="0.01" name="total_price" id="editTotalPrice" class="form-control" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label">Tarikh Ambil:</label>
                        <input type="datetime-local" name="start_date" id="editStartDate" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tarikh Pulang:</label>
                        <input type="datetime-local" name="end_date" id="editEndDate" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Status Tempahan:</label>
                    <select name="status" id="editStatus" class="form-control" required>
                        <option value="Pending">Pending (Menunggu)</option>
                        <option value="Approved">Approved (Diluluskan)</option>
                        <option value="Completed">Completed (Selesai)</option>
                        <option value="Rejected">Rejected (Ditolak)</option>
                    </select>
                </div>

                <div style="margin-top: 15px; display: flex; gap: 10px;">
                    <button type="submit" class="neo-btn btn-yellow" style="flex: 1; justify-content: center;">
                        <i class="bi bi-save"></i> Kemaskini Tempahan
                    </button>
                    <button type="button" class="neo-btn btn-pink" onclick="closeEditModal()">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL VIEW DETAILS -->
    <div class="neo-modal-overlay" id="viewModalOverlay" onclick="closeViewModalOutside(event)">
        <div class="neo-modal" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 style="font-weight: 900; text-transform: uppercase; font-size: 1.2rem;">
                    <i class="bi bi-clipboard-check-fill text-primary"></i> Butiran Lengkap Tempahan
                </h3>
            </div>
            <div style="font-weight: 700; font-size: 0.9rem; display: flex; flex-direction: column; gap: 8px;">
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 6px 0;">
                    <span style="color: #666;">ID Tempahan:</span>
                    <span id="viewId" style="font-weight: 900;"></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 6px 0;">
                    <span style="color: #666;">Pelajar:</span>
                    <span id="viewStudent" style="font-weight: 900;"></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 6px 0;">
                    <span style="color: #666;">Kenderaan:</span>
                    <span id="viewCar" style="font-weight: 900;"></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 6px 0;">
                    <span style="color: #666;">Penyedia:</span>
                    <span id="viewProvider"></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 6px 0;">
                    <span style="color: #666;">Tempoh Sewaan:</span>
                    <span id="viewDates"></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 6px 0;">
                    <span style="color: #666;">Jenis & Jumlah:</span>
                    <span id="viewPrice" style="color: #007700; font-weight: 900;"></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 6px 0;">
                    <span style="color: #666;">Status Tempahan:</span>
                    <span id="viewStatus"></span>
                </div>

                <div style="margin-top: 10px; font-weight: 900; text-transform: uppercase; font-size: 0.85rem; border-bottom: 2px solid var(--black); padding-bottom: 4px;">
                    Bukti Resit & Pulangan:
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 6px 0;">
                    <span style="color: #666;">Resit Pembayaran:</span>
                    <span id="viewReceiptFile"></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 6px 0;">
                    <span style="color: #666;">Gambar Pulangan:</span>
                    <span id="viewReturnImgFile"></span>
                </div>

                <div style="margin-top: 15px;">
                    <button class="neo-btn btn-pink" style="width: 100%; justify-content: center;" onclick="closeViewModal()">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        &copy; <?php echo date("Y"); ?> SCRS PMU. PANEL PENTADBIR SISTEM.
    </footer>

    <!-- SKRIP ASLI -->
    <script>
        const openSidebarBtn = document.getElementById('open-sidebar');
        const closeSidebarBtn = document.getElementById('close-sidebar');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');

        function openSidebar() { sidebar.classList.add('open'); sidebarOverlay.classList.add('show'); }
        function closeSidebar() { sidebar.classList.remove('open'); sidebarOverlay.classList.remove('show'); }

        openSidebarBtn.addEventListener('click', openSidebar);
        closeSidebarBtn.addEventListener('click', closeSidebar);
        sidebarOverlay.addEventListener('click', closeSidebar);

        const profileToggle = document.getElementById('profile-toggle');
        const profileMenu = document.getElementById('profile-menu');
        if (profileToggle && profileMenu) {
            profileToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                profileMenu.classList.toggle('show');
            });
            document.addEventListener('click', () => {
                profileMenu.classList.remove('show');
            });
        }

        // CREATE MODAL
        function openCreateModal() { document.getElementById('createModalOverlay').classList.add('show'); }
        function closeCreateModal() { document.getElementById('createModalOverlay').classList.remove('show'); }
        function closeCreateModalOutside(e) { if (e.target.id === 'createModalOverlay') closeCreateModal(); }

        // EDIT MODAL
        function editBooking(btn) {
            const data = JSON.parse(btn.getAttribute('data-booking'));
            document.getElementById('editBookingId').value = data.id;
            document.getElementById('editStudentLabel').textContent = data.student_name + ' (@' + data.student_username + ')';
            document.getElementById('editCarLabel').textContent = (data.car_brand ? data.car_brand + ' ' : '') + data.car_model + ' (' + data.car_plate + ')';
            document.getElementById('editRentType').value = data.rent_type;
            document.getElementById('editTotalPrice').value = parseFloat(data.total_price).toFixed(2);
            document.getElementById('editStartDate').value = data.start_date.replace(' ', 'T').substring(0, 16);
            document.getElementById('editEndDate').value = data.end_date.replace(' ', 'T').substring(0, 16);
            document.getElementById('editStatus').value = data.status;
            document.getElementById('editModalOverlay').classList.add('show');
        }
        function closeEditModal() { document.getElementById('editModalOverlay').classList.remove('show'); }
        function closeEditModalOutside(e) { if (e.target.id === 'editModalOverlay') closeEditModal(); }

        // VIEW MODAL
        function viewBooking(btn) {
            const data = JSON.parse(btn.getAttribute('data-booking'));
            document.getElementById('viewId').textContent = '#' + data.id;
            document.getElementById('viewStudent').textContent = data.student_name + ' (' + data.no_pendaftaran + ')';
            document.getElementById('viewCar').textContent = data.car_brand + ' ' + data.car_model + ' (' + data.car_plate + ')';
            document.getElementById('viewProvider').textContent = data.provider_name;
            document.getElementById('viewDates').textContent = data.start_date + ' -> ' + data.end_date;
            const statusBookingMap = {
                'Approved': '<span class="badge-status badge-approved"><i class="bi bi-check-circle-fill"></i> Diluluskan</span>',
                'Pending': '<span class="badge-status badge-pending"><i class="bi bi-hourglass-split"></i> Menunggu</span>',
                'Completed': '<span class="badge-status badge-completed"><i class="bi bi-flag-fill"></i> Selesai</span>',
                'Rejected': '<span class="badge-status badge-rejected"><i class="bi bi-x-circle-fill"></i> Ditolak</span>'
            };
            document.getElementById('viewStatus').innerHTML = statusBookingMap[data.status] || data.status.toUpperCase();

            function makeLink(url) {
                if (url && url.trim() !== '') {
                    const cleanUrl = (url.startsWith('http') || url.startsWith('../')) ? url : ('../' + url.replace(/^\//, ''));
                    return '<a href="' + cleanUrl + '" target="_blank" class="neo-badge badge-approved"><i class="bi bi-file-earmark-image"></i> Lihat Fail</a>';
                }
                return '<span style="color:#999;">Tiada Fail</span>';
            }

            document.getElementById('viewReceiptFile').innerHTML = makeLink(data.payment_receipt);
            document.getElementById('viewReturnImgFile').innerHTML = makeLink(data.return_image);

            document.getElementById('viewModalOverlay').classList.add('show');
        }
        function closeViewModal() { document.getElementById('viewModalOverlay').classList.remove('show'); }
        function closeViewModalOutside(e) { if (e.target.id === 'viewModalOverlay') closeViewModal(); }
    </script>
</body>
</html>
