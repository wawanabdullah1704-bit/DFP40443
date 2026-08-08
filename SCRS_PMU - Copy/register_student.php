<?php
require 'db.php';

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

    if (strlen($userPassword) <= 8) {
        $message = '<div class="neo-alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Ralat: Kata laluan mestilah lebih daripada 8 aksara!</div>';
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
            $targetDir = "uploads/";

            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $studentIdName = basename($_FILES["studentId"]["name"]);
            $drivingLicenseName = basename($_FILES["drivingLicense"]["name"]);

            $newStudentIdName = $noPendaftaran . "_ID_" . time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $studentIdName);
            $newLicenseName = $noPendaftaran . "_License_" . time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $drivingLicenseName);

            $targetStudentId = $targetDir . $newStudentIdName;
            $targetLicense = $targetDir . $newLicenseName;

            if (
                move_uploaded_file($_FILES["studentId"]["tmp_name"], $targetStudentId) &&
                move_uploaded_file($_FILES["drivingLicense"]["tmp_name"], $targetLicense)
            ) {
                $sql = "INSERT INTO students (username, email, full_name, phone_no, no_ic, no_pendaftaran, password, student_id_file, driving_license_file) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssssss", $username, $email, $fullName, $phoneNo, $noIC, $noPendaftaran, $hashedPassword, $targetStudentId, $targetLicense);

                if ($stmt->execute()) {
                    header("Location: pending.php");
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
            background-color: var(--yellow);
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
            background-color: var(--green);
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
                <i class="bi bi-mortarboard-fill me-2"></i> Pendaftaran Pelajar
            </div>

            <?php echo $message; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nama Pengguna (Username)</label>
                        <input type="text" class="form-control" name="username" placeholder="Cth: feeq67" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">E-mel PMU</label>
                        <input type="email" class="form-control" name="email" placeholder="Cth: pelajar@gmail.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Penuh (Mengikut IC)</label>
                    <input type="text" class="form-control" name="fullName" placeholder="Cth: Ahmad bin Ali" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nombor Telefon</label>
                        <input type="text" class="form-control" name="phoneNo" placeholder="Cth: 0123456789" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nombor Kad Pengenalan (IC)</label>
                        <input type="text" class="form-control" name="noIC" placeholder="Cth: 020101-13-1234" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Nombor Pendaftaran Matrik</label>
                    <input type="text" class="form-control" name="noPendaftaran" placeholder="Cth: 20DIT24F1008" required>
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
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sahkan Kata Laluan</label>
                        <div class="input-wrapper">
                            <input type="password" class="form-control" name="confirmPassword" id="confirmPassword" placeholder="Ulang kata laluan" required>
                        </div>
                    </div>
                </div>

                <div style="border-top: 2px dashed var(--black); margin: 20px 0 15px 0; padding-top: 15px;">
                    <div class="form-group">
                        <label class="form-label">Muat Naik Kad Pelajar / Student ID (Wajib)</label>
                        <input type="file" class="form-control" name="studentId" accept=".jpg, .jpeg, .png, .pdf" required style="border-style: dashed;">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Muat Naik Lesen Memandu (Wajib)</label>
                        <input type="file" class="form-control" name="drivingLicense" accept=".jpg, .jpeg, .png, .pdf" required style="border-style: dashed;">
                    </div>
                </div>

                <button type="submit" class="neo-btn">
                    <i class="bi bi-cloud-arrow-up-fill me-1"></i> Hantar Pendaftaran Pelajar
                </button>
            </form>

            <div style="text-align: center; margin-top: 20px; font-weight: 800; font-size: 0.9rem;">
                Sudah mendaftar? <a href="login.php" style="color: #0055ff; text-decoration: underline;">Log Masuk di sini</a>
            </div>
        </div>
    </main>

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

        // Sidebar Offcanvas
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