<?php
session_start();
require 'db.php';

// Semak jika admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$message = "";
$message_type = "";

// 1. TAMBAH KERETA (CREATE)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_create_car'])) {
    $provider_id = (int)$_POST['provider_id'];
    $car_brand = trim($_POST['car_brand']);
    $car_model = trim($_POST['car_model']);
    $car_plate = strtoupper(trim($_POST['car_plate']));
    $transmission = $_POST['transmission'];
    $seat_capacity = (int)$_POST['seat_capacity'];
    $price_per_day = (float)$_POST['price_per_day'];
    $price_per_hour = (float)$_POST['price_per_hour'];
    $status = $_POST['status'] ?? 'Available';

    // Proses Muat Naik Gambar Kereta
    $car_image_path = "";
    if (!empty($_FILES['car_image']['name'])) {
        $targetDir = "uploads/cars/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $imgName = basename($_FILES["car_image"]["name"]);
        $newImgName = "Car_" . time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $imgName);
        $targetPath = $targetDir . $newImgName;

        if (move_uploaded_file($_FILES["car_image"]["tmp_name"], $targetPath)) {
            $car_image_path = $targetPath;
        }
    }

    if (empty($car_image_path)) {
        $car_image_path = "uploads/cars/default.png";
    }

    $sql_ins = "INSERT INTO cars (provider_id, car_brand, car_model, car_plate, transmission, seat_capacity, price_per_day, price_per_hour, car_image, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_ins = $conn->prepare($sql_ins);
    $stmt_ins->bind_param("issssiidds", $provider_id, $car_brand, $car_model, $car_plate, $transmission, $seat_capacity, $price_per_day, $price_per_hour, $car_image_path, $status);
    
    if ($stmt_ins->execute()) {
        $message = "Kenderaan baharu berjaya didaftarkan ke dalam sistem!";
        $message_type = "success";
    } else {
        $message = "Ralat pangkalan data: " . $stmt_ins->error;
        $message_type = "danger";
    }
    $stmt_ins->close();
}

// 2. KEMASKINI KERETA (UPDATE)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_update_car'])) {
    $car_id = (int)$_POST['car_id'];
    $provider_id = (int)$_POST['provider_id'];
    $car_brand = trim($_POST['car_brand']);
    $car_model = trim($_POST['car_model']);
    $car_plate = strtoupper(trim($_POST['car_plate']));
    $transmission = $_POST['transmission'];
    $seat_capacity = (int)$_POST['seat_capacity'];
    $price_per_day = (float)$_POST['price_per_day'];
    $price_per_hour = (float)$_POST['price_per_hour'];
    $status = $_POST['status'];

    if (!empty($_FILES['car_image']['name'])) {
        $targetDir = "uploads/cars/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $imgName = basename($_FILES["car_image"]["name"]);
        $newImgName = "Car_" . time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $imgName);
        $targetPath = $targetDir . $newImgName;

        if (move_uploaded_file($_FILES["car_image"]["tmp_name"], $targetPath)) {
            $sql_upd = "UPDATE cars SET provider_id = ?, car_brand = ?, car_model = ?, car_plate = ?, transmission = ?, seat_capacity = ?, price_per_day = ?, price_per_hour = ?, status = ?, car_image = ? WHERE id = ?";
            $stmt_upd = $conn->prepare($sql_upd);
            $stmt_upd->bind_param("issssiiddsi", $provider_id, $car_brand, $car_model, $car_plate, $transmission, $seat_capacity, $price_per_day, $price_per_hour, $status, $targetPath, $car_id);
        }
    } else {
        $sql_upd = "UPDATE cars SET provider_id = ?, car_brand = ?, car_model = ?, car_plate = ?, transmission = ?, seat_capacity = ?, price_per_day = ?, price_per_hour = ?, status = ? WHERE id = ?";
        $stmt_upd = $conn->prepare($sql_upd);
        $stmt_upd->bind_param("issssiiddi", $provider_id, $car_brand, $car_model, $car_plate, $transmission, $seat_capacity, $price_per_day, $price_per_hour, $status, $car_id);
    }

    if ($stmt_upd->execute()) {
        $message = "Maklumat kenderaan berjaya dikemaskini!";
        $message_type = "success";
    } else {
        $message = "Ralat mengemaskini kenderaan: " . $stmt_upd->error;
        $message_type = "danger";
    }
    $stmt_upd->close();
}

