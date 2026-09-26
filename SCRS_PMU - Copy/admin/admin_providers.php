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

$message = "";
$message_type = "";

// 1. TAMBAH PENYEDIA (CREATE)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_create_provider'])) {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone_no = trim($_POST['phone_no']);
    $no_ic = trim($_POST['no_ic']);
    $password = $_POST['password'];
    $status = $_POST['status'] ?? 'approved';

    // Semak keunikan username
    $chk = $conn->prepare("SELECT id FROM providers WHERE username = ?");
    $chk->bind_param("s", $username);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        $message = "Nama pengguna (Username) '$username' sudah wujud!";
        $message_type = "danger";
    } else {
        $hashed_pwd = password_hash($password, PASSWORD_DEFAULT);
        
        $sql_ins = "INSERT INTO providers (full_name, username, email, phone_no, no_ic, password, status, ic_file, licence_file, insurance_file, greencard_file, roadtax_file) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, '', '', '', '', '')";
        $stmt_ins = $conn->prepare($sql_ins);
        $stmt_ins->bind_param("sssssss", $full_name, $username, $email, $phone_no, $no_ic, $hashed_pwd, $status);
        
        if ($stmt_ins->execute()) {
            $message = "Penyedia kereta baharu berjaya didaftarkan!";
            $message_type = "success";
        } else {
            $message = "Ralat pangkalan data: " . $stmt_ins->error;
            $message_type = "danger";
        }
        $stmt_ins->close();
    }
    $chk->close();
}

// 2. KEMASKINI PENYEDIA (UPDATE)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_update_provider'])) {
    $provider_id = (int)$_POST['provider_id'];
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone_no = trim($_POST['phone_no']);
    $no_ic = trim($_POST['no_ic']);
    $status = $_POST['status'];
    $new_password = $_POST['new_password'];

    // Semak keunikan username
    $chk = $conn->prepare("SELECT id FROM providers WHERE username = ? AND id != ?");
    $chk->bind_param("si", $username, $provider_id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        $message = "Nama pengguna '$username' telah digunakan oleh penyedia lain!";
        $message_type = "danger";
    } else {
        if (!empty($new_password)) {
            $hashed_pwd = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_upd = "UPDATE providers SET full_name = ?, username = ?, email = ?, phone_no = ?, no_ic = ?, status = ?, password = ? WHERE id = ?";
            $stmt_upd = $conn->prepare($sql_upd);
            $stmt_upd->bind_param("sssssssi", $full_name, $username, $email, $phone_no, $no_ic, $status, $hashed_pwd, $provider_id);
        } else {
            $sql_upd = "UPDATE providers SET full_name = ?, username = ?, email = ?, phone_no = ?, no_ic = ?, status = ? WHERE id = ?";
            $stmt_upd = $conn->prepare($sql_upd);
            $stmt_upd->bind_param("ssssssi", $full_name, $username, $email, $phone_no, $no_ic, $status, $provider_id);
        }

        if ($stmt_upd->execute()) {
            $message = "Maklumat penyedia kereta berjaya dikemaskini!";
            $message_type = "success";
        } else {
            $message = "Ralat mengemaskini penyedia: " . $stmt_upd->error;
            $message_type = "danger";
        }
        $stmt_upd->close();
    }
    $chk->close();
}

// 3. PADAM PENYEDIA (DELETE)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_delete_provider'])) {
    $provider_id = (int)$_POST['provider_id'];
    
    $stmt_del = $conn->prepare("DELETE FROM providers WHERE id = ?");
    $stmt_del->bind_param("i", $provider_id);
    if ($stmt_del->execute()) {
        $message = "Rekod penyedia dan semua kenderaannya berjaya dipadam!";
        $message_type = "success";
    } else {
        $message = "Ralat memadam penyedia: " . $stmt_del->error;
        $message_type = "danger";
    }
    $stmt_del->close();
}

