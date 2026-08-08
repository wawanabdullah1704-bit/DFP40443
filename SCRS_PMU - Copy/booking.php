<?php
session_start();
require 'db.php';

// Semak jika pengguna telah log masuk dan merupakan seorang pelajar
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['username'];
$message = "";

// 1. PROSES PENGHANTARAN TEMPAHAN & RESIT (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_booking'])) {
    $car_id = (int)$_POST['car_id'];
    $rent_type = htmlspecialchars($_POST['rent_type']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $total_price = (float)$_POST['total_price'];
    $status = 'Pending';

    // Pengurusan muat naik resit
    $targetDir = "uploads/receipts/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $receiptName = basename($_FILES["payment_receipt"]["name"]);
    $newReceiptName = "Resit_" . $student_id . "_" . time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $receiptName);
    $targetPath = $targetDir . $newReceiptName;

    if (move_uploaded_file($_FILES["payment_receipt"]["tmp_name"], $targetPath)) {
        
        $sql_book = "INSERT INTO bookings (student_id, car_id, rent_type, start_date, end_date, total_price, payment_receipt, status) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_book = $conn->prepare($sql_book);
        
        // Bind 8 parameter
        $stmt_book->bind_param("iisssdss", $student_id, $car_id, $rent_type, $start_date, $end_date, $total_price, $targetPath, $status);

        if ($stmt_book->execute()) {
            $message = "<div class='neo-alert alert-success'><i class='bi bi-check-circle-fill me-2'></i>Tempahan dan resit berjaya dihantar! Sila tunggu pengesahan daripada Penyedia Kereta.</div>";
        } else {
            $message = "<div class='neo-alert alert-danger'>Ralat pangkalan data: " . $stmt_book->error . "</div>";
        }
        $stmt_book->close();

    } else {
        $message = "<div class='neo-alert alert-danger'>Ralat: Gagal memuat naik resit pembayaran.</div>";
    }
}

// 2. PROSES CARIAN & PENAPISAN (GET)
$search_start = $_GET['start_date'] ?? '';
$search_end = $_GET['end_date'] ?? '';
$rent_type = $_GET['rent_type'] ?? '';
$result_cars = null;
$search_error = "";
$duration = 0;

