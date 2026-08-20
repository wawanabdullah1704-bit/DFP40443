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
$message = "";

// 1. PROSES TAMBAH KERETA
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_car'])) {
    
    $car_brand = htmlspecialchars($_POST['car_brand'] ?? '');
    $car_model = htmlspecialchars($_POST['car_model']);
    $car_plate = htmlspecialchars($_POST['car_plate']);
    $transmission = htmlspecialchars($_POST['transmission']);
    $seat_capacity = (int)$_POST['seat_capacity'];
    $price_per_day = (float)$_POST['price_per_day'];
    $price_per_hour = (float)$_POST['price_per_hour'];

    // Pengurusan Muat Naik Gambar
    $targetDir = "uploads/cars/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $imageName = basename($_FILES["car_image"]["name"]);
    $newImageName = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $imageName);
    $targetPath = $targetDir . $newImageName;

    if (move_uploaded_file($_FILES["car_image"]["tmp_name"], $targetPath)) {
        $sql = "INSERT INTO cars (provider_id, car_brand, car_model, car_plate, transmission, seat_capacity, price_per_day, price_per_hour, car_image, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Available')";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssidds", $provider_id, $car_brand, $car_model, $car_plate, $transmission, $seat_capacity, $price_per_day, $price_per_hour, $targetPath);

        if ($stmt->execute()) {
            $message = "<div class='neo-alert alert-success'><i class='bi bi-check-circle-fill me-2'></i>Berjaya: <strong>{$car_brand} {$car_model}</strong> telah ditambah ke dalam senarai kenderaan anda!</div>";
        } else {
            $message = "<div class='neo-alert alert-danger'>Ralat pangkalan data: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='neo-alert alert-danger'>Ralat: Gagal memuat naik gambar kereta.</div>";
    }
}

// 2. PROSES KEMASKINI KERETA (EDIT)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_car'])) {
    
    $car_id = (int)$_POST['car_id'];
    $car_brand = htmlspecialchars($_POST['car_brand'] ?? '');
    $car_model = htmlspecialchars($_POST['car_model']);
    $car_plate = htmlspecialchars($_POST['car_plate']);
    $transmission = htmlspecialchars($_POST['transmission']);
    $seat_capacity = (int)$_POST['seat_capacity'];
    $price_per_day = (float)$_POST['price_per_day'];
    $price_per_hour = (float)$_POST['price_per_hour'];

    if (!empty($_FILES["car_image"]["name"])) {
        $targetDir = "uploads/cars/";
        $imageName = basename($_FILES["car_image"]["name"]);
        $newImageName = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $imageName);
        $targetPath = $targetDir . $newImageName;

        // Ambil gambar lama untuk dipadam
        $sql_old_img = "SELECT car_image FROM cars WHERE id = ? AND provider_id = ?";
        $stmt_old = $conn->prepare($sql_old_img);
        $stmt_old->bind_param("ii", $car_id, $provider_id);
        $stmt_old->execute();
        $res_old = $stmt_old->get_result();
        if ($old_row = $res_old->fetch_assoc()) {
            if (file_exists($old_row['car_image'])) {
                unlink($old_row['car_image']);
            }
        }
        $stmt_old->close();

        if (move_uploaded_file($_FILES["car_image"]["tmp_name"], $targetPath)) {
            $sql = "UPDATE cars SET car_brand=?, car_model=?, car_plate=?, transmission=?, seat_capacity=?, price_per_day=?, price_per_hour=?, car_image=? WHERE id=? AND provider_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssiddssii", $car_brand, $car_model, $car_plate, $transmission, $seat_capacity, $price_per_day, $price_per_hour, $targetPath, $car_id, $provider_id);
        }
    } else {
        $sql = "UPDATE cars SET car_brand=?, car_model=?, car_plate=?, transmission=?, seat_capacity=?, price_per_day=?, price_per_hour=? WHERE id=? AND provider_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssiddii", $car_brand, $car_model, $car_plate, $transmission, $seat_capacity, $price_per_day, $price_per_hour, $car_id, $provider_id);
    }

    if (isset($stmt) && $stmt->execute()) {
        $message = "<div class='neo-alert alert-success'><i class='bi bi-check-circle-fill me-2'></i>Berjaya: Maklumat <strong>{$car_brand} {$car_model}</strong> telah dikemaskini!</div>";
    } else {
        $message = "<div class='neo-alert alert-danger'>Ralat: Gagal mengemaskini maklumat kereta.</div>";
    }
    if (isset($stmt)) $stmt->close();
}

