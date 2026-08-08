<?php
require 'db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);
    $fullName = htmlspecialchars($_POST['fullName']);
    $phoneNo = htmlspecialchars($_POST['phoneNo']);
    $noIC = htmlspecialchars($_POST['noIC']);
    $userPassword = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    if (strlen($userPassword) <= 8) {
        $message = '<div class="neo-alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Ralat: Kata laluan mestilah lebih daripada 8 aksara!</div>';
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
            $targetDir = "uploads/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $icName = basename($_FILES["ic_file"]["name"]);
            $licenceName = basename($_FILES["licence_file"]["name"]);
            $insuranceName = basename($_FILES["insurance_file"]["name"]);
            $greencardName = basename($_FILES["greencard_file"]["name"]);
            $roadtaxName = basename($_FILES["roadtax_file"]["name"]);

            $time = time();

            $newIc = $noIC . "_IC_" . $time . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $icName);
            $newLicence = $noIC . "_Licence_" . $time . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $licenceName);
            $newInsurance = $noIC . "_Ins_" . $time . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $insuranceName);
            $newGreencard = $noIC . "_GC_" . $time . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $greencardName);
            $newRoadtax = $noIC . "_RT_" . $time . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $roadtaxName);

            $targetIc = $targetDir . $newIc;
            $targetLicence = $targetDir . $newLicence;
            $targetInsurance = $targetDir . $newInsurance;
            $targetGreencard = $targetDir . $newGreencard;
            $targetRoadtax = $targetDir . $newRoadtax;

            if (
                move_uploaded_file($_FILES["ic_file"]["tmp_name"], $targetIc) &&
                move_uploaded_file($_FILES["licence_file"]["tmp_name"], $targetLicence) &&
                move_uploaded_file($_FILES["insurance_file"]["tmp_name"], $targetInsurance) &&
                move_uploaded_file($_FILES["greencard_file"]["tmp_name"], $targetGreencard) &&
                move_uploaded_file($_FILES["roadtax_file"]["tmp_name"], $targetRoadtax)
            ) {
                $sql = "INSERT INTO providers (username, email, full_name, phone_no, no_ic, password, ic_file, licence_file, insurance_file, greencard_file, roadtax_file) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssssssss", $username, $email, $fullName, $phoneNo, $noIC, $hashedPassword, $targetIc, $targetLicence, $targetInsurance, $targetGreencard, $targetRoadtax);

                if ($stmt->execute()) {
                    header("Location: pending.php");
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
    <title>Daftar Akaun Penyedia Kereta - SCRS PMU</title>
    
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
        button, input { font-family: inherit; }

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
            max-width: 650px;
            margin: 0 auto;
            width: 100%;
        }

        .reg-card {
            background-color: var(--white);
            border: var(--border-thick);
            box-shadow: var(--shadow-solid);
            padding: 30px;
        }

        .reg-header {
            font-size: 1.6rem;
            font-weight: 900;
            text-transform: uppercase;
            text-align: center;
            margin-bottom: 25px;
            background-color: var(--green);
            border: 3px solid var(--black);
            padding: 10px;
            box-shadow: 4px 4px 0px var(--black);
        }

        .form-group {
            margin-bottom: 18px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .form-label {
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .form-control {
            border: 3px solid var(--black);
            padding: 11px;
            font-weight: 700;
            background-color: var(--bg-color);
            outline: none;
            width: 100%;
        }
        .form-control:focus { background-color: var(--white); box-shadow: 3px 3px 0px var(--black); }

        .password-toggle-btn {
            position: absolute;
            right: 12px;
            cursor: pointer;
            font-size: 1.2rem;
            color: var(--black);
            background: none;
            border: none;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .neo-btn {
            background-color: var(--yellow);
            border: 3px solid var(--black);
            box-shadow: 4px 4px 0px var(--black);
            font-weight: 900;
            text-transform: uppercase;
            padding: 12px;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            font-size: 1rem;
            margin-top: 10px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }
        .neo-btn:hover { transform: translate(-2px, -2px); box-shadow: 6px 6px 0px var(--black); }
        .neo-btn:active { transform: translate(4px, 4px); box-shadow: var(--shadow-active); }

        .neo-alert {
            border: var(--border-thick); box-shadow: 4px 4px 0px var(--black);
            padding: 12px 15px; font-weight: 800; margin-bottom: 20px; text-transform: uppercase; font-size: 0.85rem;
        }
        .alert-danger { background-color: var(--pink); }

        footer {
            background-color: var(--yellow);
            border-top: var(--border-thick);
            padding: 20px;
            text-align: center;
            font-weight: 900;
            text-transform: uppercase;
            margin-top: auto;
        }

        /* TERMS CHECKBOX */
        .terms-box {
            background-color: #e8f5e9;
            border: 3px solid var(--black);
            box-shadow: 3px 3px 0px var(--black);
            padding: 14px 16px;
            margin-bottom: 16px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .terms-box input[type="checkbox"] {
            width: 22px;
            height: 22px;
            min-width: 22px;
            border: 3px solid var(--black);
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
            box-shadow: 8px 8px 0px var(--black);
            max-width: 680px;
            width: 100%;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
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
            .reg-card { padding: 20px 15px; }
            .main-content { padding: 1rem 10px; }
            .neo-brand { font-size: 1.2rem; }
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
        <a href="choose_role.php" class="neo-btn" style="width: auto; padding: 6px 12px; font-size: 0.8rem; background: var(--yellow); margin-top: 0;">Tukar Peranan</a>
    </header>

    <!-- SIDEBAR -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Menu Utama</h2>
            <button class="close-btn" id="close-sidebar"><i class="bi bi-x-lg"></i></button>
        </div>
        <nav class="sidebar-nav">
            <a href="login.php" class="sidebar-link"><i class="bi bi-box-arrow-in-right"></i> Log Masuk</a>
            <a href="choose_role.php" class="sidebar-link active"><i class="bi bi-person-plus-fill"></i> Pilih Peranan / Daftar</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="reg-card">
            <div class="reg-header">
                <i class="bi bi-car-front-fill me-2"></i> Pendaftaran Penyedia Kereta
            </div>

            <?php echo $message; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nama Pengguna (Username)</label>
                        <input type="text" class="form-control" name="username" placeholder="Cth: provider1" required>
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
                        <input type="text" class="form-control" name="phoneNo" placeholder="Cth: 0123456789" required>
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
                            <input type="password" class="form-control" name="password" id="password" placeholder="Lebih 8 aksara" required>
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
                            </div>
                            <div style="font-size:0.78rem; font-weight:800;">
                                <span id="chk-upper" style="margin-right:8px;">&#x2715; Huruf Besar</span>
                                <span id="chk-lower" style="margin-right:8px;">&#x2715; Huruf Kecil</span>
                                <span id="chk-num" style="margin-right:8px;">&#x2715; Nombor</span>
                                <span id="chk-sym">&#x2715; Simbol</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sahkan Kata Laluan</label>
                        <div class="input-wrapper">
                            <input type="password" class="form-control" name="confirmPassword" id="confirmPassword" placeholder="Ulang kata laluan" required>
                        </div>
                    </div>
                </div>

                <div style="border-top: 2px dashed var(--black); margin: 20px 0 15px 0; padding-top: 15px;">
                    <p style="font-weight: 900; text-transform: uppercase; font-size: 0.9rem; margin-bottom: 12px; color: #0055ff;">Muat Naik Dokumen Wajib (5 Dokumen)</p>

                    <div class="form-group">
                        <label class="form-label">1. Kad Pengenalan (IC)</label>
                        <input type="file" class="form-control" name="ic_file" accept=".jpg, .jpeg, .png, .pdf" required style="border-style: dashed;">
                    </div>

                    <div class="form-group">
                        <label class="form-label">2. Lesen Memandu</label>
                        <input type="file" class="form-control" name="licence_file" accept=".jpg, .jpeg, .png, .pdf" required style="border-style: dashed;">
                    </div>

                    <div class="form-group">
                        <label class="form-label">3. Insurans Kenderaan</label>
                        <input type="file" class="form-control" name="insurance_file" accept=".jpg, .jpeg, .png, .pdf" required style="border-style: dashed;">
                    </div>

                    <div class="form-group">
                        <label class="form-label">4. Geran / Kad Hijau Kenderaan</label>
                        <input type="file" class="form-control" name="greencard_file" accept=".jpg, .jpeg, .png, .pdf" required style="border-style: dashed;">
                    </div>

                    <div class="form-group">
                        <label class="form-label">5. Cukai Jalan (Roadtax)</label>
                        <input type="file" class="form-control" name="roadtax_file" accept=".jpg, .jpeg, .png, .pdf" required style="border-style: dashed;">
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

                <button type="submit" class="neo-btn">
                    <i class="bi bi-cloud-arrow-up-fill me-1"></i> Hantar Pendaftaran Penyedia
                </button>
            </form>

            <div style="text-align: center; margin-top: 20px; font-weight: 800; font-size: 0.9rem;">
                Sudah mendaftar? <a href="login.php" style="color: #0055ff; text-decoration: underline;">Log Masuk di sini</a>
            </div>
        </div>
    </main>

    <!-- MODAL TERMA DAN SYARAT - PENYEDIA KERETA -->
    <div class="modal-overlay" id="termsModalOverlay">
        <div class="modal-box">
            <div class="modal-header-custom">
                <h5><i class="bi bi-file-earmark-text-fill me-2"></i>Terma &amp; Syarat / Terms &amp; Conditions</h5>
                <button class="modal-close-btn" onclick="closeTermsModal()">&times;</button>
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
        const bars = [document.getElementById('s1'), document.getElementById('s2'), document.getElementById('s3'), document.getElementById('s4')];
        const chkUpper = document.getElementById('chk-upper');
        const chkLower = document.getElementById('chk-lower');
        const chkNum   = document.getElementById('chk-num');
        const chkSym   = document.getElementById('chk-sym');
        const colors   = ['#ff4444','#ffbb00','#00bbff','#00e676'];

        if (password) {
            password.addEventListener('input', function() {
                const v = this.value;
                if (!v) { strengthBox.style.display = 'none'; return; }
                strengthBox.style.display = 'block';

                const hasUpper = /[A-Z]/.test(v);
                const hasLower = /[a-z]/.test(v);
                const hasNum   = /[0-9]/.test(v);
                const hasSym   = /[^A-Za-z0-9]/.test(v);
                const score    = [hasUpper, hasLower, hasNum, hasSym].filter(Boolean).length;

                bars.forEach((b, i) => b.style.background = i < score ? colors[score - 1] : '#ddd');

                chkUpper.innerHTML = (hasUpper ? '&#x2714;' : '&#x2715;') + ' Huruf Besar';
                chkUpper.style.color = hasUpper ? '#007700' : '#cc0000';
                chkLower.innerHTML = (hasLower ? '&#x2714;' : '&#x2715;') + ' Huruf Kecil';
                chkLower.style.color = hasLower ? '#007700' : '#cc0000';
                chkNum.innerHTML   = (hasNum   ? '&#x2714;' : '&#x2715;') + ' Nombor';
                chkNum.style.color = hasNum ? '#007700' : '#cc0000';
                chkSym.innerHTML   = (hasSym   ? '&#x2714;' : '&#x2715;') + ' Simbol';
                chkSym.style.color = hasSym ? '#007700' : '#cc0000';
            });
        }

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