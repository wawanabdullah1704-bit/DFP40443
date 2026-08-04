<?php
session_start();
require 'db.php';

// 1. PANGGIL FAIL PHPMAILER
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
    $action = $_POST['action']; // Sama ada 'approve' atau 'reject'
    
    $new_status = ($action === 'approve') ? 'approved' : 'rejected';
    
    // Tentukan jadual database berdasarkan jenis pengguna
    $table = ($user_type === 'provider') ? 'providers' : 'students';
    
    // Kemaskini status di dalam pangkalan data
    $sql_update = "UPDATE $table SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("si", $new_status, $user_id);
    
    if ($stmt->execute()) {
        $alert_type = ($action === 'approve') ? 'alert-success' : 'alert-danger';
        $alert_text = ($action === 'approve') ? 'diluluskan' : 'ditolak';
        $user_label = ($user_type === 'provider') ? 'Provider' : 'Pelajar';
        
        $message = "<div class='alert {$alert_type} mb-3'>Berjaya: Akaun {$user_label} telah {$alert_text}!</div>";

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
                // Konfigurasi Server Gmail SMTP
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                
                // ⚠️ MASUKKAN E-MEL & APP PASSWORD ANDA DI SINI ⚠️
                $mail->Username   = 'chickenmasterz26@gmail.com';   // Ganti dengan e-mel admin anda
                $mail->Password   = 'pcccoszzikvwmzsd';        // Ganti dengan App Password 16 digit
                
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Tetapan Pengirim dan Penerima
                $mail->setFrom('admin.jhepp@gmail.com', 'Admin SCRS PMU');
                $mail->addAddress($to_email, $user_name);

                // Format E-mel
                $mail->isHTML(true);
                $mail->Subject = ($action === 'approve') ? "SCRS PMU - Akaun Diluluskan!" : "SCRS PMU - Akaun Ditolak";
                
                if ($action === 'approve') {
                    $mail->Body = "Salam <b>$user_name</b>,<br><br>Tahniah! Pendaftaran akaun anda di SCRS PMU telah <b>DILULUSKAN</b> oleh pihak JHEPP.<br>Anda kini boleh log masuk ke dalam sistem.<br><br>Terima kasih,<br>Admin SCRS PMU";
                } else {
                    $mail->Body = "Salam <b>$user_name</b>,<br><br>Dukacita dimaklumkan bahawa pendaftaran akaun anda di SCRS PMU telah <b>DITOLAK</b> oleh pihak JHEPP. Sila rujuk pihak pengurusan untuk maklumat lanjut.<br><br>Terima kasih,<br>Admin SCRS PMU";
                }

                // Hantar E-mel
                $mail->send();
                $message .= "<div class='alert alert-info'>Notifikasi e-mel berjaya dihantar ke <strong>{$to_email}</strong>.</div>";
            } catch (Exception $e) {
                // Mesej ralat jika gagal hantar e-mel
                $message .= "<div class='alert alert-warning'>Akaun dikemaskini, tetapi e-mel gagal dihantar. Ralat: {$mail->ErrorInfo}</div>";
            }
        }
        $stmt_email->close();
        // ==========================================

    } else {
        $message = "<div class='alert alert-danger'>Ralat pangkalan data: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// AMBIL SENARAI PELAJAR DAN PROVIDER YANG MASIH PENDING
$sql_pending_students = "SELECT * FROM students WHERE status = 'pending'";
$result_students = $conn->query($sql_pending_students);

$sql_pending_providers = "SELECT * FROM providers WHERE status = 'pending'";
$result_providers = $conn->query($sql_pending_providers);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Account - JHEPP</title>
    <style>
        body { background-color: #f8f9fa; min-height: 100vh; display: flex; flex-direction: column; }
        .navbar-brand { font-weight: bold; color: #0d6efd !important; }
        .document-btn { background-color: #f1f3f5; color: #495057; font-weight: 500; font-size: 0.9rem;}
        .document-btn:hover { background-color: #e9ecef; }
        .main-content { flex-grow: 1; }
    </style>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container-fluid">
            <button class="btn btn-light border-0 me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu">
                <i class="bi bi-list fs-4"></i>
            </button>
            <a class="navbar-brand me-auto" href="#">SCRS PMU</a>
        </div>
    </nav>

    <!-- OFFCANVAS MENU -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title text-primary fw-bold" id="sidebarMenuLabel">Menu JHEPP</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="#">
                        <i class="bi bi-person-lines-fill text-primary me-3 fs-4"></i> Verify Users
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="login.php">
                        <i class="bi bi-box-arrow-left text-danger me-3 fs-4"></i> Log Out
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- KANDUNGAN UTAMA -->
    <div class="main-content">
        <div class="container mt-4">
            <div class="row justify-content-center">
                <div class="col-md-6 col-sm-10">
                    
                    <h3 class="fw-bold mb-4 text-center">List of user registration</h3>
                    
                    <?php echo $message; ?>

                    <!-- ============================================== -->
                    <!-- BAHAGIAN 1: PENGESAHAN PELAJAR (STUDENT)       -->
                    <!-- ============================================== -->
                    <h5 class="text-secondary fw-bold mb-3 border-bottom pb-2">Student Registrations</h5>

                    <?php 
                    if ($result_students->num_rows > 0) {
                        while ($row = $result_students->fetch_assoc()) { 
                    ?>
                    <div class="card shadow-sm border-0 mb-4 rounded-4 border-start border-primary border-4">
                        <div class="card-body p-4">
                            
                            <div class="mb-3" style="font-size: 1.1rem;">
                                <div class="mb-1">Name: <strong><?php echo htmlspecialchars($row['full_name']); ?></strong></div>
                                <div class="mb-1">Phone No. : <strong><?php echo htmlspecialchars($row['phone_no']); ?></strong></div>
                                <div class="mb-1">No. IC: <strong><?php echo htmlspecialchars($row['no_ic']); ?></strong></div>
                                <div class="mb-1">No. Pendaftaran: <strong><?php echo htmlspecialchars($row['no_pendaftaran']); ?></strong></div>
                                <div class="mb-1">Email: <strong><?php echo htmlspecialchars($row['email']); ?></strong></div>
                            </div>

                            <p class="text-secondary fw-bold mb-2 mt-4" style="font-size: 0.85rem; letter-spacing: 0.5px;">DOCUMENT</p>
                            
                            <a href="<?php echo $row['student_id_file']; ?>" target="_blank" class="btn document-btn border w-100 mb-2 py-2 rounded-3 text-uppercase">STUDENT ID</a>
                            <a href="<?php echo $row['driving_license_file']; ?>" target="_blank" class="btn document-btn border w-100 mb-4 py-2 rounded-3 text-uppercase">DRIVING LICENCE</a>

                            <form action="" method="POST">
                                <input type="hidden" name="user_type" value="student">
                                <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" name="action" value="reject" class="btn btn-danger w-50 fw-bold py-2 rounded-3" style="background-color: #c90000; border: none;">REJECT</button>
                                    <button type="submit" name="action" value="approve" class="btn btn-success w-50 fw-bold py-2 rounded-3" style="background-color: #00b33c; border: none;">APPROVE</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php 
                        } 
                    } else {
                        echo '<div class="alert alert-light border shadow-sm text-secondary">Tiada pendaftaran pelajar baharu.</div>';
                    }
                    ?>

                    <!-- ============================================== -->
                    <!-- BAHAGIAN 2: PENGESAHAN PROVIDER KERETA         -->
                    <!-- ============================================== -->
                    <h5 class="text-secondary fw-bold mb-3 mt-5 border-bottom pb-2">Car Provider Registrations</h5>

                    <?php 
                    if ($result_providers->num_rows > 0) {
                        while ($row = $result_providers->fetch_assoc()) { 
                    ?>
                    <div class="card shadow-sm border-0 mb-4 rounded-4 border-start border-warning border-4">
                        <div class="card-body p-4">
                            
                            <div class="mb-3" style="font-size: 1.1rem;">
                                <div class="mb-1">Name: <strong><?php echo htmlspecialchars($row['full_name']); ?></strong></div>
                                <div class="mb-1">Phone No. : <strong><?php echo htmlspecialchars($row['phone_no']); ?></strong></div>
                                <div class="mb-1">No. IC: <strong><?php echo htmlspecialchars($row['no_ic']); ?></strong></div>
                                <div class="mb-1">Email: <strong><?php echo htmlspecialchars($row['email']); ?></strong></div>
                            </div>

                            <p class="text-secondary fw-bold mb-2 mt-4" style="font-size: 0.85rem; letter-spacing: 0.5px;">DOCUMENT (5 FILES)</p>
                            
                            <div class="row g-2 mb-4">
                                <div class="col-6"><a href="<?php echo $row['ic_file']; ?>" target="_blank" class="btn document-btn border w-100 py-2 rounded-3 text-uppercase">IC COPY</a></div>
                                <div class="col-6"><a href="<?php echo $row['licence_file']; ?>" target="_blank" class="btn document-btn border w-100 py-2 rounded-3 text-uppercase">LICENCE</a></div>
                                <div class="col-12"><a href="<?php echo $row['insurance_file']; ?>" target="_blank" class="btn document-btn border w-100 py-2 rounded-3 text-uppercase">INSURANCE COVER NOTE</a></div>
                                <div class="col-6"><a href="<?php echo $row['greencard_file']; ?>" target="_blank" class="btn document-btn border w-100 py-2 rounded-3 text-uppercase">GREEN CARD</a></div>
                                <div class="col-6"><a href="<?php echo $row['roadtax_file']; ?>" target="_blank" class="btn document-btn border w-100 py-2 rounded-3 text-uppercase">ROADTAX</a></div>
                            </div>

                            <form action="" method="POST">
                                <input type="hidden" name="user_type" value="provider">
                                <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" name="action" value="reject" class="btn btn-danger w-50 fw-bold py-2 rounded-3" style="background-color: #c90000; border: none;">REJECT</button>
                                    <button type="submit" name="action" value="approve" class="btn btn-success w-50 fw-bold py-2 rounded-3" style="background-color: #00b33c; border: none;">APPROVE</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php 
                        } 
                    } else {
                        echo '<div class="alert alert-light border shadow-sm mb-5 text-secondary">Tiada pendaftaran provider baharu.</div>';
                    }
                    ?>

                </div>
            </div>
        </div>
    </div>

    <!-- COPYRIGHT FOOTER/WATERMARK -->
    <footer class="text-center py-3 mt-auto text-secondary">
        <small>&copy; <?php echo date("Y"); ?> SCRS PMU. Hak Cipta Terpelihara.</small>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>