// 3. PROSES PADAM KERETA (DELETE)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_car'])) {
    $car_id = (int)$_POST['car_id'];

    $sql_img = "SELECT car_image, car_model FROM cars WHERE id = ? AND provider_id = ?";
    $stmt_img = $conn->prepare($sql_img);
    $stmt_img->bind_param("ii", $car_id, $provider_id);
    $stmt_img->execute();
    $res_img = $stmt_img->get_result();

    if ($row = $res_img->fetch_assoc()) {
        $image_path = $row['car_image'];
        $car_model_name = $row['car_model'];

        $sql_del = "DELETE FROM cars WHERE id = ? AND provider_id = ?";
        $stmt_del = $conn->prepare($sql_del);
        $stmt_del->bind_param("ii", $car_id, $provider_id);
        
        if ($stmt_del->execute()) {
            if (file_exists($image_path)) {
                unlink($image_path);
            }
            $message = "<div class='neo-alert alert-success'><i class='bi bi-check-circle-fill me-2'></i>Berjaya: Kereta <strong>{$car_model_name}</strong> telah dipadam.</div>";
        } else {
            $message = "<div class='neo-alert alert-danger'>Ralat: Gagal memadam kereta. Sila pastikan tiada tempahan aktif untuk kereta ini.</div>";
        }
        $stmt_del->close();
    }
    $stmt_img->close();
}

// 4. PROSES TUKAR STATUS (AVAILABLE / UNAVAILABLE)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['toggle_status'])) {
    $car_id = (int)$_POST['car_id'];
    $new_status = htmlspecialchars($_POST['new_status']);

    $sql = "UPDATE cars SET status = ? WHERE id = ? AND provider_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $new_status, $car_id, $provider_id);
    
    if ($stmt->execute()) {
        $status_text = ($new_status == 'Available') ? 'Tersedia (Buka Tempahan)' : 'Tidak Tersedia (Tutup Tempahan)';
        $message = "<div class='neo-alert alert-success'><i class='bi bi-check-circle-fill me-2'></i>Berjaya: Status kereta ditukar kepada <strong>{$status_text}</strong>.</div>";
    } else {
        $message = "<div class='neo-alert alert-danger'>Ralat pangkalan data: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// 5. PROSES MUAT NAIK QR CODE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_qr'])) {
    if (!empty($_FILES["qr_image"]["name"])) {
        $targetDir = "uploads/qr_codes/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $qrName = basename($_FILES["qr_image"]["name"]);
        $newQrName = "QR_" . $provider_id . "_" . time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $qrName);
        $targetPath = $targetDir . $newQrName;

        $sql_old_qr = "SELECT qr_code_image FROM providers WHERE id = ?";
        $stmt_old_qr = $conn->prepare($sql_old_qr);
        $stmt_old_qr->bind_param("i", $provider_id);
        $stmt_old_qr->execute();
        $res_old_qr = $stmt_old_qr->get_result();
        if ($old_qr_row = $res_old_qr->fetch_assoc()) {
            if (!empty($old_qr_row['qr_code_image']) && file_exists($old_qr_row['qr_code_image'])) {
                unlink($old_qr_row['qr_code_image']);
            }
        }
        $stmt_old_qr->close();

        if (move_uploaded_file($_FILES["qr_image"]["tmp_name"], $targetPath)) {
            $sql = "UPDATE providers SET qr_code_image = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $targetPath, $provider_id);
            if ($stmt->execute()) {
                $message = "<div class='neo-alert alert-success'><i class='bi bi-check-circle-fill me-2'></i>Berjaya: Kod QR Pembayaran anda telah dikemaskini!</div>";
            }
            $stmt->close();
        } else {
            $message = "<div class='neo-alert alert-danger'>Ralat: Gagal memuat naik Kod QR.</div>";
        }
    }
}