// 4. BACA SENARAI PENYEDIA (READ DENGAN CARIAN & PENAPIS)
$search = trim($_GET['search'] ?? '');
$filter_status = trim($_GET['filter_status'] ?? 'all');

$sql_query = "SELECT p.*, COUNT(c.id) AS total_cars 
              FROM providers p 
              LEFT JOIN cars c ON p.id = c.provider_id 
              WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $sql_query .= " AND (p.full_name LIKE ? OR p.username LIKE ? OR p.email LIKE ? OR p.phone_no LIKE ? OR p.no_ic LIKE ?)";
    $searchTerm = "%" . $search . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sssss";
}

if ($filter_status !== 'all' && in_array($filter_status, ['approved', 'pending', 'rejected'])) {
    $sql_query .= " AND p.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

$sql_query .= " GROUP BY p.id ORDER BY p.id DESC";

$stmt_list = $conn->prepare($sql_query);
if (!empty($params)) {
    $stmt_list->bind_param($types, ...$params);
}
$stmt_list->execute();
$providers_result = $stmt_list->get_result();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pengurusan Penyedia Kereta - Panel Admin</title>
    
    <!-- Ikon Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
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

        /* FILTER & SEARCH BAR */
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
            background-color: var(--green);
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
            <a href="admin_providers.php" class="sidebar-link active"><i class="bi bi-people-fill"></i> Urus Penyedia</a>
            <a href="admin_cars.php" class="sidebar-link"><i class="bi bi-car-front-fill"></i> Urus Kenderaan</a>
            <a href="admin_bookings.php" class="sidebar-link"><i class="bi bi-calendar-check-fill"></i> Urus Tempahan</a>
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
                        <i class="bi bi-people-fill"></i> Pengurusan Penyedia Kereta
                    </h1>
                </div>
                <p style="font-weight: 700; color: #555; margin: 5px 0 0 0; font-size: 0.92rem;">
                    Daftar penyedia baharu, semak dokumen pendaftaran & Kod QR, kemaskini maklumat, dan padam rekod.
                </p>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button class="neo-btn btn-sm btn-green" onclick="openCreateModal()">
                    <i class="bi bi-person-plus-fill"></i> Tambah Penyedia Baharu
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
                <input type="text" name="search" class="filter-input" placeholder="Cari nama penyedia, username, no ic, email, telefon..." value="<?php echo htmlspecialchars($search); ?>">
                
                <select name="filter_status" class="filter-select">
                    <option value="all" <?php if ($filter_status === 'all') echo 'selected'; ?>>Semua Status</option>
                    <option value="approved" <?php if ($filter_status === 'approved') echo 'selected'; ?>>Diluluskan (Approved)</option>
                    <option value="pending" <?php if ($filter_status === 'pending') echo 'selected'; ?>>Menunggu (Pending)</option>
                    <option value="rejected" <?php if ($filter_status === 'rejected') echo 'selected'; ?>>Ditolak (Rejected)</option>
                </select>

                <button type="submit" class="neo-btn btn-blue" style="padding: 9px 16px;">
                    <i class="bi bi-search"></i> Cari
                </button>
                <a href="admin_providers.php" class="neo-btn" style="background: #e0e0e0; padding: 9px 14px;">
                    <i class="bi bi-arrow-clockwise"></i> Reset
                </a>
            </form>
        </div>

        <!-- JADUAL SENARAI PENYEDIA (READ) -->
        <div class="table-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="font-size: 1.1rem; font-weight: 900; text-transform: uppercase;">
                    Senarai Penyedia Kereta (<?php echo $providers_result->num_rows; ?> rekod)
                </h3>
            </div>

            <div class="neo-table-wrapper">
                <table class="neo-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Penuh & Username</th>
                            <th>No IC</th>
                            <th>Telefon & E-mel</th>
                            <th>Bil. Kereta</th>
                            <th>Kod QR DuitNow</th>
                            <th>Status Akaun</th>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($providers_result->num_rows > 0): ?>
                            <?php while ($pr = $providers_result->fetch_assoc()): 
                                $status_cls = ($pr['status'] === 'approved') ? 'badge-approved' : (($pr['status'] === 'pending') ? 'badge-pending' : 'badge-rejected');
                                $pr_json = htmlspecialchars(json_encode($pr), ENT_QUOTES, 'UTF-8');
                            ?>
                                <tr>
                                    <td>#<?php echo $pr['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($pr['full_name']); ?></strong><br>
                                        <small style="color: #666;">@<?php echo htmlspecialchars($pr['username']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($pr['no_ic']); ?></td>
                                    <td>
                                        <small><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($pr['phone_no']); ?></small><br>
                                        <small><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($pr['email']); ?></small>
                                    </td>
                                    <td>
                                        <span class="neo-badge" style="background: var(--blue);">
                                            <i class="bi bi-car-front-fill"></i> <?php echo $pr['total_cars']; ?> Buah
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($pr['qr_code_image'])): ?>
                                            <?php 
                                            $qr_url = $pr['qr_code_image'];
                                            if (!str_starts_with($qr_url, 'http') && !str_starts_with($qr_url, '../')) {
                                                $qr_url = '../' . ltrim($qr_url, '/');
                                            }
                                            $qr_file_disk = __DIR__ . '/' . $qr_url;
                                            $cache_ver = file_exists($qr_file_disk) ? filemtime($qr_file_disk) : time();
                                            $qr_url_bust = $qr_url . '?v=' . $cache_ver;
                                            ?>
                                            <a href="<?php echo htmlspecialchars($qr_url_bust); ?>" target="_blank" class="neo-badge badge-approved">
                                                <i class="bi bi-qr-code"></i> Lihat QR
                                            </a>
                                        <?php else: ?>
                                            <span class="badge-status badge-pending"><i class="bi bi-qr-code"></i> Tiada QR</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($pr['status'] === 'approved'): ?>
                                            <span class="badge-status badge-approved"><i class="bi bi-check-circle-fill"></i> Diluluskan</span>
                                        <?php elseif ($pr['status'] === 'pending'): ?>
                                            <span class="badge-status badge-pending"><i class="bi bi-hourglass-split"></i> Menunggu</span>
                                        <?php else: ?>
                                            <span class="badge-status badge-rejected"><i class="bi bi-x-circle-fill"></i> Ditolak</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="neo-btn btn-sm btn-blue" data-provider='<?php echo $pr_json; ?>' onclick="viewProvider(this)" title="Lihat Penuh">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                            <button class="neo-btn btn-sm btn-yellow" data-provider='<?php echo $pr_json; ?>' onclick="editProvider(this)" title="Kemaskini">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <form action="" method="POST" style="margin: 0;" onsubmit="return confirm('Adakah anda pasti ingin memadam penyedia ini? Semua kereta dan rekod tempahannya akan turut dipadam!');">
                                                <input type="hidden" name="provider_id" value="<?php echo $pr['id']; ?>">
                                                <button type="submit" name="action_delete_provider" class="neo-btn btn-sm btn-pink" title="Padam">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 25px; color: #777;">Tiada rekod penyedia dijumpai.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- MODAL CREATE PENYEDIA -->
    <div class="neo-modal-overlay" id="createModalOverlay" onclick="closeCreateModalOutside(event)">
        <div class="neo-modal" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 style="font-weight: 900; text-transform: uppercase; font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-person-plus-fill text-success"></i> Tambah Penyedia Baharu
                </h3>
            </div>
            
            <form action="" method="POST">
                <input type="hidden" name="action_create_provider" value="1">
                
                <div class="form-group">
                    <label class="form-label">Nama Penuh Penyedia:</label>
                    <input type="text" name="full_name" class="form-control" required placeholder="Contoh: Ahmad Zamri">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label">Username:</label>
                        <input type="text" name="username" class="form-control" required placeholder="zamri_car">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kata Laluan:</label>
                        <input type="password" name="password" class="form-control" required placeholder="Kata laluan">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label">No. Kad Pengenalan:</label>
                        <input type="text" name="no_ic" class="form-control" required placeholder="880101-13-5555">
                    </div>
                    <div class="form-group">
                        <label class="form-label">No. Telefon:</label>
                        <input type="text" name="phone_no" class="form-control" required placeholder="0123456789">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label">E-mel:</label>
                        <input type="email" name="email" class="form-control" required placeholder="zamri@gmail.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status Akaun:</label>
                        <select name="status" class="form-control" required>
                            <option value="approved">Approved (Aktif)</option>
                            <option value="pending">Pending (Menunggu)</option>
                            <option value="rejected">Rejected (Disekat)</option>
                        </select>
                    </div>
                </div>

                <div style="margin-top: 15px; display: flex; gap: 10px;">
                    <button type="submit" class="neo-btn btn-green" style="flex: 1; justify-content: center;">
                        <i class="bi bi-check-lg"></i> Simpan Penyedia
                    </button>
                    <button type="button" class="neo-btn btn-pink" onclick="closeCreateModal()">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT PENYEDIA (UPDATE) -->
    <div class="neo-modal-overlay" id="editModalOverlay" onclick="closeEditModalOutside(event)">
        <div class="neo-modal" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 style="font-weight: 900; text-transform: uppercase; font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-pencil-square text-warning"></i> Kemaskini Penyedia
                </h3>
            </div>
            
            <form action="" method="POST">
                <input type="hidden" name="action_update_provider" value="1">
                <input type="hidden" name="provider_id" id="editProviderId">
                
                <div class="form-group">
                    <label class="form-label">Nama Penuh:</label>
                    <input type="text" name="full_name" id="editFullName" class="form-control" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label">Username:</label>
                        <input type="text" name="username" id="editUsername" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tukar Password (Pilihan):</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Biarkan kosong jika sama">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label">No. Kad Pengenalan:</label>
                        <input type="text" name="no_ic" id="editNoIc" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">No. Telefon:</label>
                        <input type="text" name="phone_no" id="editPhoneNo" class="form-control" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label">E-mel:</label>
                        <input type="email" name="email" id="editEmail" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status Akaun:</label>
                        <select name="status" id="editStatus" class="form-control" required>
                            <option value="approved">Approved (Aktif)</option>
                            <option value="pending">Pending (Menunggu)</option>
                            <option value="rejected">Rejected (Disekat)</option>
                        </select>
                    </div>
                </div>

                <div style="margin-top: 15px; display: flex; gap: 10px;">
                    <button type="submit" class="neo-btn btn-yellow" style="flex: 1; justify-content: center;">
                        <i class="bi bi-save"></i> Kemaskini Penyedia
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
                    <i class="bi bi-person-badge-fill text-primary"></i> Butiran & Dokumen Penyedia
                </h3>
            </div>
            <div style="font-weight: 700; font-size: 0.9rem; display: flex; flex-direction: column; gap: 8px;">
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 6px 0;">
                    <span style="color: #666;">ID Penyedia:</span>
                    <span id="viewId" style="font-weight: 900;"></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 6px 0;">
                    <span style="color: #666;">Nama Penuh:</span>
                    <span id="viewFullName" style="font-weight: 900;"></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 6px 0;">
                    <span style="color: #666;">Username:</span>
                    <span id="viewUsername"></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 6px 0;">
                    <span style="color: #666;">No IC:</span>
                    <span id="viewNoIc"></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 6px 0;">
                    <span style="color: #666;">E-mel:</span>
                    <span id="viewEmail"></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 6px 0;">
                    <span style="color: #666;">Telefon:</span>
                    <span id="viewPhoneNo"></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 6px 0;">
                    <span style="color: #666;">Status:</span>
                    <span id="viewStatus"></span>
                </div>

                <div style="margin-top: 10px; font-weight: 900; text-transform: uppercase; font-size: 0.85rem; border-bottom: 2px solid var(--black); padding-bottom: 4px;">
                    Dokumen Pendaftaran & DuitNow:
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 6px 0;">
                    <span style="color: #666;">Kad Pengenalan (IC):</span>
                    <span id="viewIcFile"></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 6px 0;">
                    <span style="color: #666;">Lesen Memandu:</span>
                    <span id="viewLicenceFile"></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 6px 0;">
                    <span style="color: #666;">Insurans Kereta:</span>
                    <span id="viewInsuranceFile"></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 6px 0;">
                    <span style="color: #666;">Roadtax (Cukai Jalan):</span>
                    <span id="viewRoadtaxFile"></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 2px dashed #ccc; padding: 6px 0;">
                    <span style="color: #666;">Kod QR DuitNow:</span>
                    <span id="viewQrFile"></span>
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
        function editProvider(btn) {
            const data = JSON.parse(btn.getAttribute('data-provider'));
            document.getElementById('editProviderId').value = data.id;
            document.getElementById('editFullName').value = data.full_name;
            document.getElementById('editUsername').value = data.username;
            document.getElementById('editNoIc').value = data.no_ic;
            document.getElementById('editEmail').value = data.email;
            document.getElementById('editPhoneNo').value = data.phone_no;
            document.getElementById('editStatus').value = data.status;
            document.getElementById('editModalOverlay').classList.add('show');
        }
        function closeEditModal() { document.getElementById('editModalOverlay').classList.remove('show'); }
        function closeEditModalOutside(e) { if (e.target.id === 'editModalOverlay') closeEditModal(); }

        // VIEW MODAL
        function viewProvider(btn) {
            const data = JSON.parse(btn.getAttribute('data-provider'));
            document.getElementById('viewId').textContent = '#' + data.id;
            document.getElementById('viewFullName').textContent = data.full_name;
            document.getElementById('viewUsername').textContent = '@' + data.username;
            document.getElementById('viewNoIc').textContent = data.no_ic;
            document.getElementById('viewEmail').textContent = data.email;
            const statusMap = {
                'approved': '<span class="badge-status badge-approved"><i class="bi bi-check-circle-fill"></i> Diluluskan</span>',
                'pending': '<span class="badge-status badge-pending"><i class="bi bi-hourglass-split"></i> Menunggu</span>',
                'rejected': '<span class="badge-status badge-rejected"><i class="bi bi-x-circle-fill"></i> Ditolak</span>'
            };
            document.getElementById('viewStatus').innerHTML = statusMap[data.status] || data.status.toUpperCase();

            function makeLink(url) {
                if (url && url.trim() !== '') {
                    const cleanUrl = (url.startsWith('http') || url.startsWith('../')) ? url : ('../' + url.replace(/^\//, ''));
                    const bustUrl = cleanUrl + (cleanUrl.includes('?') ? '&' : '?') + 't=' + new Date().getTime();
                    return '<a href="' + bustUrl + '" target="_blank" class="neo-badge badge-approved"><i class="bi bi-file-earmark-image"></i> Lihat Fail</a>';
                }
                return '<span style="color:#999;">Tiada Fail</span>';
            }

            document.getElementById('viewIcFile').innerHTML = makeLink(data.ic_file);
            document.getElementById('viewLicenceFile').innerHTML = makeLink(data.licence_file);
            document.getElementById('viewInsuranceFile').innerHTML = makeLink(data.insurance_file);
            document.getElementById('viewRoadtaxFile').innerHTML = makeLink(data.roadtax_file);
            document.getElementById('viewQrFile').innerHTML = makeLink(data.qr_code_image);

            document.getElementById('viewModalOverlay').classList.add('show');
        }
        function closeViewModal() { document.getElementById('viewModalOverlay').classList.remove('show'); }
        function closeViewModalOutside(e) { if (e.target.id === 'viewModalOverlay') closeViewModal(); }
    </script>
</body>
</html>
