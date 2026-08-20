<?php
session_start();
require 'db.php';

// Semak jika pengguna telah log masuk dan merupakan pegawai JHEPP
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'jhepp') {
    header("Location: index.php");
    exit();
}

$jhepp_username = $_SESSION['username'] ?? 'JHEPP';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

$message = "";

// PROSES BUTANG APPROVE ATAU REJECT PELAJAR
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = (int)$_POST['user_id'];
    $action = $_POST['action']; // 'approve' atau 'reject'
    
    $new_status = ($action === 'approve') ? 'approved' : 'rejected';
    
    $sql_update = "UPDATE students SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("si", $new_status, $user_id);
    
    if ($stmt->execute()) {
        $alert_type = ($action === 'approve') ? 'alert-success' : 'alert-danger';
        $alert_text = ($action === 'approve') ? 'diluluskan' : 'ditolak';
        
        $message = "<div class='neo-alert {$alert_type} mb-3'><i class='bi bi-check-circle-fill me-2'></i>Berjaya: Akaun Pelajar telah {$alert_text}!</div>";

        // ==========================================
        // FUNGSI HANTAR E-MEL MENGGUNAKAN PHPMAILER
        // ==========================================
        $sql_email = "SELECT full_name, email FROM students WHERE id = ?";
        $stmt_email = $conn->prepare($sql_email);
        $stmt_email->bind_param("i", $user_id);
        $stmt_email->execute();
        $res_email = $stmt_email->get_result();

        if ($res_email->num_rows > 0) {
            $user_data = $res_email->fetch_assoc();
            $to_email = $user_data['email'];
            $user_name = $user_data['full_name'];

            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'chickenmasterz26@gmail.com';
                $mail->Password   = 'pcccoszzikvwmzsd';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('admin.jhepp@gmail.com', 'Pegawai JHEPP PMU');
                $mail->addAddress($to_email, $user_name);

                $mail->isHTML(true);
                $mail->Subject = ($action === 'approve') ? "SCRS PMU - Akaun Diluluskan!" : "SCRS PMU - Akaun Ditolak";
                
                if ($action === 'approve') {
                    $mail->Body = "Salam <b>$user_name</b>,<br><br>Tahniah! Pendaftaran akaun anda di SCRS PMU telah <b>DILULUSKAN</b> oleh pihak JHEPP.<br>Anda kini boleh log masuk ke dalam sistem.<br><br>Terima kasih,<br>Pegawai JHEPP PMU";
                } else {
                    $mail->Body = "Salam <b>$user_name</b>,<br><br>Dukacita dimaklumkan bahawa pendaftaran akaun anda di SCRS PMU telah <b>DITOLAK</b> oleh pihak JHEPP. Sila rujuk pihak pengurusan untuk maklumat lanjut.<br><br>Terima kasih,<br>Pegawai JHEPP PMU";
                }

                $mail->send();
                $message .= "<div class='neo-alert alert-success mt-2'><i class='bi bi-envelope-check-fill me-2'></i>Notifikasi e-mel berjaya dihantar ke <strong>{$to_email}</strong>.</div>";
            } catch (Exception $e) {
                $message .= "<div class='neo-alert alert-warning mt-2'><i class='bi bi-exclamation-triangle-fill me-2'></i>Akaun dikemaskini, tetapi e-mel gagal dihantar. Ralat: {$mail->ErrorInfo}</div>";
            }
        }
        $stmt_email->close();

    } else {
        $message = "<div class='neo-alert alert-danger'>Ralat pangkalan data: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// TAB FILTER
$tab = $_GET['tab'] ?? 'pending';

// KIRAAN STATISTIK TAB
$count_pending = $conn->query("SELECT COUNT(*) AS total FROM students WHERE status = 'pending' AND email_verified = 1")->fetch_assoc()['total'] ?? 0;
$count_approved = $conn->query("SELECT COUNT(*) AS total FROM students WHERE status = 'approved'")->fetch_assoc()['total'] ?? 0;
$count_rejected = $conn->query("SELECT COUNT(*) AS total FROM students WHERE status = 'rejected'")->fetch_assoc()['total'] ?? 0;
$count_all = $conn->query("SELECT COUNT(*) AS total FROM students")->fetch_assoc()['total'] ?? 0;

// QUERY MENGIKUT TAB
if ($tab === 'approved') {
    $sql_students = "SELECT * FROM students WHERE status = 'approved' ORDER BY id DESC";
    $tab_title = "Senarai Pelajar Telah Diluluskan";
    $tab_icon = "bi-check-circle-fill text-success";
} elseif ($tab === 'rejected') {
    $sql_students = "SELECT * FROM students WHERE status = 'rejected' ORDER BY id DESC";
    $tab_title = "Senarai Permohonan Pelajar Ditolak";
    $tab_icon = "bi-x-circle-fill text-danger";
} elseif ($tab === 'all') {
    $sql_students = "SELECT * FROM students ORDER BY id DESC";
    $tab_title = "Semua Rekod Pelajar Berdaftar";
    $tab_icon = "bi-people-fill text-primary";
} else {
    $tab = 'pending';
    $sql_students = "SELECT * FROM students WHERE status = 'pending' AND email_verified = 1 ORDER BY id DESC";
    $tab_title = "Permohonan Pelajar Menunggu Kelulusan";
    $tab_icon = "bi-hourglass-split text-warning";
}
$result_students = $conn->query($sql_students);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pengesahan Dokumen Pelajar - JHEPP PMU</title>
    
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
        .menu-toggle-btn { font-size: 2rem; color: var(--black); transition: var(--transition); border: none; background: none; cursor: pointer; }
        .menu-toggle-btn:hover { transform: scale(1.1); }
        .neo-brand { font-size: 1.5rem; font-weight: 900; letter-spacing: 2px; text-transform: uppercase; }

        /* PROFILE DROPDOWN */
        .nav-right-actions { display: flex; align-items: center; gap: 12px; }
        .profile-container { position: relative; }
        .profile-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background-color: var(--yellow);
            border: 3px solid var(--black);
            padding: 8px 14px;
            font-weight: 800;
            box-shadow: 4px 4px 0px var(--black);
            cursor: pointer;
            transition: var(--transition);
        }
        .profile-btn:hover { transform: translate(-2px, -2px); box-shadow: 6px 6px 0px var(--black); }
        .dropdown-menu {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            background: var(--white);
            border: 3px solid var(--black);
            box-shadow: 6px 6px 0px var(--black);
            width: 170px;
            display: none;
            z-index: 1001;
            list-style: none;
        }
        .dropdown-menu.show { display: block; }
        .dropdown-item {
            display: flex;
            align-items: center;
            padding: 10px 14px;
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--black);
            text-decoration: none;
            transition: var(--transition);
        }
        .dropdown-item:hover { background-color: var(--pink); color: var(--black); }

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
        .sidebar-link.logout-link:hover { background-color: var(--pink); }

        /* MAIN CONTENT */
        .main-content {
            flex: 1;
            padding: 2rem 20px;
            max-width: 900px;
            margin: 0 auto;
            width: 100%;
        }

        /* TABS */
        .tabs-nav {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        .tab-btn {
            background: var(--white);
            border: 3px solid var(--black);
            box-shadow: 4px 4px 0px var(--black);
            padding: 10px 16px;
            font-weight: 900;
            font-size: 0.85rem;
            text-transform: uppercase;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }
        .tab-btn:hover { transform: translate(-2px, -2px); box-shadow: 6px 6px 0px var(--black); }
        .tab-btn.active {
            background: var(--yellow);
            transform: translate(2px, 2px);
            box-shadow: 2px 2px 0px var(--black);
        }
        .tab-badge {
            background: var(--black);
            color: var(--white);
            padding: 2px 6px;
            font-size: 0.75rem;
            border-radius: 3px;
        }

        .sub-header {
            font-size: 1.2rem; font-weight: 900; text-transform: uppercase;
            border-bottom: 3px solid var(--black); padding-bottom: 8px; margin-bottom: 20px; margin-top: 15px;
            display: flex; align-items: center; gap: 10px;
        }

        .user-card {
            background-color: var(--white);
            border: var(--border-thick);
            box-shadow: var(--shadow-solid);
            padding: 25px;
            margin-bottom: 25px;
            transition: var(--transition);
        }
        .user-card:hover {
            box-shadow: 8px 8px 0px var(--black);
        }

        .user-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-weight: 700;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }
        .user-info div { display: flex; justify-content: space-between; border-bottom: 2px dashed #eee; padding-bottom: 4px; }
        .user-info label { text-transform: uppercase; color: #666; font-size: 0.8rem; font-weight: 800; }

        /* SUPPORTING DOCS BUTTONS */
        .doc-title { font-weight: 900; text-transform: uppercase; margin-bottom: 8px; font-size: 0.85rem; }
        .doc-buttons-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }
        .doc-btn {
            background: var(--bg-color);
            border: 3px solid var(--black);
            box-shadow: 3px 3px 0px var(--black);
            padding: 10px;
            font-weight: 800;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: var(--transition);
        }
        .doc-btn:hover { background: var(--yellow); transform: translate(-2px, -2px); box-shadow: 5px 5px 0px var(--black); }

        .neo-btn {
            background-color: var(--yellow); color: var(--black);
            border: 3px solid var(--black); box-shadow: 4px 4px 0px var(--black);
            padding: 10px 16px; font-weight: 900; text-transform: uppercase;
            cursor: pointer; transition: var(--transition); display: inline-flex;
            align-items: center; justify-content: center; gap: 8px;
        }
        .neo-btn:hover { transform: translate(-2px, -2px); box-shadow: 6px 6px 0px var(--black); }
        .neo-btn:active { transform: translate(2px, 2px); box-shadow: 2px 2px 0px var(--black); }
        .btn-approve { background-color: var(--green); }
        .btn-reject { background-color: var(--pink); }

        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
        }

        .neo-alert {
            border: var(--border-thick);
            box-shadow: var(--shadow-solid);
            padding: 15px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .alert-success { background-color: var(--green); }
        .alert-danger { background-color: var(--pink); }
        .alert-warning { background-color: var(--yellow); }

        footer {
            background: var(--black);
            color: var(--white);
            border-top: var(--border-thick);
            padding: 15px;
            text-align: center;
            font-weight: 900;
            text-transform: uppercase;
            margin-top: auto;
        }

        @media (max-width: 600px) {
            .doc-buttons-grid { grid-template-columns: 1fr; }
            .action-buttons { grid-template-columns: 1fr; }
            .user-info div { flex-direction: column; gap: 2px; }
            .main-content { padding: 1rem 10px; }
            .neo-brand { font-size: 1.2rem; }
            .user-card { padding: 15px; }
            .tabs-nav { flex-direction: column; }
            .tab-btn { width: 100%; justify-content: space-between; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <header class="neo-navbar">
        <div class="neo-nav-left">
            <button class="menu-toggle-btn" id="open-sidebar"><i class="bi bi-list"></i></button>
            <a href="jhepp_dashboard.php" class="neo-brand">SCRS PMU (JHEPP)</a>
        </div>
        <div class="nav-right-actions">
            <div class="profile-container">
                <button class="profile-btn" id="profile-toggle">
                    <i class="bi bi-person-fill fs-5"></i>
                    <span><?php echo htmlspecialchars($jhepp_username); ?></span>
                </button>
                <ul class="dropdown-menu" id="profile-menu">
                    <li><a href="edit_profile.php" class="dropdown-item"><i class="bi bi-gear-fill me-2"></i> Edit Profil</a></li>
                    <li><a href="logout.php" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i> Log Keluar</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- SIDEBAR -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Portal JHEPP</h2>
            <button class="close-btn" id="close-sidebar"><i class="bi bi-x-lg"></i></button>
        </div>
        <nav class="sidebar-nav">
            <a href="jhepp_dashboard.php" class="sidebar-link"><i class="bi bi-speedometer2"></i> Papan Pemuka</a>
            <a href="verify_account.php" class="sidebar-link active"><i class="bi bi-shield-check"></i> Pengesahan Pelajar</a>
            <a href="edit_profile.php" class="sidebar-link"><i class="bi bi-person-gear"></i> Edit Profil</a>
            <a href="logout.php" class="sidebar-link logout-link"><i class="bi bi-box-arrow-right"></i> Log Keluar</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- HEADING -->
        <div style="margin-bottom: 20px;">
            <h1 style="font-size: 1.6rem; font-weight: 900; text-transform: uppercase; margin-bottom: 6px; color: var(--black); display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-shield-check text-dark"></i> Pengesahan & Dokumen Pelajar (JHEPP)
            </h1>
            <p style="font-weight: 700; color: #555; font-size: 0.95rem; margin: 0; line-height: 1.5;">
                Semak maklumat dan teliti dokumen sokongan pelajar (Kad Pelajar & Lesen Memandu) untuk permohonan yang sedang pending, diluluskan, atau ditolak.
            </p>
        </div>

        <?php echo $message; ?>

        <!-- TABS NAV -->
        <div class="tabs-nav">
            <a href="verify_account.php?tab=pending" class="tab-btn <?php echo ($tab === 'pending') ? 'active' : ''; ?>">
                <i class="bi bi-hourglass-split"></i> Menunggu Semakan
                <span class="tab-badge" style="background: var(--pink);"><?php echo $count_pending; ?></span>
            </a>
            <a href="verify_account.php?tab=approved" class="tab-btn <?php echo ($tab === 'approved') ? 'active' : ''; ?>">
                <i class="bi bi-check-circle-fill"></i> Diluluskan
                <span class="tab-badge"><?php echo $count_approved; ?></span>
            </a>
            <a href="verify_account.php?tab=rejected" class="tab-btn <?php echo ($tab === 'rejected') ? 'active' : ''; ?>">
                <i class="bi bi-x-circle-fill"></i> Ditolak
                <span class="tab-badge"><?php echo $count_rejected; ?></span>
            </a>
            <a href="verify_account.php?tab=all" class="tab-btn <?php echo ($tab === 'all') ? 'active' : ''; ?>">
                <i class="bi bi-people-fill"></i> Semua Rekod
                <span class="tab-badge"><?php echo $count_all; ?></span>
            </a>
        </div>

        <!-- SENARAI PELAJAR -->
        <div class="sub-header">
            <i class="bi <?php echo $tab_icon; ?>"></i> <?php echo $tab_title; ?> (<?php echo ($result_students ? $result_students->num_rows : 0); ?> Rekod)
        </div>

        <?php if ($result_students && $result_students->num_rows > 0): ?>
            <?php while ($row = $result_students->fetch_assoc()): ?>
                <?php 
                    $card_border = "var(--blue)";
                    $status_label = "Pending";
                    $status_bg = "var(--yellow)";
                    if ($row['status'] === 'approved') {
                        $card_border = "var(--green)";
                        $status_label = "Diluluskan";
                        $status_bg = "var(--green)";
                    } elseif ($row['status'] === 'rejected') {
                        $card_border = "var(--pink)";
                        $status_label = "Ditolak";
                        $status_bg = "var(--pink)";
                    }
                ?>
                <div class="user-card" style="border-left: 8px solid <?php echo $card_border; ?>;">
                    <div style="margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                            <span style="background: <?php echo $status_bg; ?>; color: var(--black); border: 2px solid var(--black); font-weight: 900; font-size: 0.75rem; text-transform: uppercase; padding: 2px 8px; box-shadow: 2px 2px 0px var(--black);">
                                Status JHEPP: <?php echo $status_label; ?>
                            </span>
                            <?php if ((int)$row['email_verified'] === 1): ?>
                                <span style="background: var(--green); color: var(--black); border: 2px solid var(--black); font-weight: 900; font-size: 0.75rem; text-transform: uppercase; padding: 2px 8px; box-shadow: 2px 2px 0px var(--black);">
                                    <i class="bi bi-check-all"></i> E-mel Sah
                                </span>
                            <?php else: ?>
                                <span style="background: #ddd; color: #333; border: 2px solid var(--black); font-weight: 900; font-size: 0.75rem; text-transform: uppercase; padding: 2px 8px; box-shadow: 2px 2px 0px var(--black);">
                                    <i class="bi bi-hourglass"></i> E-mel Belum Sah
                                </span>
                            <?php endif; ?>
                        </div>
                        <span style="font-size: 0.8rem; font-weight: 700; color: #555;">
                            Daftar: <?php echo date('d/m/Y h:i A', strtotime($row['created_at'])); ?>
                        </span>
                    </div>

                    <div class="user-info">
                        <div><label>Nama Penuh</label><span><?php echo htmlspecialchars($row['full_name']); ?></span></div>
                        <div><label>No. Matrik / Pendaftaran</label><span><?php echo htmlspecialchars($row['no_pendaftaran']); ?></span></div>
                        <div><label>No. Telefon</label><span><?php echo htmlspecialchars($row['phone_no']); ?></span></div>
                        <div><label>No. IC</label><span><?php echo htmlspecialchars($row['no_ic']); ?></span></div>
                        <div><label>E-mel</label><span><?php echo htmlspecialchars($row['email']); ?></span></div>
                    </div>

                    <div class="doc-title"><i class="bi bi-file-earmark-medical me-1"></i> Dokumen Sokongan Pelajar:</div>
                    <div class="doc-buttons-grid">
                        <?php if (!empty($row['student_id_file'])): ?>
                            <a href="<?php echo htmlspecialchars($row['student_id_file']); ?>" target="_blank" class="doc-btn">
                                <i class="bi bi-card-heading fs-5"></i> Lihat Kad Pelajar (ID)
                            </a>
                        <?php else: ?>
                            <span class="doc-btn" style="opacity: 0.5; cursor: not-allowed;"><i class="bi bi-x-circle"></i> Tiada Kad Pelajar</span>
                        <?php endif; ?>

                        <?php if (!empty($row['driving_license_file'])): ?>
                            <a href="<?php echo htmlspecialchars($row['driving_license_file']); ?>" target="_blank" class="doc-btn">
                                <i class="bi bi-card-checklist fs-5"></i> Lihat Lesen Memandu
                            </a>
                        <?php else: ?>
                            <span class="doc-btn" style="opacity: 0.5; cursor: not-allowed;"><i class="bi bi-x-circle"></i> Tiada Lesen</span>
                        <?php endif; ?>
                    </div>

                    <form action="" method="POST">
                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                        
                        <?php if ($row['status'] === 'pending'): ?>
                            <div class="action-buttons">
                                <button type="submit" name="action" value="reject" class="neo-btn btn-reject" onclick="return confirm('Tolak pendaftaran pelajar ini?');">
                                    <i class="bi bi-x-circle-fill me-1"></i> Tolak (Reject)
                                </button>
                                <button type="submit" name="action" value="approve" class="neo-btn btn-approve" onclick="return confirm('Luluskan pendaftaran pelajar ini?');">
                                    <i class="bi bi-check-circle-fill me-1"></i> Luluskan (Approve)
                                </button>
                            </div>
                        <?php elseif ($row['status'] === 'approved'): ?>
                            <div style="display: flex; justify-content: flex-end; margin-top: 10px;">
                                <button type="submit" name="action" value="reject" class="neo-btn btn-reject" style="padding: 6px 12px; font-size: 0.8rem;" onclick="return confirm('Tukar status pelajar ini kepada Ditolak (Rejected)?');">
                                    <i class="bi bi-x-circle me-1"></i> Batalkan Kelulusan (Tolak)
                                </button>
                            </div>
                        <?php elseif ($row['status'] === 'rejected'): ?>
                            <div style="display: flex; justify-content: flex-end; margin-top: 10px;">
                                <button type="submit" name="action" value="approve" class="neo-btn btn-approve" style="padding: 6px 12px; font-size: 0.8rem;" onclick="return confirm('Tukar status pelajar ini kepada Diluluskan (Approved)?');">
                                    <i class="bi bi-check-circle me-1"></i> Luluskan Semula Akaun Ini
                                </button>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="background: var(--white); border: 3px dashed var(--black); padding: 2.5rem; text-align: center; font-weight: 700; color: #666; margin-bottom: 30px; box-shadow: var(--shadow-solid);">
                <i class="bi bi-folder-x text-muted" style="font-size: 2.5rem; display: block; margin-bottom: 10px;"></i>
                Tiada rekod pelajar dalam kategori ini pada masa ini.
            </div>
        <?php endif; ?>

    </main>

    <!-- FOOTER -->
    <footer>
        &copy; <?php echo date("Y"); ?> SCRS PMU. SISTEM SEWAAN KERETA.
    </footer>

    <!-- SKRIP ASLI (VANILLA JS) -->
    <script>
        const openSidebarBtn = document.getElementById('open-sidebar');
        const closeSidebarBtn = document.getElementById('close-sidebar');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        const profileToggle = document.getElementById('profile-toggle');
        const profileMenu = document.getElementById('profile-menu');

        function openSidebar() {
            sidebar.classList.add('open');
            sidebarOverlay.classList.add('show');
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('show');
        }

        if (openSidebarBtn) openSidebarBtn.addEventListener('click', openSidebar);
        if (closeSidebarBtn) closeSidebarBtn.addEventListener('click', closeSidebar);
        if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

        if (profileToggle && profileMenu) {
            profileToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                profileMenu.classList.toggle('show');
            });
            document.addEventListener('click', () => {
                profileMenu.classList.remove('show');
            });
        }
    </script>
</body>
</html>