// 3. PADAM KERETA (DELETE)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_delete_car'])) {
    $car_id = (int)$_POST['car_id'];
    
    $stmt_del = $conn->prepare("DELETE FROM cars WHERE id = ?");
    $stmt_del->bind_param("i", $car_id);
    if ($stmt_del->execute()) {
        $message = "Kenderaan berjaya dipadam daripada sistem!";
        $message_type = "success";
    } else {
        $message = "Ralat memadam kenderaan: " . $stmt_del->error;
        $message_type = "danger";
    }
    $stmt_del->close();
}

// Ambil Senarai Penyedia untuk Dropdown
$providers_list = $conn->query("SELECT id, full_name, username FROM providers ORDER BY full_name ASC");
$all_providers = [];
while ($p = $providers_list->fetch_assoc()) {
    $all_providers[] = $p;
}

// 4. BACA SENARAI KERETA (READ DENGAN CARIAN & PENAPIS)
$search = trim($_GET['search'] ?? '');
$filter_status = trim($_GET['filter_status'] ?? 'all');
$filter_provider = (int)($_GET['filter_provider'] ?? 0);

$sql_query = "SELECT c.*, p.full_name AS provider_name, p.username AS provider_username 
              FROM cars c 
              JOIN providers p ON c.provider_id = p.id 
              WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $sql_query .= " AND (c.car_brand LIKE ? OR c.car_model LIKE ? OR c.car_plate LIKE ? OR p.full_name LIKE ?)";
    $searchTerm = "%" . $search . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ssss";
}

if ($filter_status !== 'all' && in_array($filter_status, ['Available', 'Unavailable'])) {
    $sql_query .= " AND c.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if ($filter_provider > 0) {
    $sql_query .= " AND c.provider_id = ?";
    $params[] = $filter_provider;
    $types .= "i";
}

$sql_query .= " ORDER BY c.id DESC";

