<?php
require_once __DIR__ . '/../includes/db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);
    $fullName = htmlspecialchars($_POST['fullName']);
    $phoneNo = htmlspecialchars($_POST['phoneNo']);
    $noIC = htmlspecialchars($_POST['noIC']);
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
        $check_sql = "SELECT id FROM providers WHERE username = ? OR email = ?";
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

            $icName = basename($_FILES["ic_file"]["name"]);
            $licenceName = basename($_FILES["licence_file"]["name"]);

            $time = time();

            $newIc = $noIC . "_IC_" . $time . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $icName);
            $newLicence = $noIC . "_Licence_" . $time . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $licenceName);

            $targetIc = $targetDir . $newIc;
            $targetLicence = $targetDir . $newLicence;
            $dbIc = "uploads/documents/" . $newIc;
            $dbLicence = "uploads/documents/" . $newLicence;

            if (
                move_uploaded_file($_FILES["ic_file"]["tmp_name"], $targetIc) &&
                move_uploaded_file($_FILES["licence_file"]["tmp_name"], $targetLicence)
            ) {
                $status = 'approved';
                $sql = "INSERT INTO providers (username, email, full_name, phone_no, no_ic, password, ic_file, licence_file, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssssss", $username, $email, $fullName, $phoneNo, $noIC, $hashedPassword, $dbIc, $dbLicence, $status);

                if ($stmt->execute()) {
                    header("Location: ../index.php?registered=provider");
                    exit();
                } else {
                    $message = '<div class="neo-alert alert-danger">Ralat Pangkalan Data: ' . $stmt->error . '</div>';
                }
                $stmt->close();
            } else {
                $message = '<div class="neo-alert alert-danger">Ralat: Gagal memuat naik satu atau lebih dokumen. Sila cuba lagi.</div>';
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
    <title>Daftar Penyedia Kereta - SCRS PMU</title>
    
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
            background-color: var(--green);
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
            background-color: var(--green);
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
            background-color: var(--yellow);
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
                <i class="bi bi-car-front-fill"></i>
                <span>Pendaftaran Penyedia Kereta</span>
            </div>
            <p style="font-weight: 700; color: #555; font-size: 0.9rem; margin-bottom: 20px; text-align: center; border-bottom: 2px dashed #ddd; padding-bottom: 12px; line-height: 1.4;">
                <strong>Panduan:</strong> Sila lengkapkan maklumat peribadi dan muat naik 5 dokumen wajib (Kad Pengenalan, Lesen Memandu, Geran Kenderaan, Cukai Jalan &amp; Insurans) untuk pengesahan akaun oleh pihak JHEPP.
            </p>

            <?php echo $message; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nama Pengguna (Username)</label>
                        <input type="text" class="form-control" name="username" placeholder="Cth: Abu123" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">E-mel</label>
                        <input type="email" class="form-control" name="email" placeholder="Cth: provider@gmail.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Penuh (Mengikut IC)</label>
                    <input type="text" class="form-control" name="fullName" placeholder="Cth: Abu bin Bakar" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nombor Telefon</label>
                        <input type="text" class="form-control" name="phoneNo" placeholder="Cth: +60123456789" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nombor Kad Pengenalan (IC)</label>
                        <input type="text" class="form-control" name="noIC" placeholder="Cth: 0123456-78-1234" required>
                    </div>
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
                    <p style="font-weight: 900; text-transform: uppercase; font-size: 0.9rem; margin-bottom: 6px; color: #0055ff;">Muat Naik Dokumen Wajib (2 Dokumen Sahaja)</p>
                    <p style="font-size: 0.8rem; font-weight: 700; color: #666; margin-bottom: 12px;">*Dokumen kenderaan (Insurans, Geran & Cukai Jalan) akan dimuat naik semasa anda menambah kenderaan di portal penyedia.</p>

                    <div class="form-group">
                        <label class="form-label">1. Kad Pengenalan (IC)</label>
                        <input type="file" class="form-control" name="ic_file" accept=".jpg, .jpeg, .png, .pdf" required style="border-style: dashed;">
                    </div>

                    <div class="form-group">
                        <label class="form-label">2. Lesen Memandu</label>
                        <input type="file" class="form-control" name="licence_file" accept=".jpg, .jpeg, .png, .pdf" required style="border-style: dashed;">
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
                    <i class="bi bi-cloud-arrow-up-fill me-1"></i> Hantar Pendaftaran Penyedia
                </button>
            </form>

            <div style="text-align: center; margin-top: 22px; font-weight: 800; font-size: 0.88rem; border-top: 2px dashed var(--black); padding-top: 15px;">
                <div>Sudah mendaftar? <a href="../index.php" style="color: #0055ff; text-decoration: underline;">Log Masuk di sini</a></div>
            </div>
        </div>
    </main>

    <!-- MODAL TERMA DAN SYARAT - PENYEDIA KERETA -->
    <div class="modal-overlay" id="termsModalOverlay">
        <div class="modal-box">
            <div class="modal-header-custom">
                <h5><i class="bi bi-file-earmark-text-fill me-2"></i>Terma &amp; Syarat / Terms &amp; Conditions</h5>
            </div>
            <div class="modal-body-custom">
                <!-- Versi Bahasa Melayu -->
                <h6>Terma dan Syarat (Penyedia Kereta)</h6>
                <ol class="mb-4">
                    <li><strong>Keadaan Kenderaan:</strong> Kenderaan yang disewakan mestilah diselenggara dengan baik, selamat untuk dipandu, dan tidak mempunyai masalah mekanikal yang kritikal.</li>
                    <li><strong>Dokumen Kenderaan:</strong> Penyedia wajib memastikan Cukai Jalan (Roadtax) dan Insurans kenderaan adalah sah dan tidak tamat tempoh sepanjang urusan sewaan dijalankan.</li>
                    <li><strong>Pemeriksaan Sebelum Sewaan:</strong> Penyedia bertanggungjawab untuk memeriksa keadaan fizikal kenderaan dan merekodkan tahap minyak bersama penyewa sebelum menyerahkan kunci.</li>
                    <li><strong>Ketepatan Maklumat:</strong> Segala maklumat pengenalan diri dan dokumen kenderaan yang dimuat naik ke dalam sistem mestilah sah dan benar.</li>
                    <li><strong>Pengecualian Liabiliti:</strong> Pihak pengurusan sistem (termasuk JHEPP) tidak akan bertanggungjawab ke atas sebarang kerosakan, kemalangan, kehilangan, atau pertikaian kewangan. Segala risiko dan tuntutan adalah di antara penyedia kereta dan penyewa sahaja.</li>
                </ol>

                <!-- Versi Bahasa Inggeris -->
                <h6 style="margin-top:18px;">Terms and Conditions (Car Provider)</h6>
                <ol>
                    <li><strong>Vehicle Condition:</strong> The rented vehicle must be well-maintained, safe to drive, and have no critical mechanical issues.</li>
                    <li><strong>Vehicle Documents:</strong> Providers must ensure the Roadtax and Insurance are valid and not expired during the rental period.</li>
                    <li><strong>Pre-Rental Inspection:</strong> Providers are responsible for physically inspecting the vehicle and recording fuel levels with the renter before handing over the keys.</li>
                    <li><strong>Information Accuracy:</strong> All personal details and vehicle documents uploaded into the system must be valid and authentic.</li>
                    <li><strong>Liability Exemption:</strong> System management (including JHEPP) will not be liable for any damage, accident, loss, or financial dispute. All risks and claims are strictly between the car provider and the renter.</li>
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