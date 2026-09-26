<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/notifications.php';

// Kawalan Keselamatan: Hanya Admin boleh mengakses halaman ini
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$admin_id = $_SESSION['admin_id'] ?? 0;
$admin_username = $_SESSION['username'] ?? 'Admin';

$message = "";
$message_type = "";
$current_admin_id = $_SESSION['admin_id'] ?? 0;

// 1. TAMBAH AKAUN BAHARU (ADMIN ATAU JHEPP)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_create_account'])) {
    $target_role = trim($_POST['target_role']); // 'admin' atau 'jhepp'
    $full_name   = trim($_POST['full_name']);
    $username    = trim($_POST['username']);
    $email       = trim($_POST['email']);
    $password    = $_POST['password'];

    if (empty($full_name) || empty($username) || empty($email) || empty($password)) {
        $message = "Sila lengkapkan semua ruangan yang diperlukan!";
        $message_type = "danger";
    } elseif (strlen($password) < 6) {
        $message = "Kata laluan mestilah sekurang-kurangnya 6 aksara!";
        $message_type = "danger";
    } else {
        // Semak keunikan username dalam kedua-dua jadual admins dan jhepp
        $chk_admin = $conn->prepare("SELECT id FROM admins WHERE username = ?");
        $chk_admin->bind_param("s", $username);
        $chk_admin->execute();
        $admin_exists = $chk_admin->get_result()->num_rows > 0;
        $chk_admin->close();

        $chk_jhepp = $conn->prepare("SELECT id FROM jhepp WHERE username = ?");
        $chk_jhepp->bind_param("s", $username);
        $chk_jhepp->execute();
        $jhepp_exists = $chk_jhepp->get_result()->num_rows > 0;
        $chk_jhepp->close();

        if ($admin_exists || $jhepp_exists) {
            $message = "Nama pengguna (Username) '$username' telah wujud dalam sistem! Sila pilih nama pengguna lain.";
            $message_type = "danger";
        } else {
            $hashed_pwd = password_hash($password, PASSWORD_DEFAULT);

            if ($target_role === 'admin') {
                $sql_ins = "INSERT INTO admins (username, email, full_name, password) VALUES (?, ?, ?, ?)";
                $role_name = "Pentadbir Sistem (Admin)";
            } else {
                $sql_ins = "INSERT INTO jhepp (username, email, full_name, password) VALUES (?, ?, ?, ?)";
                $role_name = "Pegawai JHEPP";
            }

            $stmt_ins = $conn->prepare($sql_ins);
            $stmt_ins->bind_param("ssss", $username, $email, $full_name, $hashed_pwd);

            if ($stmt_ins->execute()) {
                $message = "Akaun <strong>$role_name</strong> ($username) berjaya didaftarkan!";
                $message_type = "success";
            } else {
                $message = "Ralat pangkalan data: " . $stmt_ins->error;
                $message_type = "danger";
            }
            $stmt_ins->close();
        }
    }
}

// 2. KEMASKINI AKAUN (UPDATE)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_update_account'])) {
    $target_role  = trim($_POST['target_role']);
    $account_id   = (int)$_POST['account_id'];
    $full_name    = trim($_POST['full_name']);
    $username     = trim($_POST['username']);
    $email        = trim($_POST['email']);
    $new_password = $_POST['new_password'];

    $table = ($target_role === 'admin') ? 'admins' : 'jhepp';

    // Semak keunikan username untuk rekod lain
    $chk = $conn->prepare("SELECT id FROM $table WHERE username = ? AND id != ?");
    $chk->bind_param("si", $username, $account_id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        $message = "Nama pengguna '$username' telah digunakan oleh staf lain!";
        $message_type = "danger";
    } else {
        if (!empty($new_password)) {
            if (strlen($new_password) < 6) {
                $message = "Kata laluan baharu mestilah sekurang-kurangnya 6 aksara!";
                $message_type = "danger";
            } else {
                $hashed_pwd = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt_upd = $conn->prepare("UPDATE $table SET full_name = ?, username = ?, email = ?, password = ? WHERE id = ?");
                $stmt_upd->bind_param("ssssi", $full_name, $username, $email, $hashed_pwd, $account_id);
                if ($stmt_upd->execute()) {
                    $message = "Maklumat akaun dan kata laluan berjaya dikemaskini!";
                    $message_type = "success";
                } else {
                    $message = "Ralat: " . $stmt_upd->error;
                    $message_type = "danger";
                }
                $stmt_upd->close();
            }
        } else {
            $stmt_upd = $conn->prepare("UPDATE $table SET full_name = ?, username = ?, email = ? WHERE id = ?");
            $stmt_upd->bind_param("sssi", $full_name, $username, $email, $account_id);
            if ($stmt_upd->execute()) {
                $message = "Maklumat akaun berjaya dikemaskini!";
                $message_type = "success";
            } else {
                $message = "Ralat: " . $stmt_upd->error;
                $message_type = "danger";
            }
            $stmt_upd->close();
        }
    }
    $chk->close();
}