if ($search_start && $search_end && $rent_type) {
    $start_ts = strtotime($search_start);
    $end_ts = strtotime($search_end);

    if ($start_ts >= $end_ts) {
        $search_error = "Tarikh/Masa pemulangan mestilah selepas tarikh/masa pengambilan.";
    } else {
        // Kira tempoh masa
        if ($rent_type === 'Hourly') {
            $duration = ceil(($end_ts - $start_ts) / 3600); // Jam
        } else {
            $duration = ceil(($end_ts - $start_ts) / 86400); // Hari
            if ($duration < 1) $duration = 1;
        }

        // Cari kereta tersedia dan gabung maklumat QR Provider
        $sql_cars = "SELECT c.*, p.qr_code_image, p.full_name AS provider_name 
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
    }
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Tempahan Kereta - SCRS PMU</title>
    
    <!-- Ikon Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700;900&display=swap" rel="stylesheet">

    <!-- CSS NEO-BRUTALISM KESELURUHAN -->
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

        /* --- NAVBAR --- */
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
        .menu-toggle-btn { font-size: 2rem; color: var(--black); background: none; border: none; cursor: pointer; }
        .neo-brand { font-size: 1.5rem; font-weight: 900; letter-spacing: 2px; text-transform: uppercase; }

        /* Profil Dropdown */
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
        .profile-btn:active { transform: translate(4px, 4px); box-shadow: var(--shadow-active); }

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
            transition: background 0.1s;
            text-decoration: none;
        }
        .dropdown-item:last-child { border-bottom: none; background-color: var(--pink); }
        .dropdown-item:hover { background-color: var(--yellow); }
        .dropdown-item:last-child:hover { background-color: #ff33aa; }

        /* --- SIDEBAR --- */
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

        /* --- KANDUNGAN UTAMA --- */
        .main-content { flex: 1; padding: 2rem 20px; max-width: 1200px; margin: 0 auto; width: 100%; }

        /* Kotak Notifikasi / Alert */
        .neo-alert {
            border: var(--border-thick); box-shadow: 4px 4px 0px var(--black);
            padding: 15px 20px; font-weight: 800; margin-bottom: 25px; text-transform: uppercase;
        }
        .alert-success { background-color: var(--green); }
        .alert-danger { background-color: var(--pink); }

        /* Borang Carian (Step 1) */
        .search-card {
            background-color: var(--white); border: var(--border-thick);
            box-shadow: var(--shadow-solid); padding: 25px; margin-bottom: 30px;
        }
        .search-title { font-weight: 900; text-transform: uppercase; font-size: 1.2rem; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        
        .form-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; align-items: end; }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-label { font-weight: 800; text-transform: uppercase; font-size: 0.85rem; }
        .form-control, .form-select {
            border: 3px solid var(--black); padding: 10px; font-weight: 700;
            background: var(--bg-color); outline: none; border-radius: 0; width: 100%;
        }
        .form-control:focus, .form-select:focus { background: var(--white); box-shadow: 3px 3px 0px var(--black); }

        .neo-btn {
            background-color: var(--yellow); border: 3px solid var(--black); box-shadow: 4px 4px 0px var(--black);
            font-weight: 900; text-transform: uppercase; padding: 11px 20px; cursor: pointer; transition: var(--transition);
            display: inline-block; text-align: center;
        }
        .neo-btn:hover { transform: translate(-2px, -2px); box-shadow: 6px 6px 0px var(--black); }
        .neo-btn:active { transform: translate(4px, 4px); box-shadow: var(--shadow-active); }
        .btn-green { background-color: var(--green); }
        .btn-red { background-color: var(--pink); }

        /* Senarai Kereta Grid (Step 2) */
        .section-heading { font-weight: 900; text-transform: uppercase; margin-bottom: 5px; display: flex; align-items: center; gap: 10px; }
        .section-desc { font-weight: 700; color: #555; margin-bottom: 25px; }

        .cars-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 40px; }
        .car-card {
            background: var(--white); border: var(--border-thick); box-shadow: var(--shadow-solid);
            display: flex; flex-direction: column; overflow: hidden;
        }
        .car-img { height: 200px; width: 100%; object-fit: cover; border-bottom: var(--border-thick); }
        .car-body { padding: 20px; display: flex; flex-direction: column; flex: 1; }
        .car-title { font-weight: 900; font-size: 1.2rem; text-transform: uppercase; margin-bottom: 5px; }
        .car-plate { font-weight: 700; color: #666; margin-bottom: 15px; }
        
        .badges-box { display: flex; gap: 8px; margin-bottom: 15px; flex-wrap: wrap; }
        .neo-badge { border: 2px solid var(--black); padding: 4px 8px; font-weight: 700; font-size: 0.75rem; background: var(--bg-color); text-transform: uppercase; }

        .price-box {
            background: var(--bg-color); border: 3px solid var(--black); padding: 12px; margin-top: auto; margin-bottom: 15px;
        }
        .price-row { display: flex; justify-content: space-between; font-weight: 700; font-size: 0.9rem; }
        .price-total { display: flex; justify-content: space-between; font-weight: 900; font-size: 1.1rem; border-top: 2px dashed var(--black); margin-top: 8px; padding-top: 8px; }

        /* --- MODAL / POPUP PAYMENT --- */
        .neo-modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6); z-index: 2000; display: none; align-items: center; justify-content: center; padding: 15px;
        }
        .neo-modal-overlay.show { display: flex; }
        
        .neo-modal {
            background: var(--white); border: var(--border-thick); box-shadow: 10px 10px 0px var(--black);
            width: 100%; max-width: 800px; max-height: 90vh; overflow-y: auto; padding: 30px; position: relative;
        }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid var(--black); padding-bottom: 15px; margin-bottom: 20px; }
        .modal-title { font-weight: 900; text-transform: uppercase; font-size: 1.3rem; }
        
        .modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .modal-divider { border-right: 3px solid var(--black); padding-right: 20px; }
        
        .qr-img { max-height: 180px; width: auto; border: 3px solid var(--black); object-fit: contain; margin: 10px 0; }

        /* Footer */
        footer { background-color: var(--yellow); border-top: var(--border-thick); padding: 20px; text-align: center; font-weight: 900; text-transform: uppercase; margin-top: auto; }

        /* --- RESPONSIVE MOBILE --- */
        @media (max-width: 768px) {
            .neo-brand { font-size: 1.2rem; }
            .profile-btn { padding: 6px 10px; font-size: 0.85rem; }
            .form-grid { grid-template-columns: 1fr; gap: 10px; }
            .cars-grid { grid-template-columns: 1fr; gap: 15px; }
            .modal-grid { grid-template-columns: 1fr; gap: 15px; }
            .modal-divider { border-right: none; padding-right: 0; border-bottom: 3px solid var(--black); padding-bottom: 15px; }
            .main-content { padding: 1rem 10px; }
            .search-card { padding: 15px; }
            .neo-modal { padding: 15px; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <header class="neo-navbar">
        <div class="neo-nav-left">
            <button class="menu-toggle-btn" id="open-sidebar"><i class="bi bi-list"></i></button>
            <div class="neo-brand">SCRS PMU</div>
        </div>
        <div class="profile-container">
            <button class="profile-btn" id="profile-toggle">
                <i class="bi bi-person-fill fs-5"></i>
                <span><?php echo htmlspecialchars($student_name); ?></span>
            </button>
            <ul class="dropdown-menu" id="profile-menu">
                <li><a href="edit_profile.php" class="dropdown-item"><i class="bi bi-gear-fill me-2"></i> Edit Profil</a></li>
                <li><a href="login.php" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i> Log Keluar</a></li>
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
            <a href="dashboard.php" class="sidebar-link"><i class="bi bi-house-door-fill"></i> Papan Pemuka</a>
            <a href="booking.php" class="sidebar-link active"><i class="bi bi-car-front-fill"></i> Cari & Tempah</a>
            <a href="my_bookings.php" class="sidebar-link"><i class="bi bi-clipboard-check-fill"></i> Status Tempahan</a>
            <a href="booking_history.php" class="sidebar-link"><i class="bi bi-clock-history"></i> Sejarah Rekod</a>
        </nav>
    </aside>

    <!-- KANDUNGAN UTAMA -->
    <main class="main-content">
        
        <?php echo $message; ?>
        <?php if ($search_error) echo "<div class='neo-alert alert-danger'>$search_error</div>"; ?>

        <!-- LANGKAH 1: BORANG CARIAN -->
        <div class="search-card">
            <div class="search-title"><i class="bi bi-calendar-check text-dark"></i> Langkah 1: Pilih Tarikh & Masa Carian</div>
            <form action="booking.php" method="GET">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Jenis Sewaan</label>
                        <select class="form-select" name="rent_type" required>
                            <option value="" disabled <?php echo empty($rent_type) ? 'selected' : ''; ?>>Pilih...</option>
                            <option value="Daily" <?php echo ($rent_type === 'Daily') ? 'selected' : ''; ?>>Harian (Daily)</option>
                            <option value="Hourly" <?php echo ($rent_type === 'Hourly') ? 'selected' : ''; ?>>Jam (Hourly)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tarikh & Masa Ambil</label>
                        <input type="datetime-local" class="form-control" name="start_date" value="<?php echo htmlspecialchars($search_start); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tarikh & Masa Pulang</label>
                        <input type="datetime-local" class="form-control" name="end_date" value="<?php echo htmlspecialchars($search_end); ?>" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="neo-btn bg-y" style="width: 100%; margin-top: 22px;"><i class="bi bi-search me-1"></i> Cari Kereta</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- LANGKAH 2: PAPARAN KERETA TERSEDIA -->
        <?php if ($result_cars !== null): ?>
            
            <h3 class="section-heading"><i class="bi bi-car-front-fill text-dark"></i> Langkah 2: Pilih Kereta & Pembayaran</h3>
            <p class="section-desc">Menampilkan kereta yang tersedia untuk tempoh <strong><?php echo $duration; ?> <?php echo ($rent_type === 'Daily') ? 'Hari' : 'Jam'; ?></strong>.</p>
            
            <div class="cars-grid">
                <?php 
                if ($result_cars->num_rows > 0) {
                    while ($car = $result_cars->fetch_assoc()) { 
                        $price_rate = ($rent_type === 'Daily') ? $car['price_per_day'] : $car['price_per_hour'];
                        $total_calc_price = $duration * $price_rate;
                        $qr_image = !empty($car['qr_code_image']) ? $car['qr_code_image'] : '';
                ?>
                <div class="car-card">
                    <img src="<?php echo htmlspecialchars($car['car_image']); ?>" class="car-img" alt="Kereta">
                    <div class="car-body">
                        <h5 class="car-title"><?php echo htmlspecialchars($car['car_model']); ?></h5>
                        <p class="car-plate"><i class="bi bi-123 me-1"></i> <?php echo htmlspecialchars($car['car_plate']); ?></p>
                        
                        <div class="badges-box">
                            <span class="neo-badge"><i class="bi bi-gear-fill"></i> <?php echo htmlspecialchars($car['transmission']); ?></span>
                            <span class="neo-badge"><i class="bi bi-people-fill"></i> <?php echo htmlspecialchars($car['seat_capacity']); ?> Tempat Duduk</span>
                        </div>
                        
                        <div class="price-box">
                            <div class="price-row">
                                <span>Kadar (<?php echo ($rent_type === 'Daily') ? 'Harian' : 'Jam'; ?>):</span>
                                <span>RM <?php echo number_format($price_rate, 2); ?></span>
                            </div>
                            <div class="price-total">
                                <span>Jumlah:</span>
                                <span style="color: #007700;">RM <?php echo number_format($total_calc_price, 2); ?></span>
                            </div>
                        </div>

                        <!-- Butang Buka Modal -->
                        <button type="button" class="neo-btn btn-green" onclick="openModal('modal<?php echo $car['id']; ?>')">
                            Tempah Kereta Ini
                        </button>
                    </div>
                </div>

                <!-- MODAL POPUP PAYMENT (Vanilla JS Modal) -->
                <div class="neo-modal-overlay" id="modal<?php echo $car['id']; ?>">
                    <div class="neo-modal">
                        <div class="modal-header">
                            <h3 class="modal-title">Pengesahan & Pembayaran</h3>
                            <button class="close-btn" onclick="closeModal('modal<?php echo $car['id']; ?>')"><i class="bi bi-x-lg"></i></button>
                        </div>
                        
                        <form action="booking.php" method="POST" enctype="multipart/form-data">
                            <div class="modal-grid">
                                <!-- Kiri: Rumusan -->
                                <div class="modal-divider">
                                    <h4 style="font-weight: 900; text-transform: uppercase; margin-bottom: 15px; color: #0055ff;"><?php echo htmlspecialchars($car['car_model']); ?></h4>
                                    <ul style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; font-weight: 700;">
                                        <li style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ddd; padding-bottom: 5px;">
                                            <span style="color: #666;">Ambil:</span> 
                                            <span><?php echo date('d M Y, h:i A', strtotime($search_start)); ?></span>
                                        </li>
                                        <li style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ddd; padding-bottom: 5px;">
                                            <span style="color: #666;">Pulang:</span> 
                                            <span><?php echo date('d M Y, h:i A', strtotime($search_end)); ?></span>
                                        </li>
                                        <li style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ddd; padding-bottom: 5px;">
                                            <span style="color: #666;">Tempoh:</span> 
                                            <span><?php echo $duration; ?> <?php echo ($rent_type === 'Daily') ? 'Hari' : 'Jam'; ?></span>
                                        </li>
                                    </ul>
                                    <div style="background: var(--bg-color); border: 3px solid var(--black); padding: 15px;">
                                        <div style="display: flex; justify-content: space-between; font-weight: 900; font-size: 1.2rem;">
                                            <span>Jumlah Bayaran:</span>
                                            <span style="color: #008800;">RM <?php echo number_format($total_calc_price, 2); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Kanan: QR & Muat Naik Resit -->
                                <div style="display: flex; flex-direction: column; align-items: center; text-align: center;">
                                    <p style="font-weight: 900; text-transform: uppercase; margin-bottom: 2px;">Imbas QR DuitNow</p>
                                    <p style="font-size: 0.85rem; font-weight: 700; color: #666; margin-bottom: 10px;">Penyedia: <?php echo htmlspecialchars($car['provider_name']); ?></p>
                                    
                                    <?php if (!empty($qr_image) && file_exists($qr_image)): ?>
                                        <img src="<?php echo htmlspecialchars($qr_image); ?>" alt="QR DuitNow" class="qr-img">
                                    <?php else: ?>
                                        <div style="background: var(--pink); border: 3px solid var(--black); padding: 15px; font-weight: 800; font-size: 0.85rem; margin-bottom: 10px; width: 100%;">
                                            <i class="bi bi-exclamation-octagon fs-4"></i> Tiada QR Kod disediakan. Sila hubungi penyedia.
                                        </div>
                                    <?php endif; ?>

                                    <div class="form-group" style="width: 100%; text-align: left; margin-top: 10px;">
                                        <label class="form-label" style="color: #0055ff;">Muat Naik Resit (Wajib)</label>
                                        <input class="form-control" type="file" name="payment_receipt" accept=".jpg, .jpeg, .png, .pdf" required style="border-style: dashed;">
                                    </div>
                                </div>
                            </div>

                            <!-- Input Tersembunyi -->
                            <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                            <input type="hidden" name="rent_type" value="<?php echo htmlspecialchars($rent_type); ?>">
                            <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($search_start); ?>">
                            <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($search_end); ?>">
                            <input type="hidden" name="total_price" value="<?php echo $total_calc_price; ?>">

                            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 25px; border-top: 3px solid var(--black); padding-top: 20px;">
                                <button type="button" class="neo-btn" style="background: #ccc;" onclick="closeModal('modal<?php echo $car['id']; ?>')">Batal</button>
                                <button type="submit" name="submit_booking" class="neo-btn btn-green"><i class="bi bi-cloud-arrow-up me-1"></i> Hantar Tempahan</button>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- TAMAT MODAL -->

                <?php 
                    } 
                } else {
                    echo '<div class="neo-alert alert-danger" style="grid-column: 1 / -1; text-align: center;">Maaf, tiada kereta yang tersedia untuk tarikh/masa yang dipilih. Sila cuba tarikh lain.</div>';
                }
                ?>
            </div>
        <?php elseif (empty($search_error)): ?>
            <div style="text-align: center; padding: 4rem 1rem; border: 3px dashed var(--black); background: var(--white); box-shadow: var(--shadow-solid);">
                <i class="bi bi-search" style="font-size: 3rem;"></i>
                <h3 style="font-weight: 900; text-transform: uppercase; margin-top: 10px;">Sila Pilih Tarikh & Masa</h3>
                <p style="font-weight: 700; color: #666;">Lengkapkan pilihan di borang atas untuk memaparkan senarai kereta.</p>
            </div>
        <?php endif; ?>

    </main>

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

        // Modal Vanilla JS
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('show');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }
    </script>
</body>
</html>