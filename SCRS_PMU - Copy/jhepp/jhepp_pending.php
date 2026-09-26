<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/notifications.php';

// Semak jika pengguna telah log masuk dan merupakan pegawai JHEPP
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'jhepp') {
    header("Location: ../index.php");
    exit();
}

$jhepp_id = $_SESSION['jhepp_id'] ?? 0;
$jhepp_username = $_SESSION['username'] ?? 'JHEPP';
$jhepp_fullname = $_SESSION['full_name'] ?? $jhepp_username;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/Exception.php';
require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';

$message = "";

// PROSES TINDAKAN LULUS ATAU TOLAK PELAJAR
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_verify'])) {
    $user_id = (int)$_POST['user_id'];
    $action = $_POST['action']; // 'approve' atau 'reject'
    
    $new_status = ($action === 'approve') ? 'approved' : 'rejected';
    
    $sql_update = "UPDATE students SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("si", $new_status, $user_id);
    
    if ($stmt->execute()) {
        $alert_type = ($action === 'approve') ? 'alert-success' : 'alert-danger';
        $alert_text = ($action === 'approve') ? 'DILULUSKAN' : 'DITOLAK';
        
        $message = "<div class='neo-alert {$alert_type} mb-3'><i class='bi bi-check-circle-fill me-2'></i>Berjaya: Akaun Pelajar telah <strong>{$alert_text}</strong>!</div>";

        if ($action === 'approve') {
            create_notification(
                $conn,
                'student',
                $user_id,
                'Akaun Anda Diluluskan!',
                'Tahniah! Pendaftaran akaun anda di SCRS PMU telah diluluskan oleh JHEPP. Anda kini boleh menyewa kereta.',
                'student/dashboard.php',
                'success'
            );
        } else {
            create_notification(
                $conn,
                'student',
                $user_id,
                'Pendaftaran Ditolak',
                'Harap maaf, permohonan pendaftaran akaun anda telah ditolak oleh pihak JHEPP.',
                '',
                'danger'
            );
        }

        // HANTAR E-MEL NOTIFIKASI
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
                $mail->Subject = ($action === 'approve') ? "SCRS PMU - Akaun Pelajar Diluluskan!" : "SCRS PMU - Akaun Pelajar Ditolak";
                
                if ($action === 'approve') {
                    $mail->Body = "Salam <b>$user_name</b>,<br><br>Tahniah! Pendaftaran akaun anda di sistem SCRS PMU telah <b>DILULUSKAN</b> oleh pihak JHEPP.<br>Anda kini boleh log masuk ke dalam sistem dan membuat tempahan kereta sewa.<br><br>Terima kasih,<br>Pegawai JHEPP PMU";
                } else {
                    $mail->Body = "Salam <b>$user_name</b>,<br><br>Dukacita dimaklumkan bahawa pendaftaran akaun anda di sistem SCRS PMU telah <b>DITOLAK</b> oleh pihak JHEPP. Sila semak ketepatan dokumen yang dimuat naik atau rujuk pihak pengurusan JHEPP.<br><br>Terima kasih,<br>Pegawai JHEPP PMU";
                }

                $mail->send();
                $message .= "<div class='neo-alert alert-success mt-2'><i class='bi bi-envelope-check-fill me-2'></i>Notifikasi e-mel berjaya dihantar ke <strong>{$to_email}</strong>.</div>";
            } catch (Exception $e) {
                $message .= "<div class='neo-alert alert-warning mt-2'><i class='bi bi-exclamation-triangle-fill me-2'></i>Status dikemaskini, tetapi e-mel gagal dihantar. Ralat: {$mail->ErrorInfo}</div>";
            }
        }
        $stmt_email->close();
    } else {
        $message = "<div class='neo-alert alert-danger'>Ralat pangkalan data: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// CARIAN
$search = trim($_GET['search'] ?? '');

// KIRAAN STATISTIK
$count_pending = $conn->query("SELECT COUNT(*) AS total FROM students WHERE status = 'pending' AND email_verified = 1")->fetch_assoc()['total'] ?? 0;
$count_approved = $conn->query("SELECT COUNT(*) AS total FROM students WHERE status = 'approved'")->fetch_assoc()['total'] ?? 0;
$count_rejected = $conn->query("SELECT COUNT(*) AS total FROM students WHERE status = 'rejected'")->fetch_assoc()['total'] ?? 0;
$count_all = $conn->query("SELECT COUNT(*) AS total FROM students")->fetch_assoc()['total'] ?? 0;

// QUERY MENUNGGU KELULUSAN
$where_clauses = ["status = 'pending'", "email_verified = 1"];
$params = [];
$types = "";

if (!empty($search)) {
    $where_clauses[] = "(full_name LIKE ? OR username LIKE ? OR no_pendaftaran LIKE ? OR no_ic LIKE ? OR email LIKE ? OR phone_no LIKE ?)";
    $search_param = "%{$search}%";
    for ($i = 0; $i < 6; $i++) {
        $params[] = $search_param;
        $types .= "s";
    }
}

$sql_pending = "SELECT * FROM students WHERE " . implode(" AND ", $where_clauses) . " ORDER BY created_at DESC, id DESC";

if (!empty($params)) {
    $stmt_p = $conn->prepare($sql_pending);
    $stmt_p->bind_param($types, ...$params);
    $stmt_p->execute();
    $result_students = $stmt_p->get_result();
} else {
    $result_students = $conn->query($sql_pending);
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Menunggu Kelulusan Pelajar - Portal JHEPP</title>
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Master Neo-Brutalism CSS -->
    <link rel="stylesheet" href="../assets/css/neo-style.css">

    <style>
        .page-header-area {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
        }

        .page-title {
            font-size: 1.55rem;
            font-weight: 900;
            text-transform: uppercase;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .page-subtitle {
            font-weight: 700;
            color: #555;
            font-size: 0.95rem;
            margin: 0;
            line-height: 1.4;
        }

        /* TABS SEPARATE PAGES */
        .tabs-nav {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .tab-btn {
            background: var(--white);
            border: var(--border-thin);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            padding: 9px 15px;
            font-weight: 900;
            font-size: 0.85rem;
            text-transform: uppercase;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            text-decoration: none;
            color: var(--black);
        }
        .tab-btn:hover {
            transform: translate(-2px, -2px);
            box-shadow: var(--shadow-solid);
        }
        .tab-btn.active {
            background: var(--yellow);
            transform: translate(1px, 1px);
            box-shadow: var(--shadow-sm);
            border-color: var(--black);
        }
        .tab-badge {
            background: var(--black);
            color: var(--white);
            padding: 2px 8px;
            font-size: 0.75rem;
            border-radius: var(--radius-full);
            font-weight: 900;
        }

        /* SEARCH CARD */
        .filter-card {
            background: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-solid);
            padding: 16px;
            margin-bottom: 20px;
        }
        .search-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .search-input-wrapper {
            flex: 1;
            min-width: 260px;
            position: relative;
        }
        .search-input-wrapper input {
            width: 100%;
            padding: 10px 14px 10px 38px;
            font-weight: 700;
            font-size: 0.9rem;
            border: var(--border-thick);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            box-sizing: border-box;
        }
        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.1rem;
            color: #666;
        }

        /* TABLE CARD */
        .table-card {
            background: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-solid);
            overflow: hidden;
            margin-bottom: 30px;
        }
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.9rem;
        }
        th {
            background: var(--yellow);
            color: var(--black);
            font-weight: 900;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            padding: 14px 12px;
            border-bottom: var(--border-thick);
            white-space: nowrap;
        }
        td {
            padding: 12px;
            border-bottom: 2px solid #eee;
            vertical-align: middle;
            font-weight: 700;
        }
        tbody tr:hover {
            background-color: #fdfae6;
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

        .doc-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 10px;
            font-size: 0.775rem;
            font-weight: 800;
            border: 2px solid var(--black);
            border-radius: var(--radius-md);
            background: #fff;
            color: #000;
            text-decoration: none;
            box-shadow: var(--shadow-sm);
            cursor: pointer;
            margin: 2px;
            transition: var(--transition);
        }
        .doc-btn:hover {
            transform: translate(-1px, -1px);
            box-shadow: 2px 2px 0px #000;
            background: var(--yellow);
        }

        .action-btn-group {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        /* MODAL */
        .neo-modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.65);
            backdrop-filter: blur(3px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            padding: 16px;
        }
        .neo-modal {
            background: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-xl);
            box-shadow: 8px 8px 0px #000;
            width: 100%;
            max-width: 550px;
            padding: 24px;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }
        /* MODAL PREVIEW DOKUMEN PRO (SEIMBANG & KEMAS) */
        .doc-modal-content {
            max-width: 860px !important;
            width: 95% !important;
            padding: 20px 22px !important;
            border-radius: var(--radius-xl) !important;
            border: var(--border-thick) !important;
            box-shadow: 8px 8px 0px var(--black) !important;
        }

        .doc-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2.5px solid var(--black);
            padding-bottom: 12px;
            margin-bottom: 14px;
            gap: 12px;
            flex-wrap: wrap;
        }

        .doc-modal-title-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .doc-modal-badge {
            background: var(--yellow);
            color: var(--black);
            font-size: 0.75rem;
            font-weight: 900;
            text-transform: uppercase;
            padding: 3px 8px;
            border: 1.5px solid var(--black);
            border-radius: var(--radius-sm);
            box-shadow: 1.5px 1.5px 0px var(--black);
        }

        .doc-viewer-stage {
            width: 100%;
            min-height: 440px;
            max-height: 68vh;
            background: #18181b;
            border: 2.5px solid var(--black);
            border-radius: var(--radius-md);
            overflow: auto;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            box-sizing: border-box;
        }

        .doc-viewer-img {
            max-width: 100%;
            max-height: 62vh;
            object-fit: contain;
            border-radius: 4px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.6);
            transition: transform 0.2s ease;
            transform-origin: center center;
            display: block;
            margin: auto;
        }

        .doc-controls-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 14px;
            padding-top: 12px;
            border-top: 2px dashed #ddd;
        }

        .doc-tool-btn {
            background: var(--white);
            color: var(--black);
            border: 2px solid var(--black);
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-sm);
            padding: 6px 12px;
            font-size: 0.8rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            transition: var(--transition);
        }
        .doc-tool-btn:hover {
            background: var(--yellow);
            transform: translate(-1px, -1px);
            box-shadow: 2px 2px 0px var(--black);
        }
        .doc-tool-btn:active {
            transform: translate(1px, 1px);
            box-shadow: none;
        }
    </style>