// 3. PADAM AKAUN (DELETE)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_delete_account'])) {
    $target_role = trim($_POST['target_role']);
    $account_id  = (int)$_POST['account_id'];

    if ($target_role === 'admin' && $account_id === (int)$current_admin_id) {
        $message = "Tindakan disekat: Anda tidak boleh memadamkan akaun anda sendiri yang sedang log masuk!";
        $message_type = "danger";
    } else {
        $table = ($target_role === 'admin') ? 'admins' : 'jhepp';
        $role_label = ($target_role === 'admin') ? 'Admin' : 'JHEPP';

        $stmt_del = $conn->prepare("DELETE FROM $table WHERE id = ?");
        $stmt_del->bind_param("i", $account_id);
        if ($stmt_del->execute()) {
            $message = "Akaun $role_label berjaya dipadam!";
            $message_type = "success";
        } else {
            $message = "Ralat memadam akaun: " . $stmt_del->error;
            $message_type = "danger";
        }
        $stmt_del->close();
    }
}

// AMBIL SENARAI DARI PANGKALAN DATA
$admins_res = $conn->query("SELECT id, username, email, full_name, created_at FROM admins ORDER BY id ASC");
$jhepp_res  = $conn->query("SELECT id, username, email, full_name, created_at FROM jhepp ORDER BY id ASC");