// AMBIL SENARAI KERETA MILIK PROVIDER
$sql_cars = "SELECT * FROM cars WHERE provider_id = ? ORDER BY created_at DESC";
$stmt_cars = $conn->prepare($sql_cars);
$stmt_cars->bind_param("i", $provider_id);
$stmt_cars->execute();
$result_cars = $stmt_cars->get_result();

// AMBIL STATUS QR PROVIDER
$sql_prov = "SELECT qr_code_image FROM providers WHERE id = ?";
$stmt_prov = $conn->prepare($sql_prov);
$stmt_prov->bind_param("i", $provider_id);
$stmt_prov->execute();
$provider_data = $stmt_prov->get_result()->fetch_assoc();
$has_qr = (!empty($provider_data['qr_code_image']) && file_exists($provider_data['qr_code_image']));
$stmt_prov->close();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Senarai Kereta - SCRS PMU</title>
    
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

        .page-header-actions {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 25px; flex-wrap: wrap; gap: 15px;
        }

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
        .alert-warning { background-color: var(--yellow); }

        /* CARDS GRID */
        .cars-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 40px; }
        
        .car-card {
            background: var(--white); border: var(--border-thick); box-shadow: var(--shadow-solid);
            display: flex; flex-direction: column; position: relative; overflow: hidden;
        }
        .car-img { height: 200px; width: 100%; object-fit: cover; border-bottom: var(--border-thick); }
        .car-body { padding: 20px; display: flex; flex-direction: column; flex: 1; }
        
        .car-title { font-weight: 900; font-size: 1.2rem; text-transform: uppercase; margin-bottom: 4px; }
        .car-plate { font-weight: 700; color: #555; margin-bottom: 12px; }

        .neo-badge {
            border: 2px solid var(--black); padding: 3px 8px; font-weight: 800;
            font-size: 0.75rem; background: var(--bg-color); text-transform: uppercase;
            border-radius: 4px; display: inline-block; box-shadow: none;
        }
        .badge-available { background-color: var(--green); }
        .badge-unavailable { background-color: var(--pink); }

        .price-box {
            background: var(--bg-color); border: 3px solid var(--black); padding: 12px; margin-top: auto; margin-bottom: 15px;
        }
        .price-row { display: flex; justify-content: space-between; font-weight: 700; font-size: 0.9rem; }

        /* MODAL */
        .neo-modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6); z-index: 2000; display: none; align-items: center; justify-content: center; padding: 15px;
        }
        .neo-modal-overlay.show { display: flex; }
        
        .neo-modal {
            background: var(--white); border: var(--border-thick); box-shadow: 10px 10px 0px var(--black);
            width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; padding: 25px; position: relative;
        }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid var(--black); padding-bottom: 12px; margin-bottom: 20px; }
        .modal-title { font-weight: 900; text-transform: uppercase; font-size: 1.2rem; }

        .form-group { display: flex; flex-direction: column; gap: 5px; margin-bottom: 15px; }
        .form-label { font-weight: 800; text-transform: uppercase; font-size: 0.85rem; }
        .form-control, .form-select {
            border: 3px solid var(--black); padding: 10px; font-weight: 700; background-color: var(--bg-color); outline: none; width: 100%;
        }
        .form-control:focus, .form-select:focus { background-color: var(--white); box-shadow: 3px 3px 0px var(--black); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }

        .empty-box {
            background: var(--white); border: var(--border-thick); box-shadow: var(--shadow-solid);
            padding: 50px 20px; text-align: center;
        }
        .empty-box i { font-size: 4rem; display: block; margin-bottom: 15px; }

        /* FOOTER */
        footer {
            background-color: var(--yellow); border-top: var(--border-thick); padding: 20px;
            text-align: center; font-weight: 900; text-transform: uppercase; margin-top: auto;
        }

        /* RESPONSIVE MOBILE */
        @media (max-width: 768px) {
            .cars-grid { grid-template-columns: 1fr; }
            .form-row { grid-template-columns: 1fr; }
            .main-content { padding: 1rem 10px; }
            .neo-brand { font-size: 1.2rem; }
            .profile-btn { padding: 6px 10px; font-size: 0.85rem; }
            .page-header-actions { flex-direction: column; align-items: stretch; }
            .page-header-actions .neo-btn { width: 100%; justify-content: center; }
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
            <a href="provider_cars.php" class="sidebar-link active"><i class="bi bi-car-front-fill"></i> Senarai Kereta</a>
            <a href="provider_bookings.php" class="sidebar-link"><i class="bi bi-clipboard-check-fill"></i> Tempahan Semasa</a>
            <a href="provider_history.php" class="sidebar-link"><i class="bi bi-clock-history"></i> Sejarah Rekod</a>
        </nav>
    </aside>

    <!-- KANDUNGAN UTAMA -->
    <main class="main-content">
        
        <?php echo $message; ?>

        <!-- HEADING PANDUAN PENGGUNA (TANPA KOTAK) -->
        <div style="margin-bottom: 25px;">
            <div class="page-header-actions" style="margin-bottom: 8px;">
                <h1 style="font-size: 1.6rem; font-weight: 900; text-transform: uppercase; margin: 0; color: var(--black); display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-car-front-fill text-dark"></i> Senarai Kereta Sewaan
                </h1>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <button type="button" class="neo-btn btn-blue" onclick="openModal('qrCodeModal')">
                        <i class="bi bi-qr-code me-1"></i> QR Bayaran
                    </button>
                    <button type="button" class="neo-btn btn-green" onclick="openModal('addCarModal')">
                        <i class="bi bi-plus-circle-fill me-1"></i> Tambah Kereta
                    </button>
                </div>
            </div>
            <p style="font-weight: 700; color: #555; font-size: 0.95rem; margin: 0; line-height: 1.5;">
                Urus kenderaan sewaan anda di sini. Anda boleh menambah kenderaan baharu, mengemaskini harga, memadam kenderaan, atau menukar status ketersediaan (Buka/Tutup tempahan).
            </p>
        </div>

        <!-- GRID KERETA -->
        <div class="cars-grid">
            <?php if ($result_cars->num_rows > 0): ?>
                <?php while ($car = $result_cars->fetch_assoc()): 
                    $is_available = ($car['status'] == 'Available');
                    $badge_class = $is_available ? 'badge-available' : 'badge-unavailable';
                    $status_text = $is_available ? 'Tersedia' : 'Tidak Tersedia';
                ?>
                    <div class="car-card">
                        <img src="<?php echo htmlspecialchars($car['car_image']); ?>" class="car-img" alt="Kereta">
                        <div class="car-body">
                            
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
                                <h3 class="car-title">
                                    <?php echo !empty($car['car_brand']) ? htmlspecialchars($car['car_brand']) . ' ' : ''; ?>
                                    <?php echo htmlspecialchars($car['car_model']); ?>
                                </h3>
                                <span class="neo-badge <?php echo $badge_class; ?>"><?php echo $status_text; ?></span>
                            </div>
                            
                            <div class="car-plate"><i class="bi bi-123 me-1"></i><?php echo htmlspecialchars($car['car_plate']); ?></div>
                            
                            <div style="display: flex; gap: 8px; margin-bottom: 15px; flex-wrap: wrap;">
                                <span class="neo-badge"><i class="bi bi-gear-fill me-1"></i><?php echo htmlspecialchars($car['transmission']); ?></span>
                                <span class="neo-badge"><i class="bi bi-people-fill me-1"></i><?php echo htmlspecialchars($car['seat_capacity']); ?> Tempat Duduk</span>
                            </div>

                            <div class="price-box">
                                <div class="price-row">
                                    <span>Kadar Sehari:</span>
                                    <strong>RM <?php echo number_format($car['price_per_day'], 2); ?></strong>
                                </div>
                                <div class="price-row" style="margin-top: 5px;">
                                    <span>Kadar Sejam:</span>
                                    <strong>RM <?php echo number_format($car['price_per_hour'], 2); ?></strong>
                                </div>
                            </div>

                            <!-- BUTANG TINDAKAN -->
                            <div style="display: flex; flex-direction: column; gap: 8px; margin-top: auto;">
                                
                                <!-- FORM TUKAR STATUS -->
                                <form action="" method="POST" style="width: 100%;">
                                    <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                    <input type="hidden" name="new_status" value="<?php echo $is_available ? 'Unavailable' : 'Available'; ?>">
                                    <button type="submit" name="toggle_status" class="neo-btn" style="width: 100%; font-size: 0.85rem; padding: 8px; background: <?php echo $is_available ? 'var(--pink)' : 'var(--green)'; ?>;">
                                        <i class="bi <?php echo $is_available ? 'bi-slash-circle' : 'bi-check-circle'; ?> me-1"></i>
                                        <?php echo $is_available ? 'Tutup Tempahan' : 'Buka Tempahan'; ?>
                                    </button>
                                </form>

                                <div style="display: flex; gap: 8px;">
                                    <button type="button" class="neo-btn btn-blue" style="flex: 1; font-size: 0.85rem; padding: 8px;" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($car)); ?>)">
                                        <i class="bi bi-pencil-square"></i> Kemaskini
                                    </button>

                                    <form action="" method="POST" style="flex: 1;" onsubmit="return confirm('Adakah anda pasti ingin memadam kereta ini?');">
                                        <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                        <button type="submit" name="delete_car" class="neo-btn btn-pink" style="width: 100%; font-size: 0.85rem; padding: 8px;">
                                            <i class="bi bi-trash"></i> Padam
                                        </button>
                                    </form>
                                </div>

                            </div>

                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-box" style="grid-column: 1 / -1;">
                    <i class="bi bi-car-front"></i>
                    <h2 style="font-weight: 900; text-transform: uppercase;">Tiada Kereta Didaftarkan</h2>
                    <p style="font-weight: 700; color: #666; margin: 10px 0 20px 0;">Anda belum mendaftarkan sebarang kenderaan sewaan.</p>
                    <button type="button" class="neo-btn btn-green" onclick="openModal('addCarModal')">
                        <i class="bi bi-plus-circle-fill me-1"></i> Tambah Kereta Pertama Anda
                    </button>
                </div>
            <?php endif; ?>
        </div>

    </main>

    <!-- MODAL TAMBAH KERETA -->
    <div class="neo-modal-overlay" id="addCarModal">
        <div class="neo-modal">
            <div class="modal-header">
                <h3 class="modal-title"><i class="bi bi-plus-circle-fill me-1"></i> Tambah Kereta Baharu</h3>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Jenama (Brand)</label>
                        <input type="text" class="form-control" name="car_brand" placeholder="Cth: Perodua, Proton" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Model Kereta</label>
                        <input type="text" class="form-control" name="car_model" placeholder="Cth: Myvi, Saga" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nombor Plat Kereta</label>
                        <input type="text" class="form-control" name="car_plate" placeholder="Cth: QAA 1234 A" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Transmisi</label>
                        <select class="form-select" name="transmission" required>
                            <option value="Auto">Auto</option>
                            <option value="Manual">Manual</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Kapasiti Tempat Duduk</label>
                        <input type="number" class="form-control" name="seat_capacity" value="5" min="2" max="15" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gambar Kereta</label>
                        <input type="file" class="form-control" name="car_image" accept=".jpg,.jpeg,.png" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Harga Sehari (RM)</label>
                        <input type="number" step="0.01" class="form-control" name="price_per_day" placeholder="Cth: 100.00" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Harga Sejam (RM)</label>
                        <input type="number" step="0.01" class="form-control" name="price_per_hour" placeholder="Cth: 10.00" required>
                    </div>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; border-top: 3px solid var(--black); padding-top: 15px;">
                    <button type="button" class="neo-btn" style="background: #ccc;" onclick="closeModal('addCarModal')">Batal</button>
                    <button type="submit" name="add_car" class="neo-btn btn-green"><i class="bi bi-check-circle-fill me-1"></i> Simpan Kereta</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT KERETA -->
    <div class="neo-modal-overlay" id="editCarModal">
        <div class="neo-modal">
            <div class="modal-header">
                <h3 class="modal-title"><i class="bi bi-pencil-square me-1"></i> Kemaskini Maklumat Kereta</h3>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="car_id" id="edit_car_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Jenama (Brand)</label>
                        <input type="text" class="form-control" name="car_brand" id="edit_car_brand" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Model Kereta</label>
                        <input type="text" class="form-control" name="car_model" id="edit_car_model" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nombor Plat Kereta</label>
                        <input type="text" class="form-control" name="car_plate" id="edit_car_plate" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Transmisi</label>
                        <select class="form-select" name="transmission" id="edit_transmission" required>
                            <option value="Auto">Auto</option>
                            <option value="Manual">Manual</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Kapasiti Tempat Duduk</label>
                        <input type="number" class="form-control" name="seat_capacity" id="edit_seat_capacity" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tukar Gambar Kereta (Pilihan)</label>
                        <input type="file" class="form-control" name="car_image" accept=".jpg,.jpeg,.png">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Harga Sehari (RM)</label>
                        <input type="number" step="0.01" class="form-control" name="price_per_day" id="edit_price_per_day" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Harga Sejam (RM)</label>
                        <input type="number" step="0.01" class="form-control" name="price_per_hour" id="edit_price_per_hour" required>
                    </div>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; border-top: 3px solid var(--black); padding-top: 15px;">
                    <button type="button" class="neo-btn" style="background: #ccc;" onclick="closeModal('editCarModal')">Batal</button>
                    <button type="submit" name="edit_car" class="neo-btn btn-blue"><i class="bi bi-save-fill me-1"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL QR CODE PEMBAYARAN -->
    <div class="neo-modal-overlay" id="qrCodeModal">
        <div class="neo-modal">
            <div class="modal-header">
                <h3 class="modal-title"><i class="bi bi-qr-code me-1"></i> Kod QR DuitNow</h3>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div style="text-align: center; margin-bottom: 20px;">
                    <?php if ($has_qr): ?>
                        <img src="<?php echo htmlspecialchars($provider_data['qr_code_image']); ?>" alt="QR Code" style="max-height: 180px; max-width: 100%; border: 3px solid var(--black); box-shadow: 4px 4px 0px var(--black); padding: 5px; background: #fff;">
                        <p style="font-weight: 800; font-size: 0.85rem; margin-top: 10px; color: #2e7d32;"><i class="bi bi-check-circle-fill me-1"></i> Kod QR Aktif</p>
                    <?php else: ?>
                        <div style="border: 2px dashed var(--black); padding: 30px 10px; background: var(--bg-color);">
                            <i class="bi bi-qr-code-scan" style="font-size: 3rem; color: #666;"></i>
                            <p style="font-weight: 800; font-size: 0.85rem; margin-top: 10px; color: #666;">Belum ada Kod QR dimuat naik</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label">Pilih Gambar Kod QR Baharu</label>
                    <input type="file" class="form-control" name="qr_image" accept=".jpg,.jpeg,.png" required>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; border-top: 3px solid var(--black); padding-top: 15px;">
                    <button type="button" class="neo-btn" style="background: #ccc;" onclick="closeModal('qrCodeModal')">Tutup</button>
                    <button type="submit" name="upload_qr" class="neo-btn btn-green"><i class="bi bi-cloud-arrow-up-fill me-1"></i> Simpan Kod QR</button>
                </div>
            </form>
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

        // Modals
        window.openModal = function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.add('show');
        };

        window.closeModal = function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.remove('show');
        };

        function openEditModal(car) {
            document.getElementById('edit_car_id').value = car.id;
            document.getElementById('edit_car_brand').value = car.car_brand || '';
            document.getElementById('edit_car_model').value = car.car_model;
            document.getElementById('edit_car_plate').value = car.car_plate;
            document.getElementById('edit_transmission').value = car.transmission;
            document.getElementById('edit_seat_capacity').value = car.seat_capacity;
            document.getElementById('edit_price_per_day').value = car.price_per_day;
            document.getElementById('edit_price_per_hour').value = car.price_per_hour;
            openModal('editCarModal');
        }
    </script>
</body>
</html>
