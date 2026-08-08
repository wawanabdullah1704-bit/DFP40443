<?php
session_start();
require 'db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

$message = "";

// PROSES BUTANG APPROVE ATAU REJECT
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $user_type = $_POST['user_type']; // 'student' atau 'provider'
    $action = $_POST['action']; // 'approve' atau 'reject'
    
    $new_status = ($action === 'approve') ? 'approved' : 'rejected';
    $table = ($user_type === 'provider') ? 'providers' : 'students';
    
    $sql_update = "UPDATE $table SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("si", $new_status, $user_id);
    
    if ($stmt->execute()) {
        $alert_type = ($action === 'approve') ? 'alert-success' : 'alert-danger';
        $alert_text = ($action === 'approve') ? 'diluluskan' : 'ditolak';
        $user_label = ($user_type === 'provider') ? 'Penyedia Kereta' : 'Pelajar';
        
        $message = "<div class='neo-alert {$alert_type} mb-3'><i class='bi bi-check-circle-fill me-2'></i>Berjaya: Akaun {$user_label} telah {$alert_text}!</div>";

        // ==========================================
        // FUNGSI HANTAR E-MEL MENGGUNAKAN PHPMAILER
        // ==========================================
        $sql_email = "SELECT full_name, email FROM $table WHERE id = ?";
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

                $mail->setFrom('admin.jhepp@gmail.com', 'Admin SCRS PMU');
                $mail->addAddress($to_email, $user_name);

                $mail->isHTML(true);
                $mail->Subject = ($action === 'approve') ? "SCRS PMU - Akaun Diluluskan!" : "SCRS PMU - Akaun Ditolak";
                
                if ($action === 'approve') {
                    $mail->Body = "Salam <b>$user_name</b>,<br><br>Tahniah! Pendaftaran akaun anda di SCRS PMU telah <b>DILULUSKAN</b> oleh pihak JHEPP.<br>Anda kini boleh log masuk ke dalam sistem.<br><br>Terima kasih,<br>Admin SCRS PMU";
                } else {
                    $mail->Body = "Salam <b>$user_name</b>,<br><br>Dukacita dimaklumkan bahawa pendaftaran akaun anda di SCRS PMU telah <b>DITOLAK</b> oleh pihak JHEPP. Sila rujuk pihak pengurusan untuk maklumat lanjut.<br><br>Terima kasih,<br>Admin SCRS PMU";
                }

                $mail->send();
                $message .= "<div class='neo-alert alert-success'><i class='bi bi-envelope-check-fill me-2'></i>Notifikasi e-mel berjaya dihantar ke <strong>{$to_email}</strong>.</div>";
            } catch (Exception $e) {
                $message .= "<div class='neo-alert alert-warning'><i class='bi bi-exclamation-triangle-fill me-2'></i>Akaun dikemaskini, tetapi e-mel gagal dihantar. Ralat: {$mail->ErrorInfo}</div>";
            }
        }
        $stmt_email->close();

    } else {
        $message = "<div class='neo-alert alert-danger'>Ralat pangkalan data: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// AMBIL SENARAI PELAJAR DAN PROVIDER PENDING
$sql_pending_students = "SELECT * FROM students WHERE status = 'pending'";
$result_students = $conn->query($sql_pending_students);

$sql_pending_providers = "SELECT * FROM providers WHERE status = 'pending'";
$result_providers = $conn->query($sql_pending_providers);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pengesahan Akaun JHEPP - SCRS PMU</title>
    
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
        button { border: none; background: none; cursor: pointer; font-family: inherit; }

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

        /* MAIN CONTENT */
        .main-content {
            flex: 1;
            padding: 2rem 20px;
            max-width: 800px;
            margin: 0 auto;
            width: 100%;
        }

        .section-title {
            display: inline-block; background: var(--black); color: var(--white);
            font-weight: 900; text-transform: uppercase; padding: 10px 20px;
            box-shadow: 4px 4px 0px var(--yellow); margin-bottom: 25px;
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

        .doc-title {
            font-weight: 900;
            text-transform: uppercase;
            font-size: 0.85rem;
            color: var(--black);
            margin-bottom: 10px;
        }

        .doc-buttons-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .doc-btn {
            background-color: var(--bg-color);
            border: 3px solid var(--black);
            padding: 10px;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.8rem;
            box-shadow: 3px 3px 0px var(--black);
            text-align: center;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .doc-btn:hover { transform: translate(-2px, -2px); box-shadow: 5px 5px 0px var(--black); background: var(--yellow); }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 15px;
            border-top: 3px solid var(--black);
            padding-top: 20px;
        }

        .neo-btn {
            border: 3px solid var(--black);
            box-shadow: 4px 4px 0px var(--black);
            font-weight: 900;
            text-transform: uppercase;
            padding: 12px;
            cursor: pointer;
            transition: var(--transition);
            flex: 1;
            text-align: center;
        }
        .neo-btn:hover { transform: translate(-2px, -2px); box-shadow: 6px 6px 0px var(--black); }
        .neo-btn:active { transform: translate(4px, 4px); box-shadow: var(--shadow-active); }

        .btn-approve { background-color: var(--green); }
        .btn-reject { background-color: var(--pink); }

        .neo-alert {
            border: var(--border-thick); box-shadow: 4px 4px 0px var(--black);
            padding: 12px 15px; font-weight: 800; margin-bottom: 20px; text-transform: uppercase; font-size: 0.85rem;
        }
        .alert-success { background-color: var(--green); }
        .alert-danger { background-color: var(--pink); }
        .alert-warning { background-color: var(--yellow); }

        footer {
            background-color: var(--yellow);
            border-top: var(--border-thick);
            padding: 20px;
            text-align: center;
            font-weight: 900;
            text-transform: uppercase;
            margin-top: auto;
        }

        @media (max-width: 600px) {
            .doc-buttons-grid { grid-template-columns: 1fr; }
            .user-info div { flex-direction: column; gap: 2px; }
            .main-content { padding: 1rem 10px; }
            .neo-brand { font-size: 1.2rem; }
            .user-card { padding: 15px; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <header class="neo-navbar">
        <div class="neo-nav-left">
            <button class="menu-toggle-btn" id="open-sidebar"><i class="bi bi-list"></i></button>
            <div class="neo-brand">SCRS PMU (JHEPP)</div>
        </div>
        <a href="login.php" class="neo-btn" style="flex: none; width: auto; padding: 6px 14px; font-size: 0.85rem; background: var(--pink);">Log Keluar</a>
    </header>

    <!-- SIDEBAR -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Menu Admin</h2>
            <button class="close-btn" id="close-sidebar"><i class="bi bi-x-lg"></i></button>
        </div>
        <nav class="sidebar-nav">
            <a href="verify_account.php" class="sidebar-link active"><i class="bi bi-person-lines-fill"></i> Pengesahan Pengguna</a>
            <a href="login.php" class="sidebar-link"><i class="bi bi-box-arrow-left"></i> Log Keluar</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="section-title"><i class="bi bi-check-all me-2"></i> Senarai Permohonan Akaun</div>
        <p style="font-weight: 700; color: #555; margin-bottom: 25px;">Sahkan pendaftaran akaun pelajar dan penyedia kereta.</p>

        <?php echo $message; ?>

        <!-- BAHAGIAN 1: PELAJAR -->
        <div class="sub-header">
            <i class="bi bi-mortarboard-fill text-primary"></i> Permohonan Akaun Pelajar
        </div>

        <?php if ($result_students->num_rows > 0): ?>
            <?php while ($row = $result_students->fetch_assoc()): ?>
                <div class="user-card" style="border-left: 8px solid var(--blue);">
                    <div class="user-info">
                        <div><label>Nama Penuh</label><span><?php echo htmlspecialchars($row['full_name']); ?></span></div>
                        <div><label>No. Telefon</label><span><?php echo htmlspecialchars($row['phone_no']); ?></span></div>
                        <div><label>No. IC</label><span><?php echo htmlspecialchars($row['no_ic']); ?></span></div>
                        <div><label>No. Matrik / Pendaftaran</label><span><?php echo htmlspecialchars($row['no_pendaftaran']); ?></span></div>
                        <div><label>E-mel</label><span><?php echo htmlspecialchars($row['email']); ?></span></div>
                    </div>

                    <div class="doc-title">Dokumen Sokongan:</div>
                    <div class="doc-buttons-grid">
                        <a href="<?php echo htmlspecialchars($row['student_id_file']); ?>" target="_blank" class="doc-btn">
                            <i class="bi bi-card-heading"></i> Kad Pelajar (ID)
                        </a>
                        <a href="<?php echo htmlspecialchars($row['driving_license_file']); ?>" target="_blank" class="doc-btn">
                            <i class="bi bi-card-checklist"></i> Lesen Memandu
                        </a>
                    </div>

                    <form action="" method="POST">
                        <input type="hidden" name="user_type" value="student">
                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                        
                        <div class="action-buttons">
                            <button type="submit" name="action" value="reject" class="neo-btn btn-reject" onclick="return confirm('Tolak pendaftaran pelajar ini?');">
                                <i class="bi bi-x-circle-fill me-1"></i> Tolak (Reject)
                            </button>
                            <button type="submit" name="action" value="approve" class="neo-btn btn-approve" onclick="return confirm('Luluskan pendaftaran pelajar ini?');">
                                <i class="bi bi-check-circle-fill me-1"></i> Luluskan (Approve)
                            </button>
                        </div>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="background: var(--white); border: 3px dashed var(--black); padding: 1.5rem; text-align: center; font-weight: 700; color: #666; margin-bottom: 30px;">
                Tiada permohonan pendaftaran pelajar yang pending.
            </div>
        <?php endif; ?>

        <!-- BAHAGIAN 2: PROVIDER -->
        <div class="sub-header" style="margin-top: 35px;">
            <i class="bi bi-car-front-fill text-success"></i> Permohonan Akaun Penyedia Kereta
        </div>

        <?php if ($result_providers->num_rows > 0): ?>
            <?php while ($row = $result_providers->fetch_assoc()): ?>
                <div class="user-card" style="border-left: 8px solid var(--green);">
                    <div class="user-info">
                        <div><label>Nama Penuh</label><span><?php echo htmlspecialchars($row['full_name']); ?></span></div>
                        <div><label>No. Telefon</label><span><?php echo htmlspecialchars($row['phone_no']); ?></span></div>
                        <div><label>No. IC</label><span><?php echo htmlspecialchars($row['no_ic']); ?></span></div>
                        <div><label>E-mel</label><span><?php echo htmlspecialchars($row['email']); ?></span></div>
                    </div>

                    <div class="doc-title">Dokumen Sokongan (5 Dokumen):</div>
                    <div class="doc-buttons-grid">
                        <a href="<?php echo htmlspecialchars($row['ic_file']); ?>" target="_blank" class="doc-btn">
                            <i class="bi bi-person-vcard"></i> 1. Kad Pengenalan
                        </a>
                        <a href="<?php echo htmlspecialchars($row['licence_file']); ?>" target="_blank" class="doc-btn">
                            <i class="bi bi-card-checklist"></i> 2. Lesen Memandu
                        </a>
                        <a href="<?php echo htmlspecialchars($row['insurance_file']); ?>" target="_blank" class="doc-btn">
                            <i class="bi bi-file-earmark-text"></i> 3. Insurans
                        </a>
                        <a href="<?php echo htmlspecialchars($row['greencard_file']); ?>" target="_blank" class="doc-btn">
                            <i class="bi bi-journal-text"></i> 4. Geran / Kad Hijau
                        </a>
                        <a href="<?php echo htmlspecialchars($row['roadtax_file']); ?>" target="_blank" class="doc-btn" style="grid-column: span 2;">
                            <i class="bi bi-file-earmark-code"></i> 5. Cukai Jalan (Roadtax)
                        </a>
                    </div>

                    <form action="" method="POST">
                        <input type="hidden" name="user_type" value="provider">
                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                        
                        <div class="action-buttons">
                            <button type="submit" name="action" value="reject" class="neo-btn btn-reject" onclick="return confirm('Tolak pendaftaran penyedia ini?');">
                                <i class="bi bi-x-circle-fill me-1"></i> Tolak (Reject)
                            </button>
                            <button type="submit" name="action" value="approve" class="neo-btn btn-approve" onclick="return confirm('Luluskan pendaftaran penyedia ini?');">
                                <i class="bi bi-check-circle-fill me-1"></i> Luluskan (Approve)
                            </button>
                        </div>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="background: var(--white); border: 3px dashed var(--black); padding: 1.5rem; text-align: center; font-weight: 700; color: #666; margin-bottom: 30px;">
                Tiada permohonan pendaftaran penyedia kereta yang pending.
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
    </script>
</body>
</html>