$total_admins = $admins_res->num_rows;
$total_jhepp  = $jhepp_res->num_rows;
$total_staff  = $total_admins + $total_jhepp;
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Urus Akaun Admin & JHEPP - Panel Admin SCRS PMU</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/neo-style.css">

    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 24px;
        }
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }
        .stat-card-staff {
            background: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-solid);
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .stat-card-staff .icon-box {
            width: 46px;
            height: 46px;
            border: var(--border-thin);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            box-shadow: var(--shadow-sm);
        }

        /* TAB NAVIGATION */
        .tab-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: var(--border-thick);
            padding-bottom: 12px;
            flex-wrap: wrap;
        }
        .tab-btn {
            background: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-md);
            padding: 10px 18px;
            font-weight: 900;
            font-size: 0.9rem;
            cursor: pointer;
            box-shadow: var(--shadow-solid);
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
        }
        .tab-btn.active {
            background: var(--yellow) !important;
            transform: translate(2px, 2px);
            box-shadow: var(--shadow-sm);
        }
        .tab-btn:hover:not(.active) {
            transform: translate(-2px, -2px);
            box-shadow: var(--shadow-lg);
        }

        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .table-card {
            background: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-solid);
            padding: 20px;
            margin-bottom: 30px;
        }

        /* ROLE BADGES - PURE TEXT & ICON */
        .role-pill-admin {
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            background: transparent !important;
            color: #b45309 !important;
            border: none !important;
            border-radius: 0 !important;
            font-weight: 800 !important;
            padding: 0 !important;
            font-size: 0.85rem !important;
            text-transform: uppercase !important;
            box-shadow: none !important;
            cursor: default !important;
            user-select: none !important;
            white-space: nowrap !important;
        }
        .role-pill-jhepp {
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            background: transparent !important;
            color: #1d4ed8 !important;
            border: none !important;
            border-radius: 0 !important;
            font-weight: 800 !important;
            padding: 0 !important;
            font-size: 0.85rem !important;
            text-transform: uppercase !important;
            box-shadow: none !important;
            cursor: default !important;
            user-select: none !important;
            white-space: nowrap !important;
        }
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
        }
        .badge-approved {
            color: #15803d !important;
            background: transparent !important;
        }

        /* ROLE SELECTION CARDS IN MODAL */
        .role-select-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .role-select-card {
            cursor: pointer;
            margin: 0;
            display: block;
        }
        .role-select-card input[type="radio"] {
            display: none;
        }
        .role-card-inner {
            border: var(--border-thin);
            border-radius: var(--radius-md);
            background: var(--white);
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            user-select: none;
            position: relative;
        }
        .role-select-card:hover .role-card-inner {
            transform: translate(-1.5px, -1.5px);
            box-shadow: var(--shadow-solid);
        }
        .role-card-icon {
            width: 38px;
            height: 38px;
            border: var(--border-thin);
            border-radius: var(--radius-sm);
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: var(--black);
            box-shadow: 2px 2px 0 var(--black);
            flex-shrink: 0;
        }
        .role-card-info {
            flex: 1;
            min-width: 0;
        }
        .role-card-title {
            font-weight: 900;
            font-size: 0.88rem;
            color: var(--black);
            text-transform: uppercase;
            line-height: 1.2;
        }
        .role-card-desc {
            font-weight: 700;
            font-size: 0.72rem;
            color: #555;
            margin-top: 2px;
        }
        .role-check-mark {
            font-size: 1.25rem;
            color: #d1d5db;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        /* Checked states */
        .role-select-card input[type="radio"]:checked + .role-card-inner.role-card-admin {
            background-color: var(--yellow);
            border: var(--border-thick);
            box-shadow: var(--shadow-solid);
            transform: translate(1px, 1px);
        }
        .role-select-card input[type="radio"]:checked + .role-card-inner.role-card-admin .role-check-mark {
            color: var(--black);
        }

        .role-select-card input[type="radio"]:checked + .role-card-inner.role-card-jhepp {
            background-color: var(--blue);
            border: var(--border-thick);
            box-shadow: var(--shadow-solid);
            transform: translate(1px, 1px);
        }
        .role-select-card input[type="radio"]:checked + .role-card-inner.role-card-jhepp .role-check-mark {
            color: var(--black);
        }

        @media (max-width: 576px) {
            .role-select-grid {
                grid-template-columns: 1fr;
            }
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
            <a href="admin_bookings.php" class="sidebar-link"><i class="bi bi-calendar-check-fill"></i> Urus Tempahan</a>
            <a href="admin_staff.php" class="sidebar-link active"><i class="bi bi-shield-shaded"></i> Urus Admin & JHEPP</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        
        <?php if (!empty($message)): ?>
            <div class="neo-alert alert-<?php echo $message_type; ?>">
                <i class="bi bi-<?php echo ($message_type === 'success') ? 'check-circle-fill' : 'exclamation-triangle-fill'; ?> me-2"></i>
                <div><?php echo $message; ?></div>
            </div>
        <?php endif; ?>

        <!-- PAGE HEADER -->
        <div class="page-header">
            <div>
                <div class="page-title-row">
                    <a href="admin_dashboard.php" class="neo-btn btn-yellow btn-arrow-back" title="Papan Pemuka" aria-label="Kembali ke Papan Pemuka">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <h1 style="font-size: 1.5rem; font-weight: 900; text-transform: uppercase; margin: 0; color: var(--black); display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-shield-lock-fill"></i> Pengurusan Akaun Admin & JHEPP
                    </h1>
                </div>
                <p style="font-weight: 700; color: #555; margin: 4px 0 0 0; font-size: 0.92rem;">
                    Daftar dan urus akses pentadbir sistem (Admin) dan pegawai Jabatan Hal Ehwal Pelajar (JHEPP).
                </p>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button type="button" class="neo-btn btn-green" onclick="openCreateModal()">
                    <i class="bi bi-person-plus-fill me-1"></i> Tambah Akaun Baharu
                </button>
            </div>
        </div>

        <!-- STATISTIK KAD -->
        <div class="stats-row">
            <div class="stat-card-staff">
                <div class="icon-box" style="background: var(--yellow);">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.5rem; font-weight: 900; margin: 0;"><?php echo $total_admins; ?></h3>
                    <span style="font-size: 0.78rem; font-weight: 800; text-transform: uppercase; color: #666;">Pentadbir (Admin)</span>
                </div>
            </div>
            <div class="stat-card-staff">
                <div class="icon-box" style="background: var(--blue);">
                    <i class="bi bi-patch-check-fill"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.5rem; font-weight: 900; margin: 0;"><?php echo $total_jhepp; ?></h3>
                    <span style="font-size: 0.78rem; font-weight: 800; text-transform: uppercase; color: #666;">Pegawai JHEPP</span>
                </div>
            </div>
            <div class="stat-card-staff">
                <div class="icon-box" style="background: var(--green);">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.5rem; font-weight: 900; margin: 0;"><?php echo $total_staff; ?></h3>
                    <span style="font-size: 0.78rem; font-weight: 800; text-transform: uppercase; color: #666;">Jumlah Keseluruhan Staf</span>
                </div>
            </div>
        </div>

        <!-- TAB NAVIGATION -->
        <div class="tab-bar">
            <button type="button" class="tab-btn active" onclick="switchTab('tab-admins', this)">
                <i class="bi bi-shield-lock-fill"></i> Akaun Admin (<?php echo $total_admins; ?>)
            </button>
            <button type="button" class="tab-btn" onclick="switchTab('tab-jhepp', this)">
                <i class="bi bi-patch-check-fill"></i> Akaun JHEPP (<?php echo $total_jhepp; ?>)
            </button>
        </div>

        <!-- TAB 1: SENARAI ADMIN -->
        <div class="tab-content active" id="tab-admins">
            <div class="table-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                    <h3 style="font-size: 1.1rem; font-weight: 900; text-transform: uppercase; margin: 0;">
                        <i class="bi bi-shield-shaded me-1"></i> Senarai Pentadbir Sistem (Admin)
                    </h3>
                    <button type="button" class="neo-btn btn-sm btn-yellow" onclick="openCreateModalRole('admin')">
                        <i class="bi bi-plus-circle-fill me-1"></i> Tambah Admin
                    </button>
                </div>

                <div class="neo-table-wrapper">
                    <table class="neo-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th>Nama Penuh</th>
                                <th>Nama Pengguna (Username)</th>
                                <th>Alamat E-mel</th>
                                <th style="width: 150px; text-align: center; white-space: nowrap;">Peranan</th>
                                <th>Tarikh Didaftar</th>
                                <th style="width: 140px; text-align: center;">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($admins_res->num_rows > 0): ?>
                                <?php while ($adm = $admins_res->fetch_assoc()): 
                                    $is_current_user = ($adm['id'] == $current_admin_id);
                                    $adm_json = htmlspecialchars(json_encode([
                                        'id' => $adm['id'],
                                        'full_name' => $adm['full_name'],
                                        'username' => $adm['username'],
                                        'email' => $adm['email'],
                                        'role' => 'admin'
                                    ]), ENT_QUOTES, 'UTF-8');
                                ?>
                                    <tr>
                                        <td><strong>#<?php echo $adm['id']; ?></strong></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($adm['full_name']); ?></strong>
                                            <?php if ($is_current_user): ?>
                                                <span class="badge-status badge-approved" style="font-size: 0.7rem; padding: 2px 8px; margin-left: 4px;"><i class="bi bi-person-check-fill"></i> Akaun Anda</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><code>@<?php echo htmlspecialchars($adm['username']); ?></code></td>
                                        <td><a href="mailto:<?php echo htmlspecialchars($adm['email']); ?>" style="color: var(--black); text-decoration: underline;"><?php echo htmlspecialchars($adm['email']); ?></a></td>
                                        <td style="text-align: center; white-space: nowrap;"><span class="role-pill-admin"><i class="bi bi-shield-shaded"></i> ADMIN</span></td>
                                        <td><small style="font-weight: 700; color: #555;"><?php echo date('d/m/Y, h:i A', strtotime($adm['created_at'])); ?></small></td>
                                        <td style="text-align: center;">
                                            <button type="button" class="neo-btn btn-sm btn-yellow" data-account="<?php echo $adm_json; ?>" onclick="openEditModal(this)" title="Kemaskini">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <?php if (!$is_current_user): ?>
                                                <button type="button" class="neo-btn btn-sm btn-pink" onclick="openDeleteModal('admin', <?php echo $adm['id']; ?>, '<?php echo htmlspecialchars($adm['username'], ENT_QUOTES); ?>')" title="Padam">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="neo-btn btn-sm" style="background: #e0e0e0; cursor: not-allowed;" title="Tidak boleh padam akaun sendiri" disabled>
                                                    <i class="bi bi-lock-fill"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 25px;">Tiada rekod admin.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 2: SENARAI JHEPP -->
        <div class="tab-content" id="tab-jhepp">
            <div class="table-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                    <h3 style="font-size: 1.1rem; font-weight: 900; text-transform: uppercase; margin: 0;">
                        <i class="bi bi-patch-check-fill me-1"></i> Senarai Pegawai JHEPP
                    </h3>
                    <button type="button" class="neo-btn btn-sm btn-blue" onclick="openCreateModalRole('jhepp')">
                        <i class="bi bi-plus-circle-fill me-1"></i> Tambah Pegawai JHEPP
                    </button>
                </div>

                <div class="neo-table-wrapper">
                    <table class="neo-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th>Nama Penuh</th>
                                <th>Nama Pengguna (Username)</th>
                                <th>Alamat E-mel</th>
                                <th style="width: 170px; text-align: center; white-space: nowrap;">Peranan</th>
                                <th>Tarikh Didaftar</th>
                                <th style="width: 140px; text-align: center;">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($jhepp_res->num_rows > 0): ?>
                                <?php while ($jhp = $jhepp_res->fetch_assoc()): 
                                    $jhp_json = htmlspecialchars(json_encode([
                                        'id' => $jhp['id'],
                                        'full_name' => $jhp['full_name'],
                                        'username' => $jhp['username'],
                                        'email' => $jhp['email'],
                                        'role' => 'jhepp'
                                    ]), ENT_QUOTES, 'UTF-8');
                                ?>
                                    <tr>
                                        <td><strong>#<?php echo $jhp['id']; ?></strong></td>
                                        <td><strong><?php echo htmlspecialchars($jhp['full_name']); ?></strong></td>
                                        <td><code>@<?php echo htmlspecialchars($jhp['username']); ?></code></td>
                                        <td><a href="mailto:<?php echo htmlspecialchars($jhp['email']); ?>" style="color: var(--black); text-decoration: underline;"><?php echo htmlspecialchars($jhp['email']); ?></a></td>
                                        <td style="text-align: center; white-space: nowrap;"><span class="role-pill-jhepp"><i class="bi bi-patch-check-fill"></i> PEGAWAI JHEPP</span></td>
                                        <td><small style="font-weight: 700; color: #555;"><?php echo date('d/m/Y, h:i A', strtotime($jhp['created_at'])); ?></small></td>
                                        <td style="text-align: center;">
                                            <button type="button" class="neo-btn btn-sm btn-yellow" data-account="<?php echo $jhp_json; ?>" onclick="openEditModal(this)" title="Kemaskini">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button type="button" class="neo-btn btn-sm btn-pink" onclick="openDeleteModal('jhepp', <?php echo $jhp['id']; ?>, '<?php echo htmlspecialchars($jhp['username'], ENT_QUOTES); ?>')" title="Padam">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 25px;">Tiada rekod pegawai JHEPP. Sila klik "Tambah Pegawai JHEPP" untuk menambah akaun baharu.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>

    <!-- MODAL 1: TAMBAH AKAUN BAHARU -->
    <div class="neo-modal-overlay" id="createModalOverlay" onclick="closeCreateModalOutside(event)">
        <div class="neo-modal" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="modal-title"><i class="bi bi-person-plus-fill me-1"></i> Tambah Akaun Baharu</h3>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action_create_account" value="1">
                
                <div style="margin-bottom: 16px;">
                    <label style="font-weight: 800; font-size: 0.85rem; text-transform: uppercase; display: block; margin-bottom: 8px;">
                        Pilih Peranan / Jenis Akaun:
                    </label>
                    <div class="role-select-grid">
                        <label class="role-select-card" for="createRoleAdmin">
                            <input type="radio" name="target_role" id="createRoleAdmin" value="admin" checked>
                            <div class="role-card-inner role-card-admin">
                                <div class="role-card-icon">
                                    <i class="bi bi-shield-shaded"></i>
                                </div>
                                <div class="role-card-info">
                                    <div class="role-card-title">Pentadbir (Admin)</div>
                                    <div class="role-card-desc">Akses penuh sistem</div>
                                </div>
                                <div class="role-check-mark">
                                    <i class="bi bi-check-circle-fill"></i>
                                </div>
                            </div>
                        </label>

                        <label class="role-select-card" for="createRoleJhepp">
                            <input type="radio" name="target_role" id="createRoleJhepp" value="jhepp">
                            <div class="role-card-inner role-card-jhepp">
                                <div class="role-card-icon">
                                    <i class="bi bi-patch-check-fill"></i>
                                </div>
                                <div class="role-card-info">
                                    <div class="role-card-title">Pegawai JHEPP</div>
                                    <div class="role-card-desc">Pengesahan pelajar</div>
                                </div>
                                <div class="role-check-mark">
                                    <i class="bi bi-check-circle-fill"></i>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="font-weight: 800; font-size: 0.85rem; text-transform: uppercase; display: block; margin-bottom: 4px;">Nama Penuh</label>
                    <input type="text" name="full_name" class="form-control" placeholder="Contoh: Muhammad Ali bin Ismail" required>
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="font-weight: 800; font-size: 0.85rem; text-transform: uppercase; display: block; margin-bottom: 4px;">Nama Pengguna (Username)</label>
                    <input type="text" name="username" class="form-control" placeholder="Contoh: ali_pmu atau jhepp_ali" required>
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="font-weight: 800; font-size: 0.85rem; text-transform: uppercase; display: block; margin-bottom: 4px;">Alamat E-mel Rasmi</label>
                    <input type="email" name="email" class="form-control" placeholder="Contoh: ali@pmu.edu.my" required>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 800; font-size: 0.85rem; text-transform: uppercase; display: block; margin-bottom: 4px;">Kata Laluan Sementara</label>
                    <input type="password" name="password" class="form-control" placeholder="Minimum 6 aksara" minlength="6" required>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; border-top: 2px dashed #ccc; padding-top: 15px;">
                    <button type="button" class="neo-btn" style="background: #ccc;" onclick="closeCreateModal()">Batal</button>
                    <button type="submit" class="neo-btn btn-green"><i class="bi bi-check-circle-fill me-1"></i> Simpan Akaun</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: KEMASKINI MAKLUMAT AKAUN -->
    <div class="neo-modal-overlay" id="editModalOverlay" onclick="closeEditModalOutside(event)">
        <div class="neo-modal" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="modal-title"><i class="bi bi-pencil-square me-1"></i> Kemaskini Maklumat Akaun</h3>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action_update_account" value="1">
                <input type="hidden" name="target_role" id="editRole" value="">
                <input type="hidden" name="account_id" id="editAccountId" value="">

                <div style="margin-bottom: 12px;">
                    <span id="editRoleBadge" class="role-pill-admin"></span>
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="font-weight: 800; font-size: 0.85rem; text-transform: uppercase; display: block; margin-bottom: 4px;">Nama Penuh</label>
                    <input type="text" name="full_name" id="editFullName" class="form-control" required>
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="font-weight: 800; font-size: 0.85rem; text-transform: uppercase; display: block; margin-bottom: 4px;">Nama Pengguna (Username)</label>
                    <input type="text" name="username" id="editUsername" class="form-control" required>
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="font-weight: 800; font-size: 0.85rem; text-transform: uppercase; display: block; margin-bottom: 4px;">Alamat E-mel</label>
                    <input type="email" name="email" id="editEmail" class="form-control" required>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 800; font-size: 0.85rem; text-transform: uppercase; display: block; margin-bottom: 4px;">
                        Tukar Kata Laluan (Pilihan)
                    </label>
                    <input type="password" name="new_password" class="form-control" placeholder="Biarkan kosong jika tidak mahu tukar">
                    <small style="font-weight: 700; color: #666;">Isi hanya jika ingin menetapkan kata laluan baharu (min 6 aksara).</small>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; border-top: 2px dashed #ccc; padding-top: 15px;">
                    <button type="button" class="neo-btn" style="background: #ccc;" onclick="closeEditModal()">Batal</button>
                    <button type="submit" class="neo-btn btn-yellow"><i class="bi bi-save-fill me-1"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: PENGESAHAN PADAM -->
    <div class="neo-modal-overlay" id="deleteModalOverlay" onclick="closeDeleteModalOutside(event)">
        <div class="neo-modal" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="modal-title" style="color: #c62828;"><i class="bi bi-trash3-fill me-1"></i> Padam Akaun Staf</h3>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action_delete_account" value="1">
                <input type="hidden" name="target_role" id="delRole" value="">
                <input type="hidden" name="account_id" id="delAccountId" value="">

                <p style="font-weight: 700; font-size: 0.95rem; line-height: 1.5; margin-bottom: 20px;">
                    Adakah anda pasti ingin memadamkan akaun <span id="delRoleLabel" style="font-weight: 900;"></span> untuk pengguna <strong id="delUsername" style="color: #c62828;"></strong>?
                    <br><small style="color: #666;">Tindakan ini adalah kekal dan tidak boleh diundur semula.</small>
                </p>

                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="neo-btn" style="background: #ccc;" onclick="closeDeleteModal()">Batal</button>
                    <button type="submit" class="neo-btn btn-pink"><i class="bi bi-trash-fill me-1"></i> Padam Sekarang</button>
                </div>
            </form>
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        &copy; <?php echo date("Y"); ?> SCRS PMU. SISTEM SEWAAN KERETA.
    </footer>

    <!-- SKRIP JAVASCRIPT -->
    <script>
        // Tab switching
        function switchTab(tabId, btn) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            btn.classList.add('active');
        }

        // Profile menu
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

        // CREATE MODAL
        function openCreateModal() {
            document.getElementById('createModalOverlay').classList.add('show');
        }
        function openCreateModalRole(role) {
            if (role === 'admin') {
                document.getElementById('createRoleAdmin').checked = true;
            } else {
                document.getElementById('createRoleJhepp').checked = true;
            }
            openCreateModal();
        }
        function closeCreateModal() {
            document.getElementById('createModalOverlay').classList.remove('show');
        }
        function closeCreateModalOutside(e) {
            if (e.target.id === 'createModalOverlay') closeCreateModal();
        }

        // EDIT MODAL
        function openEditModal(btn) {
            const data = JSON.parse(btn.getAttribute('data-account'));
            document.getElementById('editRole').value = data.role;
            document.getElementById('editAccountId').value = data.id;
            document.getElementById('editFullName').value = data.full_name;
            document.getElementById('editUsername').value = data.username;
            document.getElementById('editEmail').value = data.email;

            const badge = document.getElementById('editRoleBadge');
            if (data.role === 'admin') {
                badge.className = 'role-pill-admin';
                badge.innerHTML = '<i class="bi bi-shield-shaded"></i> PENTADBIR (ADMIN)';
            } else {
                badge.className = 'role-pill-jhepp';
                badge.innerHTML = '<i class="bi bi-patch-check-fill"></i> PEGAWAI JHEPP';
            }

            document.getElementById('editModalOverlay').classList.add('show');
        }
        function closeEditModal() {
            document.getElementById('editModalOverlay').classList.remove('show');
        }
        function closeEditModalOutside(e) {
            if (e.target.id === 'editModalOverlay') closeEditModal();
        }

        // DELETE MODAL
        function openDeleteModal(role, id, username) {
            document.getElementById('delRole').value = role;
            document.getElementById('delAccountId').value = id;
            document.getElementById('delUsername').textContent = '@' + username;
            document.getElementById('delRoleLabel').textContent = (role === 'admin') ? 'Pentadbir (Admin)' : 'Pegawai JHEPP';
            document.getElementById('deleteModalOverlay').classList.add('show');
        }
        function closeDeleteModal() {
            document.getElementById('deleteModalOverlay').classList.remove('show');
        }
        function closeDeleteModalOutside(e) {
            if (e.target.id === 'deleteModalOverlay') closeDeleteModal();
        }
    </script>
</body>
</html>
