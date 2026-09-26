<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/Exception.php';
require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);
    $fullName = htmlspecialchars($_POST['fullName']);
    $phoneNo = htmlspecialchars($_POST['phoneNo']);
    $noIC = htmlspecialchars($_POST['noIC']);
    $noPendaftaran = htmlspecialchars($_POST['noPendaftaran']);
    $userPassword = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    if (strlen($userPassword) < 8) {
        $message = '<div class="neo-alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Ralat: Kata laluan mestilah sekurang-kurangnya 8 aksara (8 aksara atau lebih)!</div>';
    }
    else if (!preg_match('/[A-Z]/', $userPassword) || !preg_match('/[a-z]/', $userPassword) || !preg_match('/[0-9]/', $userPassword) || !preg_match('/[^A-Za-z0-9]/', $userPassword)) {
        $message = '<div class="neo-alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Ralat: Kata laluan mesti mengandungi huruf besar, huruf kecil, nombor, dan simbol!</div>';
    }
    else if ($userPassword !== $confirmPassword) {
        $message = '<div class="neo-alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Ralat: Kata laluan tidak sepadan!</div>';
    } 
    else {
        $check_sql = "SELECT id FROM students WHERE username = ? OR email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $message = '<div class="neo-alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Ralat: Nama Pengguna (Username) atau E-mel ini telah digunakan.</div>';
        } else {
            $hashedPassword = password_hash($userPassword, PASSWORD_DEFAULT);
            $targetDir = "../uploads/documents/";

            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $studentIdName = basename($_FILES["studentId"]["name"]);
            $drivingLicenseName = basename($_FILES["drivingLicense"]["name"]);

            $newStudentIdName = $noPendaftaran . "_ID_" . time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $studentIdName);
            $newLicenseName = $noPendaftaran . "_License_" . time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $drivingLicenseName);

            $targetStudentId = $targetDir . $newStudentIdName;
            $targetLicense = $targetDir . $newLicenseName;
            $dbStudentId = "uploads/documents/" . $newStudentIdName;
            $dbLicense = "uploads/documents/" . $newLicenseName;

            if (
                move_uploaded_file($_FILES["studentId"]["tmp_name"], $targetStudentId) &&
                move_uploaded_file($_FILES["drivingLicense"]["tmp_name"], $targetLicense)
            ) {
                // Jana Token Pengesahan E-mel
                $verification_token = bin2hex(random_bytes(32));
                $email_verified = 0;
                $status = 'pending';

                $sql = "INSERT INTO students (username, email, full_name, phone_no, no_ic, no_pendaftaran, password, student_id_file, driving_license_file, status, email_verified, verification_token) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssssssis", $username, $email, $fullName, $phoneNo, $noIC, $noPendaftaran, $hashedPassword, $dbStudentId, $dbLicense, $status, $email_verified, $verification_token);

                if ($stmt->execute()) {
                    // Bina Pautan Pengesahan E-mel
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
                    $domainName = $_SERVER['HTTP_HOST'];
                    $dirPath = dirname($_SERVER['PHP_SELF']);
                    $verifyLink = $protocol . $domainName . rtrim($dirPath, '/\\') . "/verify_email.php?token=" . $verification_token;

                    // Hantar E-mel Pengesahan kepada Pelajar
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'chickenmasterz26@gmail.com';
                        $mail->Password   = 'pcccoszzikvwmzsd';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;

                        $mail->setFrom('admin.jhepp@gmail.com', 'SCRS PMU');
                        $mail->addAddress($email, $fullName);

                        $mail->isHTML(true);
                        $mail->Subject = 'SCRS PMU - Pengesahan E-mel Pendaftaran Pelajar';
                        
                        $mail->Body = "
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 3px solid #000; background: #ffffff;'>
                            <div style='background: #ffde59; padding: 15px; border-bottom: 3px solid #000; text-align: center;'>
                                <h2 style='margin: 0; text-transform: uppercase; font-weight: 900; color: #000;'>SCRS PMU</h2>
                                <p style='margin: 5px 0 0 0; font-size: 13px; font-weight: bold; color: #222;'>Pengesahan E-mel Pendaftaran Akaun Pelajar</p>
                            </div>
                            <div style='padding: 25px 20px; color: #333; line-height: 1.6;'>
                                <p>Salam <b>" . htmlspecialchars($fullName) . "</b>,</p>
                                <p>Terima kasih kerana mendaftar akaun pelajar di <b>Sistem Sewaan Kereta Siswa Politeknik Mukah (SCRS PMU)</b>.</p>
                                <p>Sila klik butang di bawah untuk mengesahkan alamat e-mel anda. Selepas e-mel anda disahkan, permohonan anda akan dihantar ke pihak <b>JHEPP PMU</b> untuk semakan dan kelulusan dokumen:</p>
                                <div style='text-align: center; margin: 30px 0;'>
                                    <a href='{$verifyLink}' style='background: #00e676; color: #000; padding: 14px 28px; font-weight: 900; text-decoration: none; text-transform: uppercase; border: 3px solid #000; display: inline-block; box-shadow: 4px 4px 0px #000;'>
                                        Sahkan E-mel Saya Sekarang &rarr;
                                    </a>
                                </div>
                                <p style='font-size: 12px; color: #666;'>Jika butang di atas tidak boleh ditekan, salin dan buka pautan berikut di pelayar anda:<br><a href='{$verifyLink}' style='color: #0055ff;'>{$verifyLink}</a></p>
                            </div>
                            <div style='background: #f4f4f0; padding: 12px; border-top: 2px solid #000; text-align: center; font-size: 12px; color: #555;'>
                                &copy; SCRS PMU - Politeknik Mukah Sarawak
                            </div>
                        </div>";

                        $mail->send();
                    } catch (Exception $e) {
                        // Email sending logged, proceed to pending page with instruction
                    }

                    header("Location: pending.php?type=verify_email&email=" . urlencode($email));
                    exit();
                } else {
                    $message = '<div class="neo-alert alert-danger">Ralat Pangkalan Data: ' . $stmt->error . '</div>';
                }
                $stmt->close();
            } else {
                $message = '<div class="neo-alert alert-danger">Ralat: Gagal memuat naik dokumen. Sila cuba lagi.</div>';
            }
        }
        $check_stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Daftar Akaun Pelajar - SCRS PMU</title>
    
    <!-- Ikon Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Master Neo-Brutalism CSS -->
    <link rel="stylesheet" href="../assets/css/neo-style.css">

    <style>
        .main-content {
            flex: 1;
            padding: 2rem 16px;
            max-width: 680px;
            margin: 0 auto;
            width: 100%;
        }

        .reg-card {
            background-color: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            padding: 28px 24px;
        }

        .reg-header {
            font-size: 1.35rem;
            font-weight: 900;
            text-transform: uppercase;
            text-align: center;
            margin-bottom: 20px;
            background-color: var(--yellow);
            border: var(--border-thick);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px 16px;
            line-height: 1.3;
        }
        .reg-header i {
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-toggle-btn {
            position: absolute;
            right: 12px;
            cursor: pointer;
            font-size: 1.2rem;
            color: var(--black);
            background: none;
            border: none;
            padding: 4px;
            border-radius: var(--radius-sm);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .terms-box {
            background-color: #fffde7;
            border: var(--border-thick);
            border-radius: var(--radius-md);
            padding: 12px 14px;
            margin-top: 10px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .terms-box input[type="checkbox"] {
            width: 22px;
            height: 22px;
            min-width: 22px;
            border: var(--border-thin);
            border-radius: var(--radius-xs);
            cursor: pointer;
            accent-color: var(--black);
            margin-top: 2px;
        }
        .terms-box label {
            font-weight: 800;
            font-size: 0.85rem;
            cursor: pointer;
            line-height: 1.5;
        }
        .terms-link {
            color: #0055ff;
            text-decoration: underline;
            font-weight: 900;
            cursor: pointer;
            background: none;
            border: none;
            font-family: inherit;
            font-size: inherit;
            padding: 0;
        }
        .terms-link:hover { color: #ff2200; }

        /* MODAL OVERLAY */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            padding: 15px;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: var(--white);
            border: 4px solid var(--black);
            border-radius: var(--radius-xl);
            box-shadow: 8px 8px 0px var(--black);
            max-width: 680px;
            width: 100%;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .modal-header-custom {
            background-color: var(--yellow);
            border-bottom: 3px solid var(--black);
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header-custom h5 {
            font-weight: 900;
            font-size: 1.1rem;
            text-transform: uppercase;
        }
        .modal-close-btn {
            background: var(--white);
            border: 3px solid var(--black);
            border-radius: var(--radius-sm);
            box-shadow: 2px 2px 0px var(--black);
            font-size: 1.2rem;
            font-weight: 900;
            cursor: pointer;
            padding: 2px 10px;
            line-height: 1.4;
        }
        .modal-close-btn:hover { background: var(--pink); }
        .modal-body-custom {
            padding: 20px;
            overflow-y: auto;
            flex: 1;
            font-size: 0.88rem;
        }
        .modal-body-custom h6 {
            font-weight: 900;
            text-transform: uppercase;
            font-size: 0.85rem;
            border-bottom: 2px solid var(--black);
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .modal-body-custom ol {
            padding-left: 20px;
            line-height: 1.7;
        }
        .modal-body-custom ol li { margin-bottom: 8px; }
        .modal-footer-custom {
            border-top: 3px solid var(--black);
            padding: 14px 20px;
            display: flex;
            justify-content: center;
        }
        .modal-agree-btn {
            background-color: var(--green);
            border: 3px solid var(--black);
            border-radius: var(--radius-md);
            box-shadow: 4px 4px 0px var(--black);
            font-weight: 900;
            text-transform: uppercase;
            padding: 10px 40px;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.95rem;
            transition: var(--transition);
        }
        .modal-agree-btn:hover { transform: translate(-2px,-2px); box-shadow: 6px 6px 0px var(--black); }

        @media (max-width: 600px) {
            .form-row { grid-template-columns: 1fr; }
            .reg-card { padding: 20px 14px; }
            .main-content { padding: 1rem 10px; }
            .neo-brand { font-size: 1.2rem; }
            .reg-header {
                font-size: 1.05rem;
                padding: 10px 12px;
                gap: 8px;
                line-height: 1.35;
            }
            .reg-header i {
                font-size: 1.2rem;
            }
        }
        @media (max-width: 400px) {
            .reg-header {
                font-size: 0.95rem;
                padding: 8px 10px;
            }
            .reg-header i {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <header class="neo-navbar">
        <div class="neo-nav-left">
            <a href="../index.php" class="neo-brand">SCRS PMU</a>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div style="margin-bottom: 12px;">
            <a href="choose_role.php" class="neo-btn btn-yellow btn-arrow-back" title="Kembali" aria-label="Kembali">
                <i class="bi bi-arrow-left"></i>
            </a>
        </div>
        <div class="reg-card">
            <div class="reg-header">
                <i class="bi bi-mortarboard-fill"></i>
                <span>Pendaftaran Akaun Pelajar</span>
            </div>
            <p style="font-weight: 700; color: #555; font-size: 0.9rem; margin-bottom: 20px; text-align: center; border-bottom: 2px dashed #ddd; padding-bottom: 12px; line-height: 1.4;">
                <strong>Panduan:</strong> Sila isi maklumat peribadi anda dengan lengkap dan muat naik dokumen (Kad Matrik Pelajar &amp; Lesen Memandu) untuk disahkan oleh pihak pentadbir JHEPP sebelum akaun diaktifkan.
            </p>

            <?php echo $message; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nama Pengguna (Username)</label>
                        <input type="text" class="form-control" name="username" placeholder="Cth: Ali67" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">E-mel</label>
                        <input type="email" class="form-control" name="email" placeholder="Cth: pelajar@gmail.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Penuh</label>
                    <input type="text" class="form-control" name="fullName" placeholder="Cth: Ali bin Abu" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nombor Telefon</label>
                        <input type="text" class="form-control" name="phoneNo" placeholder="Cth: 0123456789" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nombor Pendaftaran Matrik</label>
                        <input type="text" class="form-control" name="noPendaftaran" placeholder="Cth: 20DDT21F1001" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Nombor Kad Pengenalan (IC)</label>
                    <input type="text" class="form-control" name="noIC" placeholder="Cth: 010203-13-1234" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Kata Laluan</label>
                        <div class="input-wrapper">
                            <input type="password" class="form-control" name="password" id="password" placeholder="8 aksara atau lebih" minlength="8" required>
                            <button type="button" class="password-toggle-btn" id="togglePassword">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                        </div>
                        <!-- Strength Indicator -->
                        <div id="strength-box" style="display:none; margin-top:8px; border:2px solid var(--black); padding:8px; background:#fafafa;">
                            <div style="display:flex; gap:4px; margin-bottom:6px;">
                                <div id="s1" style="flex:1;height:5px;background:#ddd;"></div>
                                <div id="s2" style="flex:1;height:5px;background:#ddd;"></div>
                                <div id="s3" style="flex:1;height:5px;background:#ddd;"></div>
                                <div id="s4" style="flex:1;height:5px;background:#ddd;"></div>
                                <div id="s5" style="flex:1;height:5px;background:#ddd;"></div>
                            </div>
                            <div style="font-size:0.78rem; font-weight:800; display:flex; flex-wrap:wrap; gap:8px;">
                                <span id="chk-len">&#x2715; 8+ Aksara</span>
                                <span id="chk-upper">&#x2715; Huruf Besar</span>
                                <span id="chk-lower">&#x2715; Huruf Kecil</span>
                                <span id="chk-num">&#x2715; Nombor</span>
                                <span id="chk-sym">&#x2715; Simbol</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sahkan Kata Laluan</label>
                        <div class="input-wrapper">
                            <input type="password" class="form-control" name="confirmPassword" id="confirmPassword" placeholder="Ulang kata laluan" minlength="8" required>
                        </div>
                    </div>
                </div>

                <div style="border-top: 2px dashed var(--black); margin: 20px 0 15px 0; padding-top: 15px;">
                    <p style="font-weight: 900; text-transform: uppercase; font-size: 0.9rem; margin-bottom: 12px; color: #0055ff;">Muat Naik Dokumen Pengesahan JHEPP</p>

                    <div class="form-group">
                        <label class="form-label">1. Kad Pelajar / Student ID (Matrik PMU)</label>
                        <input type="file" class="form-control" name="studentId" accept=".jpg, .jpeg, .png, .pdf" required style="border-style: dashed;">
                    </div>

                    <div class="form-group">
                        <label class="form-label">2. Lesen Memandu Yang Sah</label>
                        <input type="file" class="form-control" name="drivingLicense" accept=".jpg, .jpeg, .png, .pdf" required style="border-style: dashed;">
                    </div>
                </div>

                <!-- TERMS & CONDITIONS CHECKBOX -->
                <div class="terms-box">
                    <input type="checkbox" id="agreeTerms" name="agreeTerms" required>
                    <label for="agreeTerms">
                        Saya telah membaca dan bersetuju dengan 
                        <button type="button" class="terms-link" onclick="openTermsModal()">Terma dan Syarat / Terms and Conditions</button>
                        sistem sewaan kereta SCRS PMU.
                    </label>
                </div>

                <button type="submit" class="neo-btn btn-green btn-block" style="margin-top: 14px;">
                    <i class="bi bi-cloud-arrow-up-fill me-1"></i> Hantar Pendaftaran Pelajar
                </button>
            </form>

            <div style="text-align: center; margin-top: 22px; font-weight: 800; font-size: 0.88rem; border-top: 2px dashed var(--black); padding-top: 15px;">
                <div>Sudah mendaftar? <a href="../index.php" style="color: #0055ff; text-decoration: underline;">Log Masuk di sini</a></div>
            </div>
        </div>
    </main>

    <!-- MODAL TERMA DAN SYARAT - PELAJAR -->
    <div class="modal-overlay" id="termsModalOverlay">
        <div class="modal-box">
            <div class="modal-header-custom">
                <h5><i class="bi bi-file-earmark-text-fill me-2"></i>Terma &amp; Syarat / Terms &amp; Conditions</h5>
            </div>
            <div class="modal-body-custom">
                <!-- Versi Bahasa Melayu -->
                <h6>Terma dan Syarat (Pelajar / Penyewa)</h6>
                <ol class="mb-4">
                    <li><strong>Kelayakan:</strong> Penyewa mestilah pelajar yang berdaftar dan wajib memiliki Lesen Memandu Malaysia yang masih sah tempoh.</li>
                    <li><strong>Tanggungjawab Penjagaan:</strong> Penyewa bertanggungjawab sepenuhnya ke atas keselamatan, kebersihan, dan penjagaan kenderaan sepanjang tempoh sewaan.</li>
                    <li><strong>Saman dan Kesalahan Trafik:</strong> Sebarang saman lalu lintas, kompaun, atau denda yang dikenakan semasa tempoh sewaan adalah tanggungjawab penyewa sepenuhnya.</li>
                    <li><strong>Larangan Menyewa Semula:</strong> Penyewa dilarang sama sekali menyewakan semula (sublet) kenderaan tersebut kepada pihak ketiga atau rakan lain.</li>
                    <li><strong>Kerosakan dan Kemalangan:</strong> Sebarang kerosakan kenderaan akibat kecuaian penyewa perlu dilaporkan segera kepada penyedia kereta. Kos pembaikan adalah di bawah tanggungjawab penyewa.</li>
                    <li><strong>Pemulangan Kenderaan:</strong> Kenderaan hendaklah dipulangkan pada masa dan tarikh yang telah dipersetujui beserta tahap minyak yang sama seperti sebelum disewa. Kelewatan boleh menyebabkan caj tambahan dikenakan.</li>
                </ol>

                <!-- Versi Bahasa Inggeris -->
                <h6 style="margin-top:18px;">Terms and Conditions (Student / Renter)</h6>
                <ol>
                    <li><strong>Eligibility:</strong> The renter must be a registered student and possess a valid Malaysian Driving License.</li>
                    <li><strong>Care Responsibility:</strong> The renter is fully responsible for the safety, cleanliness, and care of the vehicle throughout the rental period.</li>
                    <li><strong>Summons and Traffic Offences:</strong> Any traffic summons, compounds, or fines incurred during the rental period are the sole responsibility of the renter.</li>
                    <li><strong>Prohibition of Subletting:</strong> The renter is strictly prohibited from subletting the vehicle to a third party or other friends.</li>
                    <li><strong>Damages and Accidents:</strong> Any damage to the vehicle due to the renter's negligence must be reported immediately to the car provider. Repair costs are the renter's responsibility.</li>
                    <li><strong>Vehicle Return:</strong> The vehicle must be returned at the agreed time and date with the same fuel level as before the rental. Delays may incur additional charges.</li>
                </ol>
            </div>
            <div class="modal-footer-custom">
                <button class="modal-agree-btn" onclick="agreeAndClose()">
                    <i class="bi bi-check-circle-fill me-2"></i>Saya Faham / I Understand
                </button>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        &copy; <?php echo date("Y"); ?> SCRS PMU. SISTEM SEWAAN KERETA.
    </footer>

    <!-- SKRIP ASLI (VANILLA JS) -->
    <script>
        // Toggle Password Visibility
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');

        if (togglePassword && password) {
            togglePassword.addEventListener('click', function () {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.querySelector('i').classList.toggle('bi-eye-fill');
                this.querySelector('i').classList.toggle('bi-eye-slash-fill');
            });
        }

        // Password Strength Indicator
        const strengthBox = document.getElementById('strength-box');
        const bars = [document.getElementById('s1'), document.getElementById('s2'), document.getElementById('s3'), document.getElementById('s4'), document.getElementById('s5')];
        const chkLen   = document.getElementById('chk-len');
        const chkUpper = document.getElementById('chk-upper');
        const chkLower = document.getElementById('chk-lower');
        const chkNum   = document.getElementById('chk-num');
        const chkSym   = document.getElementById('chk-sym');
        const colors   = ['#ff4444','#ff7700','#ffbb00','#00bbff','#00e676'];

        function updateCheck(el, pass, text) {
            if (!el) return;
            el.style.color = pass ? '#007700' : '#cc0000';
            el.innerHTML = (pass ? '&#x2714;' : '&#x2715;') + ' ' + text;
        }

        if (password) {
            password.addEventListener('input', function() {
                const v = this.value;
                if (!v) { strengthBox.style.display = 'none'; return; }
                strengthBox.style.display = 'block';

                const hasLen   = v.length >= 8;
                const hasUpper = /[A-Z]/.test(v);
                const hasLower = /[a-z]/.test(v);
                const hasNum   = /[0-9]/.test(v);
                const hasSym   = /[^A-Za-z0-9]/.test(v);
                const score    = [hasLen, hasUpper, hasLower, hasNum, hasSym].filter(Boolean).length;

                bars.forEach((b, i) => {
                    if (b) b.style.background = i < score ? colors[score - 1] : '#ddd';
                });

                updateCheck(chkLen, hasLen, '8+ Aksara');
                updateCheck(chkUpper, hasUpper, 'Huruf Besar');
                updateCheck(chkLower, hasLower, 'Huruf Kecil');
                updateCheck(chkNum, hasNum, 'Nombor');
                updateCheck(chkSym, hasSym, 'Simbol');
            });
        }

        // Terms & Conditions Modal
        const termsOverlay = document.getElementById('termsModalOverlay');
        const agreeCheckbox = document.getElementById('agreeTerms');

        function openTermsModal() {
            termsOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeTermsModal() {
            termsOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        function agreeAndClose() {
            agreeCheckbox.checked = true;
            closeTermsModal();
        }

        // Close modal when clicking outside the box
        termsOverlay.addEventListener('click', function(e) {
            if (e.target === termsOverlay) closeTermsModal();
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeTermsModal();
        });
    </script>
</body>
</html>