</head>
<body>

    <!-- NAVBAR (CONSISTENT WITH JHEPP DASHBOARD) -->
    <header class="neo-navbar">
        <div class="neo-nav-left">
            <button class="menu-toggle-btn" id="open-sidebar" aria-label="Buka Menu"><i class="bi bi-list"></i></button>
            <a href="jhepp_dashboard.php" class="neo-brand">SCRS PMU</a>
        </div>
        <?php render_navbar_actions($conn, 'jhepp', $jhepp_id, $jhepp_username, '../'); ?>
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
            <a href="jhepp_pending.php" class="sidebar-link active"><i class="bi bi-hourglass-split"></i> Menunggu Kelulusan</a>
            <a href="jhepp_approved.php" class="sidebar-link"><i class="bi bi-check-circle-fill"></i> Pelajar Diluluskan</a>
            <a href="jhepp_rejected.php" class="sidebar-link"><i class="bi bi-x-circle-fill"></i> Pendaftaran Ditolak</a>
            <a href="senarai_pendaftaran.php" class="sidebar-link"><i class="bi bi-people-fill"></i> Semua Rekod Pelajar</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        
        <!-- PAGE HEADER -->
        <div style="margin-bottom: 24px;">
            <div class="page-title-row">
                <a href="jhepp_dashboard.php" class="neo-btn btn-yellow btn-arrow-back" title="Papan Pemuka" aria-label="Kembali ke Papan Pemuka">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h1 class="page-title" style="margin: 0;"><i class="bi bi-hourglass-split text-warning"></i> Menunggu Kelulusan JHEPP</h1>
            </div>
            <p class="page-subtitle">Permohonan akaun pelajar baharu yang menunggu semakan dokumen Kad Matrik PMU dan Lesen Memandu.</p>
        </div>

        <?php echo $message; ?>


        <!-- SEARCH -->
        <div class="filter-card">
            <form action="jhepp_pending.php" method="GET" class="search-form">
                <div class="search-input-wrapper">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" name="search" placeholder="Cari nama pelajar, no. matrik, no. IC, atau e-mel..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <button type="submit" class="neo-btn btn-yellow" style="padding: 10px 18px;">
                    <i class="bi bi-funnel-fill"></i> Cari
                </button>
                <?php if (!empty($search)): ?>
                    <a href="jhepp_pending.php" class="neo-btn btn-white" style="padding: 10px 14px;">
                        <i class="bi bi-x-circle"></i> Reset
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- SECTION SUB-HEADER -->
        <div class="section-title" style="margin-bottom: 12px;">
            <i class="bi bi-hourglass-split me-1 text-warning"></i> Senarai Menunggu Pengesahan JHEPP
            <span style="font-size: 0.85rem; color: #555; font-weight: 700;">(<?php echo ($result_students ? $result_students->num_rows : 0); ?> rekod)</span>
        </div>

        <!-- TABLE -->
        <div class="table-card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">Bil</th>
                            <th>Tarikh Daftar</th>
                            <th>Maklumat Pelajar</th>
                            <th>No. Matrik & IC</th>
                            <th>No. Telefon</th>
                            <th>Dokumen Sokongan</th>
                            <th>Status</th>
                            <th style="text-align: center; min-width: 150px;">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_students && $result_students->num_rows > 0): ?>
                            <?php $bil = 1; ?>
                            <?php while ($row = $result_students->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $bil++; ?></td>
                                    <td>
                                        <div style="font-weight: 800; font-size: 0.85rem;"><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></div>
                                        <div style="font-size: 0.75rem; color: #666;"><?php echo date('h:i A', strtotime($row['created_at'])); ?></div>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.95rem; font-weight: 900; color: var(--black);"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                        <div style="font-size: 0.8rem; color: #555;"><?php echo htmlspecialchars($row['email']); ?></div>
                                        <div style="margin-top: 3px;">
                                            <span style="color: #007700; font-size: 0.72rem; font-weight: 900;"><i class="bi bi-check-circle-fill"></i> E-mel Disahkan</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div><span style="font-size: 0.75rem; color: #666;">MATRIK:</span> <strong><?php echo htmlspecialchars($row['no_pendaftaran']); ?></strong></div>
                                        <div><span style="font-size: 0.75rem; color: #666;">IC:</span> <strong><?php echo htmlspecialchars($row['no_ic']); ?></strong></div>
                                    </td>
                                    <td>
                                        <div><strong><?php echo htmlspecialchars($row['phone_no']); ?></strong></div>
                                        <?php 
                                            $clean_phone = preg_replace('/[^0-9]/', '', $row['phone_no']);
                                            if (substr($clean_phone, 0, 1) === '0') {
                                                $wa_phone = '60' . substr($clean_phone, 1);
                                            } else {
                                                $wa_phone = $clean_phone;
                                            }
                                        ?>
                                        <?php if (!empty($clean_phone)): ?>
                                            <a href="https://wa.me/<?php echo $wa_phone; ?>" target="_blank" style="font-size: 0.75rem; color: #007700; font-weight: 800; text-decoration: none;">
                                                <i class="bi bi-whatsapp"></i> WhatsApp
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; flex-direction: column; gap: 4px;">
                                            <?php if (!empty($row['student_id_file'])): ?>
                                                <button type="button" class="doc-btn" onclick="viewDocument('<?php echo htmlspecialchars($row['student_id_file']); ?>', 'Kad Pelajar (Matrik) - <?php echo htmlspecialchars(addslashes($row['full_name'])); ?>')">
                                                    <i class="bi bi-card-heading text-primary"></i> Kad Pelajar
                                                </button>
                                            <?php endif; ?>

                                            <?php if (!empty($row['driving_license_file'])): ?>
                                                <button type="button" class="doc-btn" onclick="viewDocument('<?php echo htmlspecialchars($row['driving_license_file']); ?>', 'Lesen Memandu - <?php echo htmlspecialchars(addslashes($row['full_name'])); ?>')">
                                                    <i class="bi bi-card-checklist text-success"></i> Lesen Memandu
                                                </button>
                                            <?php endif; ?>

                                            <?php if (empty($row['student_id_file']) && empty($row['driving_license_file'])): ?>
                                                <span style="color: #999; font-size: 0.8rem;">Tiada Dokumen</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge-status badge-pending"><i class="bi bi-hourglass-split"></i> Menunggu</span>
                                    </td>
                                    <td style="text-align: center;">
                                        <div class="action-btn-group" style="justify-content: center;">
                                            <button type="button" class="neo-btn btn-sm btn-green" onclick="confirmAction(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['full_name'])); ?>', 'approve')">
                                                <i class="bi bi-check-lg"></i> Lulus
                                            </button>
                                            <button type="button" class="neo-btn btn-sm btn-pink" onclick="confirmAction(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['full_name'])); ?>', 'reject')">
                                                <i class="bi bi-x-lg"></i> Tolak
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 3rem 1rem; color: #666;">
                                    <i class="bi bi-check-circle-fill text-success" style="font-size: 2.5rem; display: block; margin-bottom: 8px;"></i>
                                    Tiada permohonan pelajar yang menunggu kelulusan pada masa ini.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- FOOTER -->
    <footer>
        &copy; <?php echo date("Y"); ?> SCRS PMU. SISTEM SEWAAN KERETA - PORTAL JHEPP.
    </footer>

    <!-- MODAL PREVIEW DOKUMEN (KEMAS, SEIMBANG & INTERAKTIF) -->
    <div class="neo-modal-overlay" id="docModalOverlay" onclick="closeDocModalOutside(event)">
        <div class="neo-modal doc-modal-content" onclick="event.stopPropagation()">
            <!-- HEADER -->
            <div class="doc-modal-header">
                <div class="doc-modal-title-group">
                    <span class="doc-modal-badge" id="docTypeBadge">Dokumen Pelajar</span>
                    <h3 id="docModalTitle" style="font-weight: 900; font-size: 1.1rem; margin: 0; text-transform: uppercase; color: var(--black);">
                        Pratonton Dokumen
                    </h3>
                </div>
                <button type="button" class="neo-btn btn-sm btn-pink" onclick="closeDocModal()" title="Tutup Modal" style="padding: 5px 12px; font-size: 0.82rem;">
                    <i class="bi bi-x-lg"></i> Tutup
                </button>
            </div>

            <!-- VIEWPORT CANVAS BERPUSAT (LATAR KELABU GELAP UNTUK KEJELASAN DOKUMEN) -->
            <div class="doc-viewer-stage" id="docViewerContainer">
                <img id="docPreviewImg" src="" alt="Pratonton Dokumen" class="doc-viewer-img">
                <iframe id="docPreviewPdf" src="" style="width: 100%; height: 62vh; border: none; display: none;"></iframe>
            </div>

            <!-- CONTROLS & ACTIONS TOOLBAR -->
            <div class="doc-controls-toolbar">
                <div style="display: flex; gap: 6px; flex-wrap: wrap; align-items: center;">
                    <button type="button" class="doc-tool-btn" onclick="docZoom(0.15)" title="Besarkan Dokumen">
                        <i class="bi bi-zoom-in"></i> Zoom +
                    </button>
                    <button type="button" class="doc-tool-btn" onclick="docZoom(-0.15)" title="Kecilkan Dokumen">
                        <i class="bi bi-zoom-out"></i> Zoom -
                    </button>
                    <button type="button" class="doc-tool-btn" onclick="docRotate(90)" title="Putar 90 Darjah">
                        <i class="bi bi-arrow-clockwise"></i> Putar 90°
                    </button>
                    <button type="button" class="doc-tool-btn" onclick="docResetTransform()" title="Reset Saiz & Kedudukan">
                        <i class="bi bi-arrow-repeat"></i> Reset
                    </button>
                </div>

                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <a id="docOpenTabBtn" href="" target="_blank" class="neo-btn btn-sm btn-yellow" style="padding: 6px 14px; font-size: 0.82rem;">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Buka Tab Baharu
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL PENGESAHAN TINDAKAN (APPROVE / REJECT) -->
    <div class="neo-modal-overlay" id="actionModalOverlay" onclick="closeActionModalOutside(event)">
        <div class="neo-modal" onclick="event.stopPropagation()">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--black); padding-bottom: 10px; margin-bottom: 15px;">
                <h3 id="actionModalHeader" style="font-weight: 900; font-size: 1.15rem; margin: 0; text-transform: uppercase;">
                    Sahkan Tindakan
                </h3>
                <button type="button" class="neo-btn btn-sm btn-pink" onclick="closeActionModal()"><i class="bi bi-x-lg"></i></button>
            </div>
            
            <p id="actionModalDesc" style="font-weight: 700; font-size: 0.95rem; color: #333; line-height: 1.5;"></p>

            <form action="" method="POST" id="actionForm">
                <input type="hidden" name="action_verify" value="1">
                <input type="hidden" name="user_id" id="modalUserId">
                <input type="hidden" name="action" id="modalAction">

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" class="neo-btn btn-white" onclick="closeActionModal()">Batal</button>
                    <button type="submit" class="neo-btn" id="modalSubmitBtn">
                        Sahkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- SCRIPT -->
    <script>
        // Sidebar Toggle
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
        if (openSidebarBtn) openSidebarBtn.addEventListener('click', openSidebar);
        if (closeSidebarBtn) closeSidebarBtn.addEventListener('click', closeSidebar);
        if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

        // User profile menu dropdown
        const profileToggle = document.getElementById('profile-toggle');
        const profileMenu = document.getElementById('profile-menu');
        if (profileToggle && profileMenu) {
            profileToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                profileMenu.classList.toggle('show');
            });
            document.addEventListener('click', function() {
                profileMenu.classList.remove('show');
            });
        }

        // Dokumen Preview Modal State & Controls
        let currentDocScale = 1.0;
        let currentDocRotate = 0;
        const docModalOverlay = document.getElementById('docModalOverlay');
        const docModalTitle = document.getElementById('docModalTitle');
        const docTypeBadge = document.getElementById('docTypeBadge');
        const docPreviewImg = document.getElementById('docPreviewImg');
        const docPreviewPdf = document.getElementById('docPreviewPdf');
        const docOpenTabBtn = document.getElementById('docOpenTabBtn');

        function updateDocTransform() {
            if (docPreviewImg) {
                docPreviewImg.style.transform = `scale(${currentDocScale}) rotate(${currentDocRotate}deg)`;
            }
        }

        function docZoom(delta) {
            currentDocScale = Math.max(0.5, Math.min(3.0, currentDocScale + delta));
            updateDocTransform();
        }

        function docRotate(deg) {
            currentDocRotate = (currentDocRotate + deg) % 360;
            updateDocTransform();
        }

        function docResetTransform() {
            currentDocScale = 1.0;
            currentDocRotate = 0;
            updateDocTransform();
        }

        function viewDocument(url, title) {
            docResetTransform();
            docModalTitle.textContent = title;

            if (docTypeBadge) {
                const lowerTitle = title.toLowerCase();
                if (lowerTitle.includes('kad pelajar') || lowerTitle.includes('matrik')) {
                    docTypeBadge.textContent = 'Kad Pelajar';
                    docTypeBadge.style.background = 'var(--yellow)';
                } else if (lowerTitle.includes('lesen')) {
                    docTypeBadge.textContent = 'Lesen Memandu';
                    docTypeBadge.style.background = 'var(--green)';
                } else {
                    docTypeBadge.textContent = 'Dokumen Sokongan';
                    docTypeBadge.style.background = 'var(--blue)';
                }
            }

            const fullUrl = (url && !url.startsWith('../') && !url.startsWith('http')) ? '../' + url : url;
            docOpenTabBtn.href = fullUrl;

            const isPdf = fullUrl.toLowerCase().endsWith('.pdf');
            if (isPdf) {
                docPreviewImg.style.display = 'none';
                docPreviewPdf.style.display = 'block';
                docPreviewPdf.src = fullUrl;
            } else {
                docPreviewPdf.style.display = 'none';
                docPreviewImg.style.display = 'block';
                docPreviewImg.src = fullUrl;
            }

            docModalOverlay.style.display = 'flex';
        }

        function closeDocModal() {
            docModalOverlay.style.display = 'none';
            docPreviewImg.src = '';
            docPreviewPdf.src = '';
            docResetTransform();
        }

        function closeDocModalOutside(e) {
            if (e.target === docModalOverlay) {
                closeDocModal();
            }
        }

        // Action Modal
        const actionModalOverlay = document.getElementById('actionModalOverlay');
        const actionModalHeader = document.getElementById('actionModalHeader');
        const actionModalDesc = document.getElementById('actionModalDesc');
        const modalUserId = document.getElementById('modalUserId');
        const modalAction = document.getElementById('modalAction');
        const modalSubmitBtn = document.getElementById('modalSubmitBtn');

        function confirmAction(id, name, action) {
            modalUserId.value = id;
            modalAction.value = action;

            if (action === 'approve') {
                actionModalHeader.innerHTML = '<i class="bi bi-check-circle-fill text-success me-2"></i>Luluskan Pendaftaran Pelajar';
                actionModalDesc.innerHTML = 'Adakah anda pasti mahu <strong>MELULUSKAN</strong> pendaftaran akaun untuk <strong>' + name + '</strong>? E-mel notifikasi kelulusan rasmi akan dihantar kepada pelajar ini.';
                modalSubmitBtn.className = 'neo-btn btn-green';
                modalSubmitBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Ya, Luluskan Akaun';
            } else {
                actionModalHeader.innerHTML = '<i class="bi bi-x-circle-fill text-danger me-2"></i>Tolak Pendaftaran Pelajar';
                actionModalDesc.innerHTML = 'Adakah anda pasti mahu <strong>MENOLAK</strong> pendaftaran akaun untuk <strong>' + name + '</strong>? E-mel pemberitahuan akan dihantar kepada pelajar.';
                modalSubmitBtn.className = 'neo-btn btn-pink';
                modalSubmitBtn.innerHTML = '<i class="bi bi-x-lg me-1"></i> Ya, Tolak Akaun';
            }

            actionModalOverlay.style.display = 'flex';
        }

        function closeActionModal() {
            actionModalOverlay.style.display = 'none';
        }

        function closeActionModalOutside(e) {
            if (e.target === actionModalOverlay) {
                closeActionModal();
            }
        }
    </script>
</body>
</html>