$stmt_list = $conn->prepare($sql_query);
if (!empty($params)) {
    $stmt_list->bind_param($types, ...$params);
}
$stmt_list->execute();
$cars_result = $stmt_list->get_result();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pengurusan Kenderaan - Panel Admin</title>
    
    <!-- Ikon Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700;900&display=swap" rel="stylesheet">

    <!-- CSS NEO-BRUTALISM -->
    <style>
        :root {
            --black: #000000;
            --white: #ffffff;
            --yellow: #ffde59;
            --green: #00e676;
            --blue: #00e5ff;
            --pink: #ff66c4;
            --orange: #ff914d;
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
        button, input, select { font-family: inherit; }

        /* NAVBAR */
        .neo-navbar {
            background-color: var(--white);
            border-bottom: var(--border-thick);
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky; top: 0; z-index: 1000;
        }
        .neo-nav-left { display: flex; align-items: center; gap: 15px; }
        .menu-toggle-btn { font-size: 2rem; color: var(--black); background: none; border: none; cursor: pointer; transition: var(--transition); }
        .menu-toggle-btn:hover { transform: scale(1.1); }
        .neo-brand { font-size: 1.5rem; font-weight: 900; letter-spacing: 2px; text-transform: uppercase; }

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

        .sidebar-nav { padding: 20px; display: flex; flex-direction: column; gap: 8px; overflow-y: auto; }
        .sidebar-link {
            padding: 10px 14px; border: 3px solid transparent; font-weight: 800;
            text-transform: uppercase; display: flex; align-items: center; gap: 12px; transition: var(--transition);
            font-size: 0.9rem;
        }
        .sidebar-link.active, .sidebar-link:hover { border: 3px solid var(--black); background: var(--white); transform: translate(-2px, -2px); box-shadow: 4px 4px 0px var(--black); }
        .sidebar-link.logout-link:hover { background-color: var(--pink); }

        /* MAIN CONTENT */
        .main-content {
            flex: 1;
            padding: 2rem 20px;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        .page-header {
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .neo-btn {
            background-color: var(--yellow);
            border: 3px solid var(--black);
            box-shadow: 4px 4px 0px var(--black);
            font-weight: 900;
            text-transform: uppercase;
            padding: 10px 16px;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }
        .neo-btn:hover { transform: translate(-2px, -2px); box-shadow: 6px 6px 0px var(--black); }
        .neo-btn:active { transform: translate(4px, 4px); box-shadow: var(--shadow-active); }
        .btn-green { background-color: var(--green); }
        .btn-blue { background-color: var(--blue); }
        .btn-pink { background-color: var(--pink); }

        .neo-alert {
            border: var(--border-thick); box-shadow: 4px 4px 0px var(--black);
            padding: 12px 15px; font-weight: 800; margin-bottom: 20px; text-transform: uppercase; font-size: 0.9rem;
            display: flex; align-items: center; gap: 10px;
        }
        .alert-success { background-color: var(--green); }
        .alert-danger { background-color: var(--pink); }

        /* FILTER CARD */
        .filter-card {
            background-color: var(--white);
            border: var(--border-thick);
            box-shadow: var(--shadow-solid);
            padding: 15px 20px;
            margin-bottom: 25px;
        }
        .filter-form {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        .filter-input {
            border: 3px solid var(--black);
            padding: 9px 12px;
            font-weight: 700;
            background-color: var(--bg-color);
            flex: 1;
            min-width: 200px;
        }
        .filter-select {
            border: 3px solid var(--black);
            padding: 9px 12px;
            font-weight: 800;
            background-color: var(--bg-color);
        }

        /* TABLE */
        .table-card {
            background-color: var(--white);
            border: var(--border-thick);
            box-shadow: var(--shadow-solid);
            padding: 20px;
        }
        .neo-table-wrapper { overflow-x: auto; }
        .neo-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-weight: 700;
            font-size: 0.9rem;
        }
        .neo-table th {
            background-color: var(--blue);
            border: 2px solid var(--black);
            padding: 10px;
            text-transform: uppercase;
            font-weight: 900;
            font-size: 0.85rem;
            white-space: nowrap;
        }
        .neo-table td {
            border: 2px solid var(--black);
            padding: 10px;
            vertical-align: middle;
        }
        .neo-table tr:nth-child(even) { background-color: #fafafa; }

        .car-thumb {
            width: 65px;
            height: 45px;
            object-fit: cover;
            border: 2px solid var(--black);
            box-shadow: 2px 2px 0px var(--black);
        }

        .neo-badge {
            border: 2px solid var(--black);
            padding: 3px 8px;
            font-weight: 900;
            text-transform: uppercase;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .badge-approved { background-color: var(--green); }
        .badge-rejected { background-color: var(--pink); }

        .action-btns { display: flex; gap: 6px; flex-wrap: wrap; }
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
            border-width: 2px;
            box-shadow: 2px 2px 0px var(--black);
        }

        /* MODAL POPUP */
        .neo-modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6); z-index: 2000;
            display: none; align-items: center; justify-content: center; padding: 15px;
        }
        .neo-modal-overlay.show { display: flex; }
        .neo-modal {
            background: var(--white); border: var(--border-thick);
            box-shadow: 10px 10px 0px var(--black); width: 100%; max-width: 550px;
            max-height: 90vh; overflow-y: auto; padding: 25px; position: relative;
        }
        .modal-header {
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 3px solid var(--black); padding-bottom: 10px; margin-bottom: 15px;
        }
        .form-group { margin-bottom: 14px; display: flex; flex-direction: column; gap: 4px; }
        .form-label { font-weight: 800; text-transform: uppercase; font-size: 0.8rem; color: #333; }
        .form-control {
            border: 2px solid var(--black); padding: 8px 10px; font-weight: 700;
            background-color: var(--bg-color); width: 100%;
        }

        footer {
            background-color: var(--yellow);
            border-top: var(--border-thick);
            padding: 20px;
            text-align: center;
            font-weight: 900;
            text-transform: uppercase;
            margin-top: auto;
        }

        @media (max-width: 768px) {
            .main-content { padding: 1rem 10px; }
            .filter-form { flex-direction: column; align-items: stretch; }
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
        <a href="admin_dashboard.php" class="neo-btn" style="padding: 6px 12px; font-size: 0.8rem;">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
    </header>

    <!-- SIDEBAR -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Panel Admin</h2>
            <button class="close-btn" id="close-sidebar"><i class="bi bi-x-lg"></i></button>
        </div>
        <nav class="sidebar-nav">
            <a href="admin_dashboard.php" class="sidebar-link"><i class="bi bi-speedometer2"></i> Papan Pemuka</a>
            <a href="admin_students.php" class="sidebar-link"><i class="bi bi-mortarboard-fill"></i> Urus Pelajar</a>
            <a href="admin_providers.php" class="sidebar-link"><i class="bi bi-people-fill"></i> Urus Penyedia</a>
            <a href="admin_cars.php" class="sidebar-link active"><i class="bi bi-car-front-fill"></i> Urus Kenderaan</a>
            <a href="admin_bookings.php" class="sidebar-link"><i class="bi bi-calendar-check-fill"></i> Urus Tempahan</a>
            <a href="logout.php" class="sidebar-link logout-link"><i class="bi bi-box-arrow-right"></i> Log Keluar</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        
        <div class="page-header">
            <div>
                <h1 style="font-size: 1.6rem; font-weight: 900; text-transform: uppercase; margin: 0; color: var(--black); display: flex; align-items: center; gap: 10px;">
                    <i class="bi bi-car-front-fill"></i> Pengurusan Kenderaan
                </h1>
                <p style="font-weight: 700; color: #555; margin: 5px 0 0 0; font-size: 0.9rem;">
                    Daftar kereta baharu, tetapkan kadar harga harian/jam, kemaskini butiran kereta, dan padam kenderaan.
                </p>
            </div>
            <button class="neo-btn btn-green" onclick="openCreateModal()">
                <i class="bi bi-plus-circle-fill"></i> Tambah Kereta Baharu
            </button>
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
                <input type="text" name="search" class="filter-input" placeholder="Cari model, jenama, no plat, nama penyedia..." value="<?php echo htmlspecialchars($search); ?>">
                
                <select name="filter_provider" class="filter-select">
                    <option value="0">Semua Penyedia</option>
                    <?php foreach ($all_providers as $pr): ?>
                        <option value="<?php echo $pr['id']; ?>" <?php if ($filter_provider == $pr['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($pr['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="filter_status" class="filter-select">
                    <option value="all" <?php if ($filter_status === 'all') echo 'selected'; ?>>Semua Status</option>
                    <option value="Available" <?php if ($filter_status === 'Available') echo 'selected'; ?>>Available (Sedia Disewa)</option>
                    <option value="Unavailable" <?php if ($filter_status === 'Unavailable') echo 'selected'; ?>>Unavailable (Tidak Tersedia)</option>
                </select>

                <button type="submit" class="neo-btn btn-blue" style="padding: 9px 16px;">
                    <i class="bi bi-search"></i> Cari
                </button>
                <a href="admin_cars.php" class="neo-btn" style="background: #e0e0e0; padding: 9px 14px;">
                    <i class="bi bi-arrow-clockwise"></i> Reset
                </a>
            </form>
        </div>

        <!-- JADUAL SENARAI KERETA (READ) -->
        <div class="table-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="font-size: 1.1rem; font-weight: 900; text-transform: uppercase;">
                    Senarai Kenderaan (<?php echo $cars_result->num_rows; ?> rekod)
                </h3>
            </div>

            <div class="neo-table-wrapper">
                <table class="neo-table">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Model & Jenama</th>
                            <th>No Plat</th>
                            <th>Penyedia</th>
                            <th>Transmisi / Muatan</th>
                            <th>Kadar Sewa (RM)</th>
                            <th>Status</th>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($cars_result->num_rows > 0): ?>
                            <?php while ($c = $cars_result->fetch_assoc()): 
                                $status_cls = ($c['status'] === 'Available') ? 'badge-approved' : 'badge-rejected';
                                $c_json = htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8');
                            ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo htmlspecialchars($c['car_image']); ?>" class="car-thumb" alt="Kereta">
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($c['car_brand']); ?> <?php echo htmlspecialchars($c['car_model']); ?></strong>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($c['car_plate']); ?></code></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($c['provider_name']); ?></strong><br>
                                        <small style="color: #666;">@<?php echo htmlspecialchars($c['provider_username']); ?></small>
                                    </td>
                                    <td>
                                        <small><i class="bi bi-gear"></i> <?php echo htmlspecialchars($c['transmission']); ?></small><br>
                                        <small><i class="bi bi-people"></i> <?php echo htmlspecialchars($c['seat_capacity']); ?> Tempat Duduk</small>
                                    </td>
                                    <td>
                                        <small>Hari: <strong>RM <?php echo number_format($c['price_per_day'], 2); ?></strong></small><br>
                                        <small>Jam: <strong>RM <?php echo number_format($c['price_per_hour'], 2); ?></strong></small>
                                    </td>
                                    <td>
                                        <span class="neo-badge <?php echo $status_cls; ?>">
                                            <?php echo $c['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="neo-btn btn-sm btn-blue" data-car='<?php echo $c_json; ?>' onclick="viewCar(this)" title="Lihat Penuh">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                            <button class="neo-btn btn-sm btn-yellow" data-car='<?php echo $c_json; ?>' onclick="editCar(this)" title="Kemaskini">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <form action="" method="POST" style="margin: 0;" onsubmit="return confirm('Adakah anda pasti ingin memadam kenderaan ini? Rekod tempahan berkaitan turut akan terjejas!');">
                                                <input type="hidden" name="car_id" value="<?php echo $c['id']; ?>">
                                                <button type="submit" name="action_delete_car" class="neo-btn btn-sm btn-pink" title="Padam">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 25px; color: #777;">Tiada rekod kenderaan dijumpai.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- MODAL CREATE KERETA -->
    <div class="neo-modal-overlay" id="createModalOverlay" onclick="closeCreateModalOutside(event)">
        <div class="neo-modal" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 style="font-weight: 900; text-transform: uppercase; font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-plus-circle-fill text-success"></i> Tambah Kereta Baharu
                </h3>
                <button class="close-btn" onclick="closeCreateModal()">X</button>
            </div>
            
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action_create_car" value="1">
                
                <div class="form-group">
                    <label class="form-label">Penyedia Kereta:</label>
                    <select name="provider_id" class="form-control" required>
                        <option value="">-- Pilih Penyedia --</option>
                        <?php foreach ($all_providers as $pr): ?>
                            <option value="<?php echo $pr['id']; ?>"><?php echo htmlspecialchars($pr['full_name']); ?> (@<?php echo htmlspecialchars($pr['username']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label">Jenama (Brand):</label>
                        <input type="text" name="car_brand" class="form-control" required placeholder="Contoh: Perodua">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Model:</label>
                        <input type="text" name="car_model" class="form-control" required placeholder="Contoh: Myvi AV">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label">No. Plat Kereta:</label>
                        <input type="text" name="car_plate" class="form-control" required placeholder="Contoh: QAA 1234 B">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Transmisi:</label>
                        <select name="transmission" class="form-control" required>
                            <option value="Auto">Auto</option>
                            <option value="Manual">Manual</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label">Tempat Duduk:</label>
                        <input type="number" name="seat_capacity" class="form-control" value="5" min="1" max="15" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Harga/Hari (RM):</label>
                        <input type="number" step="0.01" name="price_per_day" class="form-control" required placeholder="120.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Harga/Jam (RM):</label>
                        <input type="number" step="0.01" name="price_per_hour" class="form-control" required placeholder="15.00">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label">Status Kenderaan:</label>
                        <select name="status" class="form-control" required>
                            <option value="Available">Available (Tersedia)</option>
                            <option value="Unavailable">Unavailable (Disewa/Baiki)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gambar Kenderaan:</label>
                        <input type="file" name="car_image" accept=".jpg,.jpeg,.png" class="form-control" required>
                    </div>
                </div>

                <div style="margin-top: 15px; display: flex; gap: 10px;">
                    <button type="submit" class="neo-btn btn-green" style="flex: 1; justify-content: center;">
                        <i class="bi bi-check-lg"></i> Simpan Kenderaan
                    </button>
                    <button type="button" class="neo-btn btn-pink" onclick="closeCreateModal()">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT KERETA (UPDATE) -->
    <div class="neo-modal-overlay" id="editModalOverlay" onclick="closeEditModalOutside(event)">
        <div class="neo-modal" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 style="font-weight: 900; text-transform: uppercase; font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-pencil-square text-warning"></i> Kemaskini Kenderaan
                </h3>
                <button class="close-btn" onclick="closeEditModal()">X</button>
            </div>
            
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action_update_car" value="1">
                <input type="hidden" name="car_id" id="editCarId">
                
                <div class="form-group">
                    <label class="form-label">Penyedia Kereta:</label>
                    <select name="provider_id" id="editProviderId" class="form-control" required>
                        <?php foreach ($all_providers as $pr): ?>
                            <option value="<?php echo $pr['id']; ?>"><?php echo htmlspecialchars($pr['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label">Jenama (Brand):</label>
                        <input type="text" name="car_brand" id="editCarBrand" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Model:</label>
                        <input type="text" name="car_model" id="editCarModel" class="form-control" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label">No. Plat Kereta:</label>
                        <input type="text" name="car_plate" id="editCarPlate" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Transmisi:</label>
                        <select name="transmission" id="editTransmission" class="form-control" required>
                            <option value="Auto">Auto</option>
                            <option value="Manual">Manual</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label">Tempat Duduk:</label>
                        <input type="number" name="seat_capacity" id="editSeatCapacity" class="form-control" min="1" max="15" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Harga/Hari (RM):</label>
                        <input type="number" step="0.01" name="price_per_day" id="editPricePerDay" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Harga/Jam (RM):</label>
                        <input type="number" step="0.01" name="price_per_hour" id="editPricePerHour" class="form-control" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label">Status:</label>
                        <select name="status" id="editStatus" class="form-control" required>
                            <option value="Available">Available (Tersedia)</option>
                            <option value="Unavailable">Unavailable (Disewa/Baiki)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tukar Gambar (Pilihan):</label>
                        <input type="file" name="car_image" accept=".jpg,.jpeg,.png" class="form-control">
                    </div>
                </div>

                <div style="margin-top: 15px; display: flex; gap: 10px;">
                    <button type="submit" class="neo-btn btn-yellow" style="flex: 1; justify-content: center;">
                        <i class="bi bi-save"></i> Kemaskini Kenderaan
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
                    <i class="bi bi-car-front-fill text-primary"></i> Butiran Kenderaan
                </h3>
                <button class="close-btn" onclick="closeViewModal()">X</button>
            </div>
            <div style="font-weight: 700; font-size: 0.9rem; display: flex; flex-direction: column; gap: 8px;">
                <div style="text-align: center; margin-bottom: 10px;">
                    <img id="viewCarImg" src="" alt="Kereta" style="max-height: 180px; max-width: 100%; border: 3px solid var(--black); box-shadow: 3px 3px 0 var(--black); object-fit: cover;">
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 6px 0;">
                    <span style="color: #666;">Kenderaan:</span>
                    <span id="viewCarTitle" style="font-weight: 900;"></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 6px 0;">
                    <span style="color: #666;">No. Plat:</span>
                    <span id="viewCarPlate" style="font-weight: 900;"></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 6px 0;">
                    <span style="color: #666;">Penyedia:</span>
                    <span id="viewProvider"></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 6px 0;">
                    <span style="color: #666;">Transmisi & Muatan:</span>
                    <span id="viewTransSeat"></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 6px 0;">
                    <span style="color: #666;">Kadar Sewa Harian:</span>
                    <span id="viewRateDay" style="color: #007700; font-weight: 900;"></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 6px 0;">
                    <span style="color: #666;">Kadar Sewa Jam:</span>
                    <span id="viewRateHour" style="color: #007700; font-weight: 900;"></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 6px 0;">
                    <span style="color: #666;">Status Ketersediaan:</span>
                    <span id="viewStatus"></span>
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

        // CREATE MODAL
        function openCreateModal() { document.getElementById('createModalOverlay').classList.add('show'); }
        function closeCreateModal() { document.getElementById('createModalOverlay').classList.remove('show'); }
        function closeCreateModalOutside(e) { if (e.target.id === 'createModalOverlay') closeCreateModal(); }

        // EDIT MODAL
        function editCar(btn) {
            const data = JSON.parse(btn.getAttribute('data-car'));
            document.getElementById('editCarId').value = data.id;
            document.getElementById('editProviderId').value = data.provider_id;
            document.getElementById('editCarBrand').value = data.car_brand;
            document.getElementById('editCarModel').value = data.car_model;
            document.getElementById('editCarPlate').value = data.car_plate;
            document.getElementById('editTransmission').value = data.transmission;
            document.getElementById('editSeatCapacity').value = data.seat_capacity;
            document.getElementById('editPricePerDay').value = parseFloat(data.price_per_day).toFixed(2);
            document.getElementById('editPricePerHour').value = parseFloat(data.price_per_hour).toFixed(2);
            document.getElementById('editStatus').value = data.status;
            document.getElementById('editModalOverlay').classList.add('show');
        }
        function closeEditModal() { document.getElementById('editModalOverlay').classList.remove('show'); }
        function closeEditModalOutside(e) { if (e.target.id === 'editModalOverlay') closeEditModal(); }

        // VIEW MODAL
        function viewCar(btn) {
            const data = JSON.parse(btn.getAttribute('data-car'));
            document.getElementById('viewCarImg').src = data.car_image;
            document.getElementById('viewCarTitle').textContent = data.car_brand + ' ' + data.car_model;
            document.getElementById('viewCarPlate').textContent = data.car_plate;
            document.getElementById('viewProvider').textContent = data.provider_name + ' (@' + data.provider_username + ')';
            document.getElementById('viewTransSeat').textContent = data.transmission + ' / ' + data.seat_capacity + ' Tempat Duduk';
            document.getElementById('viewRateDay').textContent = 'RM ' + parseFloat(data.price_per_day).toFixed(2) + ' / hari';
            document.getElementById('viewRateHour').textContent = 'RM ' + parseFloat(data.price_per_hour).toFixed(2) + ' / jam';
            document.getElementById('viewStatus').textContent = data.status.toUpperCase();

            document.getElementById('viewModalOverlay').classList.add('show');
        }
        function closeViewModal() { document.getElementById('viewModalOverlay').classList.remove('show'); }
        function closeViewModalOutside(e) { if (e.target.id === 'viewModalOverlay') closeViewModal(); }
    </script>
</body>
</html>
