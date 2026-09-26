<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/notifications.php';

// Semak jika pengguna telah log masuk dan merupakan Penyedia Kereta (Provider)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'provider') {
    header("Location: ../index.php");
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
    $roadtax_expiry = !empty($_POST['roadtax_expiry']) ? $_POST['roadtax_expiry'] : null;
    $insurance_expiry = !empty($_POST['insurance_expiry']) ? $_POST['insurance_expiry'] : null;

    // Pengurusan Direktori Muat Naik
    $targetDir = "../uploads/cars/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    $docDir = "../uploads/cars/documents/";
    if (!is_dir($docDir)) {
        mkdir($docDir, 0777, true);
    }

    $time = time();
    $imageName = basename($_FILES["car_image"]["name"]);
    $newImageName = $time . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $imageName);
    $targetPath = $targetDir . $newImageName;
    $db_car_image = "uploads/cars/" . $newImageName;

    // Muat naik dokumen kenderaan
    $db_grant = null;
    $db_roadtax = null;
    $db_insurance = null;

    if (!empty($_FILES["grant_file"]["name"])) {
        $grantName = basename($_FILES["grant_file"]["name"]);
        $newGrantName = $time . "_VOC_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $grantName);
        $targetGrant = $docDir . $newGrantName;
        if (move_uploaded_file($_FILES["grant_file"]["tmp_name"], $targetGrant)) {
            $db_grant = "uploads/cars/documents/" . $newGrantName;
        }
    }

    if (!empty($_FILES["roadtax_file"]["name"])) {
        $roadtaxName = basename($_FILES["roadtax_file"]["name"]);
        $newRoadtaxName = $time . "_RT_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $roadtaxName);
        $targetRoadtax = $docDir . $newRoadtaxName;
        if (move_uploaded_file($_FILES["roadtax_file"]["tmp_name"], $targetRoadtax)) {
            $db_roadtax = "uploads/cars/documents/" . $newRoadtaxName;
        }
    }

    if (!empty($_FILES["insurance_file"]["name"])) {
        $insuranceName = basename($_FILES["insurance_file"]["name"]);
        $newInsuranceName = $time . "_INS_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $insuranceName);
        $targetInsurance = $docDir . $newInsuranceName;
        if (move_uploaded_file($_FILES["insurance_file"]["tmp_name"], $targetInsurance)) {
            $db_insurance = "uploads/cars/documents/" . $newInsuranceName;
        }
    }

    if (move_uploaded_file($_FILES["car_image"]["tmp_name"], $targetPath)) {
        $sql = "INSERT INTO cars (provider_id, car_brand, car_model, car_plate, transmission, seat_capacity, price_per_day, price_per_hour, car_image, grant_file, roadtax_file, insurance_file, roadtax_expiry, insurance_expiry, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Available')";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssiddssssss", $provider_id, $car_brand, $car_model, $car_plate, $transmission, $seat_capacity, $price_per_day, $price_per_hour, $db_car_image, $db_grant, $db_roadtax, $db_insurance, $roadtax_expiry, $insurance_expiry);

        if ($stmt->execute()) {
            $message = "<div class='neo-alert alert-success'><i class='bi bi-check-circle-fill me-2'></i>Berjaya: <strong>{$car_brand} {$car_model}</strong> beserta dokumen sah laku telah ditambah ke dalam senarai kenderaan anda!</div>";
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
    $roadtax_expiry = !empty($_POST['roadtax_expiry']) ? $_POST['roadtax_expiry'] : null;
    $insurance_expiry = !empty($_POST['insurance_expiry']) ? $_POST['insurance_expiry'] : null;

    $docDir = "uploads/cars/documents/";
    if (!is_dir($docDir)) {
        mkdir($docDir, 0777, true);
    }
    $time = time();

    // Dapatkan data sedia ada
    $sql_cur = "SELECT car_image, grant_file, roadtax_file, insurance_file FROM cars WHERE id = ? AND provider_id = ?";
    $stmt_cur = $conn->prepare($sql_cur);
    $stmt_cur->bind_param("ii", $car_id, $provider_id);
    $stmt_cur->execute();
    $cur_car = $stmt_cur->get_result()->fetch_assoc();
    $stmt_cur->close();

    $newImagePath = $cur_car['car_image'];
    $newGrantPath = $cur_car['grant_file'];
    $newRoadtaxPath = $cur_car['roadtax_file'];
    $newInsurancePath = $cur_car['insurance_file'];

    if (!empty($_FILES["car_image"]["name"])) {
        $targetDir = "uploads/cars/";
        $imageName = basename($_FILES["car_image"]["name"]);
        $newImageName = $time . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $imageName);
        $newImagePath = $targetDir . $newImageName;
        if (move_uploaded_file($_FILES["car_image"]["tmp_name"], $newImagePath)) {
            if (!empty($cur_car['car_image']) && file_exists($cur_car['car_image'])) {
                unlink($cur_car['car_image']);
            }
        }
    }

    if (!empty($_FILES["grant_file"]["name"])) {
        $grantName = basename($_FILES["grant_file"]["name"]);
        $newGrantName = $time . "_VOC_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $grantName);
        $newGrantPath = $docDir . $newGrantName;
        if (move_uploaded_file($_FILES["grant_file"]["tmp_name"], $newGrantPath)) {
            if (!empty($cur_car['grant_file']) && file_exists($cur_car['grant_file'])) {
                unlink($cur_car['grant_file']);
            }
        }
    }

    if (!empty($_FILES["roadtax_file"]["name"])) {
        $roadtaxName = basename($_FILES["roadtax_file"]["name"]);
        $newRoadtaxName = $time . "_RT_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $roadtaxName);
        $newRoadtaxPath = $docDir . $newRoadtaxName;
        if (move_uploaded_file($_FILES["roadtax_file"]["tmp_name"], $newRoadtaxPath)) {
            if (!empty($cur_car['roadtax_file']) && file_exists($cur_car['roadtax_file'])) {
                unlink($cur_car['roadtax_file']);
            }
        }
    }

    if (!empty($_FILES["insurance_file"]["name"])) {
        $insuranceName = basename($_FILES["insurance_file"]["name"]);
        $newInsuranceName = $time . "_INS_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $insuranceName);
        $newInsurancePath = $docDir . $newInsuranceName;
        if (move_uploaded_file($_FILES["insurance_file"]["tmp_name"], $newInsurancePath)) {
            if (!empty($cur_car['insurance_file']) && file_exists($cur_car['insurance_file'])) {
                unlink($cur_car['insurance_file']);
            }
        }
    }

    $sql = "UPDATE cars SET car_brand=?, car_model=?, car_plate=?, transmission=?, seat_capacity=?, price_per_day=?, price_per_hour=?, car_image=?, grant_file=?, roadtax_file=?, insurance_file=?, roadtax_expiry=?, insurance_expiry=? WHERE id=? AND provider_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssiddssssssii", $car_brand, $car_model, $car_plate, $transmission, $seat_capacity, $price_per_day, $price_per_hour, $newImagePath, $newGrantPath, $newRoadtaxPath, $newInsurancePath, $roadtax_expiry, $insurance_expiry, $car_id, $provider_id);

    if ($stmt->execute()) {
        $message = "<div class='neo-alert alert-success'><i class='bi bi-check-circle-fill me-2'></i>Berjaya: Maklumat dan dokumen <strong>{$car_brand} {$car_model}</strong> telah dikemaskini!</div>";
    } else {
        $message = "<div class='neo-alert alert-danger'>Ralat: Gagal mengemaskini maklumat kereta (" . $stmt->error . ").</div>";
    }
    $stmt->close();
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
    
    <!-- Ikon Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Master Neo-Brutalism CSS -->
    <link rel="stylesheet" href="../assets/css/neo-style.css">

    <style>
        .page-header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
            flex-wrap: wrap;
            gap: 12px;
        }

        /* Grid Kereta Responsif */
        .cars-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }
        
        /* Kad Kereta Premium Neo-Brutalist (Sama Seperti di Page Student) */
        .car-card {
            background: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-solid);
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .car-card:hover {
            transform: translate(-3px, -3px);
            box-shadow: 6px 6px 0px var(--black);
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
            z-index: 2;
            pointer-events: none;
            user-select: none;
        }

        .car-body {
            padding: 16px 18px;
            display: flex;
            flex-direction: column;
            gap: 11px;
            flex: 1;
        }
        
        .car-title-wrap {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
            min-height: 44px;
        }
        .car-name {
            font-weight: 900;
            font-size: 1.1rem;
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

        /* Spesifikasi Kenderaan */
        .car-specs-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 2px 0;
            flex-wrap: wrap;
        }
        .car-spec-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.8rem;
            font-weight: 800;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .car-spec-item i {
            color: #1e293b;
            font-size: 0.85rem;
        }
        .car-spec-dot {
            color: #94a3b8;
            font-weight: 900;
            font-size: 0.8rem;
        }

        /* Panel Harga */
        .car-price-box {
            background: #f8fafc;
            border: 1.5px solid #cbd5e1;
            border-radius: var(--radius-md);
            padding: 9px 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .price-col {
            display: flex;
            flex-direction: column;
        }
        .price-col-lbl {
            font-size: 0.72rem;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
        }
        .price-col-val {
            font-size: 0.98rem;
            font-weight: 900;
            color: #007700;
        }

        /* Panel Dokumen Kenderaan */
        .car-docs-box {
            background: #f8fafc;
            border: 1.5px solid #cbd5e1;
            border-radius: var(--radius-md);
            padding: 9px 12px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .car-docs-dates {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.76rem;
            color: #475569;
            font-weight: 700;
            flex-wrap: wrap;
            gap: 4px;
        }
        .car-docs-dates strong {
            color: var(--black);
            font-weight: 800;
        }
        .car-docs-links {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            padding-top: 6px;
            border-top: 1px dashed #cbd5e1;
        }
        .doc-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.7rem;
            font-weight: 800;
            padding: 2px 7px;
            border: 1.5px solid var(--black);
            border-radius: 4px;
            text-decoration: none;
            color: var(--black);
            box-shadow: 1.5px 1.5px 0px var(--black);
            transition: transform 0.1s ease;
        }
        .doc-pill:hover {
            transform: translate(-1px, -1px);
        }
        .doc-pill.pill-yellow { background: var(--yellow); }
        .doc-pill.pill-green { background: var(--green); }
        .doc-pill.pill-blue { background: #38bdf8; color: var(--black); }

        /* Butang Tindakan */
        .car-actions-wrap {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: auto;
            padding-top: 4px;
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

        .car-card {
            cursor: pointer;
        }

        .header-action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 14px;
        }

        @media (max-width: 768px) {
            .cars-grid { grid-template-columns: 1fr; gap: 18px; }
            .car-thumb-wrap { height: 185px; }
            .form-row { grid-template-columns: 1fr !important; }
            .header-action-buttons {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }
            .header-action-buttons .neo-btn {
                width: 100%;
                justify-content: center;
                padding: 10px 8px;
                font-size: 0.85rem;
            }
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
            <a href="provider_cars.php" class="sidebar-link active"><i class="bi bi-car-front-fill"></i> Urus Kenderaan</a>
            <a href="provider_bookings.php" class="sidebar-link"><i class="bi bi-clipboard-check-fill"></i> Senarai Permohonan</a>
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
                    Senarai Kereta Sewaan
                </h1>
            </div>
            <p style="font-weight: 700; color: #555; font-size: 0.92rem; margin: 0 0 14px 0; line-height: 1.5;">
                Urus kenderaan sewaan anda di sini. Anda boleh menambah kenderaan baharu, mengemaskini harga, memadam kenderaan, atau menukar status ketersediaan (Buka/Tutup tempahan).
            </p>
            <div class="header-action-buttons">
                <button type="button" class="neo-btn btn-green" onclick="openModal('addCarModal')">
                    <i class="bi bi-plus-circle-fill me-1"></i> Tambah Kereta
                </button>
                <button type="button" class="neo-btn btn-blue" onclick="openModal('qrCodeModal')">
                    <i class="bi bi-qr-code me-1"></i> QR Bayaran
                </button>
            </div>
        </div>

        <!-- GRID KERETA -->
        <div class="cars-grid">
            <?php if ($result_cars->num_rows > 0): ?>
                <?php while ($car = $result_cars->fetch_assoc()): 
                    $is_available = ($car['status'] == 'Available');
                    $badge_class = $is_available ? 'status-available' : 'status-unavailable';
                    $status_text = $is_available ? 'Tersedia' : 'Ditutup';
                    $status_icon = $is_available ? 'bi-check-circle-fill' : 'bi-slash-circle';
                    $car_display_name = (!empty($car['car_brand']) ? htmlspecialchars($car['car_brand']) . ' ' : '') . htmlspecialchars($car['car_model']);
                    $car_img_src = (strpos($car['car_image'], 'http') === 0 || strpos($car['car_image'], '../') === 0) ? htmlspecialchars($car['car_image']) : '../' . htmlspecialchars($car['car_image']);
                ?>
                    <!-- KAD RINGKAS KERETA (KEMAS TANPA DETAIL BERSELERAK) -->
                    <div class="car-card" onclick="openModal('carDetailModal<?php echo $car['id']; ?>')">
                        <!-- THUMBNAIL KERETA (PERSIS SEPERTI DI PAGE STUDENT) -->
                        <div class="car-thumb-wrap">
                            <img src="<?php echo $car_img_src; ?>" class="car-img" alt="<?php echo $car_display_name; ?>" loading="lazy">
                            <span class="car-rate-pill">
                                RM <?php echo number_format($car['price_per_day'], 2); ?> / Hari
                            </span>
                        </div>

                        <div class="car-body">
                            <!-- TAJUK & NOMBOR PLAT KERETA -->
                            <div class="car-title-wrap">
                                <h3 class="car-name"><?php echo $car_display_name; ?></h3>
                                <span class="car-plate-pill"><?php echo htmlspecialchars($car['car_plate']); ?></span>
                            </div>
                            
                            <!-- SPESIFIKASI ASAS & STATUS KERETA (SEPERTI DI PAGE STUDENT) -->
                            <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                                <div class="car-specs-row">
                                    <span class="car-spec-item"><i class="bi bi-gear-fill"></i> <?php echo htmlspecialchars($car['transmission']); ?></span>
                                    <span class="car-spec-dot">•</span>
                                    <span class="car-spec-item"><i class="bi bi-people-fill"></i> <?php echo htmlspecialchars($car['seat_capacity']); ?> Tempat</span>
                                </div>
                                <span class="badge-status <?php echo $badge_class; ?>" style="font-size: 0.8rem;">
                                    <i class="bi <?php echo $status_icon; ?>"></i> <?php echo $status_text; ?>
                                </span>
                            </div>

                            <!-- BUTANG LIHAT BUTIRAN KERETA -->
                            <button type="button" class="neo-btn btn-yellow" style="width: 100%; justify-content: center; margin-top: auto; font-size: 0.86rem; padding: 9px 12px; font-weight: 800;" onclick="event.stopPropagation(); openModal('carDetailModal<?php echo $car['id']; ?>')">
                                <i class="bi bi-eye-fill me-1"></i> Lihat Butiran Kereta
                            </button>
                        </div>
                    </div>

                    <!-- MODAL POPUP BUTIRAN & PENGURUSAN KERETA -->
                    <div class="neo-modal-overlay" id="carDetailModal<?php echo $car['id']; ?>" onclick="if(event.target === this) closeModal('carDetailModal<?php echo $car['id']; ?>')">
                        <div class="neo-modal" onclick="event.stopPropagation()" style="max-width: 520px;">
                            <div class="modal-header">
                                <h3 class="modal-title" style="font-weight: 900; font-size: 1.15rem; margin: 0;">
                                    <i class="bi bi-file-earmark-text-fill me-1 text-primary"></i> Butiran Kenderaan
                                </h3>
                                <button type="button" class="close-btn" onclick="closeModal('carDetailModal<?php echo $car['id']; ?>')">&times;</button>
                            </div>

                            <!-- BANNER PREVIEW KERETA -->
                            <div style="display: flex; gap: 14px; background: #f8fafc; border: 2px solid var(--black); border-radius: var(--radius-md); padding: 12px; margin-bottom: 15px; align-items: center;">
                                <img src="<?php echo $car_img_src; ?>" style="width: 105px; height: 72px; object-fit: cover; border-radius: var(--radius-sm); border: 2px solid var(--black); flex-shrink: 0;" alt="<?php echo $car_display_name; ?>">
                                <div style="flex: 1; min-width: 0;">
                                    <h4 style="font-weight: 900; text-transform: uppercase; margin: 0 0 6px 0; font-size: 0.98rem; color: var(--black); line-height: 1.25;">
                                        <?php echo $car_display_name; ?>
                                    </h4>
                                    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                        <span class="car-plate-pill"><?php echo htmlspecialchars($car['car_plate']); ?></span>
                                        <span style="font-size: 0.8rem; font-weight: 800; color: <?php echo $is_available ? '#15803d' : '#b91c1c'; ?>; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="bi <?php echo $status_icon; ?>"></i> <?php echo $status_text; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- GRID KADAR HARGA & SPESIFIKASI -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 14px;">
                                <div style="background: #f1f5f9; border: 1.5px solid #cbd5e1; border-radius: var(--radius-sm); padding: 8px 10px;">
                                    <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Kadar Sehari</div>
                                    <div style="font-size: 1.05rem; font-weight: 900; color: #15803d;">RM <?php echo number_format($car['price_per_day'], 2); ?></div>
                                </div>
                                <div style="background: #f1f5f9; border: 1.5px solid #cbd5e1; border-radius: var(--radius-sm); padding: 8px 10px;">
                                    <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Kadar Sejam</div>
                                    <div style="font-size: 1.05rem; font-weight: 900; color: #0284c7;">RM <?php echo number_format($car['price_per_hour'], 2); ?></div>
                                </div>
                                <div style="background: #f1f5f9; border: 1.5px solid #cbd5e1; border-radius: var(--radius-sm); padding: 8px 10px;">
                                    <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Transmisi</div>
                                    <div style="font-size: 0.92rem; font-weight: 900; color: var(--black);"><i class="bi bi-gear-fill me-1"></i><?php echo htmlspecialchars($car['transmission']); ?></div>
                                </div>
                                <div style="background: #f1f5f9; border: 1.5px solid #cbd5e1; border-radius: var(--radius-sm); padding: 8px 10px;">
                                    <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Tempat Duduk</div>
                                    <div style="font-size: 0.92rem; font-weight: 900; color: var(--black);"><i class="bi bi-people-fill me-1"></i><?php echo htmlspecialchars($car['seat_capacity']); ?> Orang</div>
                                </div>
                            </div>

                            <!-- DOKUMEN KENDERAAN & STATUS SAH LAKU -->
                            <div style="border: 2px solid var(--black); border-radius: var(--radius-md); padding: 12px 14px; background: #fff; margin-bottom: 14px;">
                                <div style="font-size: 0.82rem; font-weight: 900; text-transform: uppercase; color: var(--black); margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                                    <i class="bi bi-file-earmark-lock2-fill text-primary"></i> Dokumen & Status Sah Laku
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 7px; font-size: 0.82rem; font-weight: 700; margin-bottom: 12px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed #e2e8f0; padding-bottom: 5px;">
                                        <span style="color: #475569;"><i class="bi bi-calendar-event me-1 text-primary"></i> Cukai Jalan Sah:</span>
                                        <strong><?php echo !empty($car['roadtax_expiry']) ? date('d/m/Y', strtotime($car['roadtax_expiry'])) : 'Belum Diisi'; ?></strong>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="color: #475569;"><i class="bi bi-shield-check me-1 text-success"></i> Insurans Sah:</span>
                                        <strong><?php echo !empty($car['insurance_expiry']) ? date('d/m/Y', strtotime($car['insurance_expiry'])) : 'Belum Diisi'; ?></strong>
                                    </div>
                                </div>

                                <!-- BUTANG DOKUMEN DALAM GRID 3 KOLOM SEIMBANG -->
                                <div class="car-modal-doc-grid" style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 6px; border-top: 1.5px dashed #cbd5e1; padding-top: 10px; width: 100%; box-sizing: border-box;">
                                    <?php if (!empty($car['grant_file'])): ?>
                                        <a href="../<?php echo htmlspecialchars($car['grant_file']); ?>" target="_blank" class="neo-btn btn-sm btn-yellow car-modal-doc-btn" style="justify-content: center; font-size: 0.72rem; padding: 7px 2px; text-align: center; min-width: 0; gap: 4px; letter-spacing: 0; box-sizing: border-box;" title="Lihat Geran Kenderaan">
                                            <i class="bi bi-file-earmark-text" style="font-size: 0.8rem; flex-shrink: 0;"></i>
                                            <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">Geran</span>
                                        </a>
                                    <?php else: ?>
                                        <span class="neo-btn btn-sm car-modal-doc-btn" style="background: #f1f5f9; color: #94a3b8; border-color: #cbd5e1; box-shadow: none; justify-content: center; font-size: 0.72rem; padding: 7px 2px; text-align: center; cursor: not-allowed; min-width: 0; gap: 4px; letter-spacing: 0; box-sizing: border-box;">
                                            <i class="bi bi-file-earmark-text" style="font-size: 0.8rem; flex-shrink: 0;"></i>
                                            <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">Geran</span>
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($car['roadtax_file'])): ?>
                                        <a href="../<?php echo htmlspecialchars($car['roadtax_file']); ?>" target="_blank" class="neo-btn btn-sm btn-green car-modal-doc-btn" style="justify-content: center; font-size: 0.72rem; padding: 7px 2px; text-align: center; min-width: 0; gap: 4px; letter-spacing: 0; box-sizing: border-box;" title="Lihat Roadtax Kenderaan">
                                            <i class="bi bi-file-earmark-check" style="font-size: 0.8rem; flex-shrink: 0;"></i>
                                            <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">Roadtax</span>
                                        </a>
                                    <?php else: ?>
                                        <span class="neo-btn btn-sm car-modal-doc-btn" style="background: #f1f5f9; color: #94a3b8; border-color: #cbd5e1; box-shadow: none; justify-content: center; font-size: 0.72rem; padding: 7px 2px; text-align: center; cursor: not-allowed; min-width: 0; gap: 4px; letter-spacing: 0; box-sizing: border-box;">
                                            <i class="bi bi-file-earmark-check" style="font-size: 0.8rem; flex-shrink: 0;"></i>
                                            <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">Roadtax</span>
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($car['insurance_file'])): ?>
                                        <a href="../<?php echo htmlspecialchars($car['insurance_file']); ?>" target="_blank" class="neo-btn btn-sm btn-blue car-modal-doc-btn" style="justify-content: center; font-size: 0.72rem; padding: 7px 2px; text-align: center; min-width: 0; gap: 4px; letter-spacing: 0; box-sizing: border-box;" title="Lihat Polisi Insurans">
                                            <i class="bi bi-shield-check" style="font-size: 0.8rem; flex-shrink: 0;"></i>
                                            <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">Insurans</span>
                                        </a>
                                    <?php else: ?>
                                        <span class="neo-btn btn-sm car-modal-doc-btn" style="background: #f1f5f9; color: #94a3b8; border-color: #cbd5e1; box-shadow: none; justify-content: center; font-size: 0.72rem; padding: 7px 2px; text-align: center; cursor: not-allowed; min-width: 0; gap: 4px; letter-spacing: 0; box-sizing: border-box;">
                                            <i class="bi bi-shield-check" style="font-size: 0.8rem; flex-shrink: 0;"></i>
                                            <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">Insurans</span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- STATUS SEWAAN & BUTANG TUKAR STATUS (KEMAS & BERSTRUKTUR) -->
                            <div style="background: <?php echo $is_available ? '#f0fdf4' : '#fff1f2'; ?>; border: 2px solid var(--black); border-radius: var(--radius-md); padding: 12px 14px; margin-bottom: 14px; box-shadow: 2px 2px 0px var(--black);">
                                <div>
                                    <div style="font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Status Ketersediaan</div>
                                    <div style="font-size: 0.92rem; font-weight: 900; color: <?php echo $is_available ? '#15803d' : '#b91c1c'; ?>; display: flex; align-items: center; gap: 6px; margin-top: 2px;">
                                        <i class="bi <?php echo $status_icon; ?>"></i>
                                        <?php echo $is_available ? 'Dibuka untuk Tempahan' : 'Tempahan Ditutup Sementara'; ?>
                                    </div>
                                </div>

                                <div style="border-top: 1.5px dashed <?php echo $is_available ? '#86efac' : '#fca5a5'; ?>; margin: 10px 0;"></div>

                                <form action="" method="POST" style="margin: 0;">
                                    <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                    <input type="hidden" name="new_status" value="<?php echo $is_available ? 'Unavailable' : 'Available'; ?>">
                                    <button type="submit" name="toggle_status" class="neo-btn" style="width: 100%; justify-content: center; font-size: 0.84rem; padding: 8px 12px; font-weight: 800; background: <?php echo $is_available ? '#fecdd3' : '#bbf7d0'; ?>; color: var(--black);">
                                        <i class="bi <?php echo $is_available ? 'bi-slash-circle text-danger' : 'bi-check-circle text-success'; ?> me-1.5"></i>
                                        <?php echo $is_available ? 'Tutup Tempahan Kenderaan' : 'Buka Tempahan Kenderaan'; ?>
                                    </button>
                                </form>
                            </div>

                            <!-- TINDAKAN PENGURUSAN KENDERAAN -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; width: 100%;">
                                <button type="button" class="neo-btn btn-blue" style="justify-content: center; font-size: 0.86rem; padding: 9px 10px;" onclick="closeModal('carDetailModal<?php echo $car['id']; ?>'); openEditModal(<?php echo htmlspecialchars(json_encode($car)); ?>)">
                                    <i class="bi bi-pencil-square me-1"></i> Kemaskini
                                </button>

                                <form action="" method="POST" style="margin: 0;" onsubmit="return confirm('Adakah anda pasti ingin memadam kereta ini?');">
                                    <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                    <button type="submit" name="delete_car" class="neo-btn btn-pink" style="width: 100%; justify-content: center; font-size: 0.86rem; padding: 9px 10px;">
                                        <i class="bi bi-trash-fill me-1"></i> Padam
                                    </button>
                                </form>
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
    <div class="neo-modal-overlay" id="addCarModal" onclick="if(event.target === this) closeModal('addCarModal')">
        <div class="neo-modal" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="modal-title"><i class="bi bi-plus-circle-fill me-1"></i> Tambah Kereta Baharu</h3>
                <button type="button" class="close-btn" onclick="closeModal('addCarModal')">&times;</button>
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

                <!-- DOKUMEN KENDERAAN & TARIKH SAH LAKU -->
                <div style="border-top: 2px dashed var(--black); margin: 20px 0 15px 0; padding-top: 15px;">
                    <h5 style="font-weight: 900; text-transform: uppercase; font-size: 0.95rem; margin-bottom: 12px; color: #0055ff;">
                        <i class="bi bi-file-earmark-lock2-fill me-1"></i> Dokumen Kenderaan & Tarikh Sah Laku
                    </h5>

                    <!-- KOTAK PERINGATAN PRIVASI UNTUK SIJIL PEMILIKAN (VOC / GERAN) -->
                    <div class="neo-alert" style="background: #fff8e1; border: 2px solid var(--black); box-shadow: 3px 3px 0px var(--black); padding: 12px; margin-bottom: 15px; font-size: 0.85rem; line-height: 1.45;">
                        <div style="font-weight: 900; color: #b78103; margin-bottom: 5px; display: flex; align-items: center; gap: 6px;">
                            <i class="bi bi-shield-exclamation fs-5"></i> <strong>Panduan Privasi Sijil Pemilikan Kenderaan (VOC / Geran):</strong>
                        </div>
                        <p style="margin: 0 0 6px 0; font-weight: 700; color: #333;">
                            Pemilik kenderaan diminta menutup (sensor/mask) maklumat peribadi sensitif (seperti nama pemilik lama, nombor kad pengenalan, atau alamat kediaman) sebelum memuat naik salinan geran.
                        </p>
                        <p style="margin: 0; font-weight: 800; color: #000;">
                            <strong>Maklumat yang WAJIB kelihatan jelas hanyalah:</strong>
                        </p>
                        <ul style="margin: 4px 0 0 18px; padding: 0; font-weight: 700; color: #444;">
                            <li>1. Nombor Pendaftaran Kenderaan (No Plat)</li>
                            <li>2. Nombor Chasis / Nombor Enjin</li>
                            <li>3. Buatan / Nama Model</li>
                            <li>4. Keupayaan Enjin (CC)</li>
                            <li>5. Bahan Bakar (Petrol/Diesel)</li>
                        </ul>
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label class="form-label"><i class="bi bi-file-earmark-text-fill text-primary me-1"></i> Salinan Sijil Pemilikan Kenderaan (Geran / VOC)</label>
                        <input type="file" class="form-control" name="grant_file" accept=".jpg,.jpeg,.png,.pdf" required style="border-style: dashed;">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><i class="bi bi-calendar-event me-1"></i> Cukai Jalan Sah Sehingga</label>
                            <input type="date" class="form-control" name="roadtax_expiry" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="bi bi-file-earmark-check me-1"></i> Salinan Cukai Jalan (Roadtax)</label>
                            <input type="file" class="form-control" name="roadtax_file" accept=".jpg,.jpeg,.png,.pdf" required style="border-style: dashed;">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><i class="bi bi-calendar-check me-1"></i> Insurans Sah Sehingga</label>
                            <input type="date" class="form-control" name="insurance_expiry" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="bi bi-shield-check me-1"></i> Salinan Polisi Insurans</label>
                            <input type="file" class="form-control" name="insurance_file" accept=".jpg,.jpeg,.png,.pdf" required style="border-style: dashed;">
                        </div>
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
    <div class="neo-modal-overlay" id="editCarModal" onclick="if(event.target === this) closeModal('editCarModal')">
        <div class="neo-modal" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="modal-title"><i class="bi bi-pencil-square me-1"></i> Kemaskini Maklumat Kereta</h3>
                <button type="button" class="close-btn" onclick="closeModal('editCarModal')">&times;</button>
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

                <!-- DOKUMEN KENDERAAN & TARIKH SAH LAKU (EDIT) -->
                <div style="border-top: 2px dashed var(--black); margin: 20px 0 15px 0; padding-top: 15px;">
                    <h5 style="font-weight: 900; text-transform: uppercase; font-size: 0.95rem; margin-bottom: 12px; color: #0055ff;">
                        <i class="bi bi-file-earmark-lock2-fill me-1"></i> Dokumen Kenderaan & Tarikh Sah Laku
                    </h5>

                    <!-- KOTAK PERINGATAN PRIVASI UNTUK SIJIL PEMILIKAN (VOC / GERAN) -->
                    <div class="neo-alert" style="background: #fff8e1; border: 2px solid var(--black); box-shadow: 3px 3px 0px var(--black); padding: 12px; margin-bottom: 15px; font-size: 0.85rem; line-height: 1.45;">
                        <div style="font-weight: 900; color: #b78103; margin-bottom: 5px; display: flex; align-items: center; gap: 6px;">
                            <i class="bi bi-shield-exclamation fs-5"></i> <strong>Panduan Privasi Sijil Pemilikan Kenderaan (VOC / Geran):</strong>
                        </div>
                        <p style="margin: 0; font-weight: 700; color: #333;">
                            Pastikan maklumat sensitif ditutup sebelum memuat naik. Hanya no. pendaftaran, no. chasis/enjin, buatan/model, cc enjin dan bahan bakar diperlukan.
                        </p>
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label class="form-label"><i class="bi bi-file-earmark-text-fill text-primary me-1"></i> Tukar Sijil Pemilikan Kenderaan (Pilihan)</label>
                        <input type="file" class="form-control" name="grant_file" accept=".jpg,.jpeg,.png,.pdf" style="border-style: dashed;">
                        <div id="current_grant_file" style="margin-top: 5px; font-size: 0.8rem; font-weight: 700;"></div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><i class="bi bi-calendar-event me-1"></i> Cukai Jalan Sah Sehingga</label>
                            <input type="date" class="form-control" name="roadtax_expiry" id="edit_roadtax_expiry" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="bi bi-file-earmark-check me-1"></i> Tukar Cukai Jalan (Pilihan)</label>
                            <input type="file" class="form-control" name="roadtax_file" accept=".jpg,.jpeg,.png,.pdf" style="border-style: dashed;">
                            <div id="current_roadtax_file" style="margin-top: 5px; font-size: 0.8rem; font-weight: 700;"></div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><i class="bi bi-calendar-check me-1"></i> Insurans Sah Sehingga</label>
                            <input type="date" class="form-control" name="insurance_expiry" id="edit_insurance_expiry" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="bi bi-shield-check me-1"></i> Tukar Polisi Insurans (Pilihan)</label>
                            <input type="file" class="form-control" name="insurance_file" accept=".jpg,.jpeg,.png,.pdf" style="border-style: dashed;">
                            <div id="current_insurance_file" style="margin-top: 5px; font-size: 0.8rem; font-weight: 700;"></div>
                        </div>
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
    <div class="neo-modal-overlay" id="qrCodeModal" onclick="if(event.target === this) closeModal('qrCodeModal')">
        <div class="neo-modal" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="modal-title"><i class="bi bi-qr-code me-1"></i> Kod QR DuitNow</h3>
                <button type="button" class="close-btn" onclick="closeModal('qrCodeModal')">&times;</button>
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
            document.getElementById('edit_roadtax_expiry').value = car.roadtax_expiry || '';
            document.getElementById('edit_insurance_expiry').value = car.insurance_expiry || '';

            const grantContainer = document.getElementById('current_grant_file');
            if (car.grant_file) {
                const grantUrl = (car.grant_file.startsWith('http') || car.grant_file.startsWith('../')) ? car.grant_file : '../' + car.grant_file;
                grantContainer.innerHTML = `<span style="color: #2e7d32;">Fail sedia ada: <a href="${grantUrl}" target="_blank" style="text-decoration: underline; font-weight: 800;"><i class="bi bi-file-earmark-pdf me-1"></i>Buka Geran</a></span>`;
            } else {
                grantContainer.innerHTML = `<span style="color: #999;">Tiada fail geran dimuat naik</span>`;
            }

            const roadtaxContainer = document.getElementById('current_roadtax_file');
            if (car.roadtax_file) {
                const roadtaxUrl = (car.roadtax_file.startsWith('http') || car.roadtax_file.startsWith('../')) ? car.roadtax_file : '../' + car.roadtax_file;
                roadtaxContainer.innerHTML = `<span style="color: #2e7d32;">Fail sedia ada: <a href="${roadtaxUrl}" target="_blank" style="text-decoration: underline; font-weight: 800;"><i class="bi bi-file-earmark-pdf me-1"></i>Buka Roadtax</a></span>`;
            } else {
                roadtaxContainer.innerHTML = `<span style="color: #999;">Tiada fail roadtax dimuat naik</span>`;
            }

            const insuranceContainer = document.getElementById('current_insurance_file');
            if (car.insurance_file) {
                const insUrl = (car.insurance_file.startsWith('http') || car.insurance_file.startsWith('../')) ? car.insurance_file : '../' + car.insurance_file;
                insuranceContainer.innerHTML = `<span style="color: #2e7d32;">Fail sedia ada: <a href="${insUrl}" target="_blank" style="text-decoration: underline; font-weight: 800;"><i class="bi bi-file-earmark-pdf me-1"></i>Buka Insurans</a></span>`;
            } else {
                insuranceContainer.innerHTML = `<span style="color: #999;">Tiada fail insurans dimuat naik</span>`;
            }

            openModal('editCarModal');
        }
    </script>
</body>
</html>
