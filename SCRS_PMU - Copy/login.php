<?php
session_start();
require 'db.php';

$error_message = "";

// Semak jika borang Log Masuk dihantar
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $username = htmlspecialchars($_POST['username']);
    $password = $_POST['password'];
    $user_found = false;

    // 1. SEMAKAN JADUAL ADMINS (JHEPP)
    $sql_admin = "SELECT id, username, password, full_name FROM admins WHERE username = ?";
    $stmt = $conn->prepare($sql_admin);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res_admin = $stmt->get_result();

    if ($res_admin->num_rows > 0) {
        $user_found = true;
        $row = $res_admin->fetch_assoc();
        
        if (password_verify($password, $row['password'])) {
            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = 'admin';
            
            header("Location: verify_account.php"); 
            exit();
        } else {
            $error_message = '<div class="neo-alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Ralat: Kata laluan salah!</div>';
        }
    }
    $stmt->close();

    // 2. SEMAKAN JADUAL STUDENTS
    if (!$user_found) {
        $sql_student = "SELECT id, username, password, full_name, status FROM students WHERE username = ?";
        $stmt = $conn->prepare($sql_student);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res_student = $stmt->get_result();

        if ($res_student->num_rows > 0) {
            $user_found = true;
            $row = $res_student->fetch_assoc();
            
            if (password_verify($password, $row['password'])) {
                
                if ($row['status'] === 'pending') {
                    header("Location: pending.php");
                    exit();
                } else if ($row['status'] === 'rejected') {
                    $error_message = '<div class="neo-alert alert-danger"><i class="bi bi-x-circle-fill me-2"></i>Maaf, pendaftaran akaun anda telah ditolak oleh JHEPP.</div>';
                } else if ($row['status'] === 'approved') {
                    $_SESSION['student_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['role'] = 'student';
                    
                    header("Location: dashboard.php");
                    exit();
                }
                
            } else {
                $error_message = '<div class="neo-alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Ralat: Kata laluan salah!</div>';
            }
        }
        $stmt->close();
    }

    // 3. SEMAKAN JADUAL PROVIDERS
    if (!$user_found) {
        $sql_provider = "SELECT id, username, password, full_name, status FROM providers WHERE username = ?";
        $stmt = $conn->prepare($sql_provider);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res_provider = $stmt->get_result();

        if ($res_provider->num_rows > 0) {
            $user_found = true;
            $row = $res_provider->fetch_assoc();
            
            if (password_verify($password, $row['password'])) {
                
                if ($row['status'] === 'pending') {
                    header("Location: pending.php");
                    exit();
                } else if ($row['status'] === 'rejected') {
                    $error_message = '<div class="neo-alert alert-danger"><i class="bi bi-x-circle-fill me-2"></i>Maaf, pendaftaran akaun Penyedia Kereta anda telah ditolak.</div>';
                } else if ($row['status'] === 'approved') {
                    $_SESSION['provider_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['role'] = 'provider';
                    
                    header("Location: provider_dashboard.php");
                    exit();
                }

            } else {
                $error_message = '<div class="neo-alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Ralat: Kata laluan salah!</div>';
            }
        }
        $stmt->close();
    }

    if (!$user_found) {
        $error_message = '<div class="neo-alert alert-danger"><i class="bi bi-exclamation-octagon-fill me-2"></i>Ralat: Nama Pengguna (Username) tidak wujud!</div>';
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Log Masuk - SCRS PMU</title>
    
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 20px;
        }

        .login-card {
            background-color: var(--white);
            border: var(--border-thick);
            box-shadow: 8px 8px 0px var(--black);
            padding: 30px;
            width: 100%;
            max-width: 450px;
        }

        .login-title {
            font-size: 1.8rem;
            font-weight: 900;
            text-transform: uppercase;
            text-align: center;
            margin-bottom: 20px;
            background-color: var(--yellow);
            border: 3px solid var(--black);
            padding: 8px;
            box-shadow: 4px 4px 0px var(--black);
        }

        .form-group {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            gap: 6px;
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
            padding: 12px;
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
        }
        .neo-btn:hover { transform: translate(-2px, -2px); box-shadow: 6px 6px 0px var(--black); }
        .neo-btn:active { transform: translate(4px, 4px); box-shadow: var(--shadow-active); }

        .neo-alert {
            border: var(--border-thick); box-shadow: 4px 4px 0px var(--black);
            padding: 12px 15px; font-weight: 800; margin-bottom: 20px; text-transform: uppercase; font-size: 0.85rem;
        }
        .alert-danger { background-color: var(--pink); }

        .register-prompt {
            text-align: center;
            margin-top: 20px;
            font-weight: 700;
            font-size: 0.9rem;
            border-top: 2px dashed var(--black);
            padding-top: 15px;
        }

        footer {
            background-color: var(--yellow);
            border-top: var(--border-thick);
            padding: 20px;
            text-align: center;
            font-weight: 900;
            text-transform: uppercase;
            margin-top: auto;
        }

        @media (max-width: 480px) {
            .login-card { padding: 20px 15px; }
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
        <a href="choose_role.php" class="neo-btn" style="width: auto; padding: 6px 12px; font-size: 0.8rem; background: var(--yellow); margin-top: 0;">Daftar Akaun</a>
    </header>

    <!-- SIDEBAR -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Menu Utama</h2>
            <button class="close-btn" id="close-sidebar"><i class="bi bi-x-lg"></i></button>
        </div>
        <nav class="sidebar-nav">
            <a href="login.php" class="sidebar-link active"><i class="bi bi-box-arrow-in-right"></i> Log Masuk</a>
            <a href="choose_role.php" class="sidebar-link"><i class="bi bi-person-plus-fill"></i> Pilih Peranan / Daftar</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="login-card">
            <div class="login-title">
                <i class="bi bi-shield-lock-fill me-1"></i> Log Masuk
            </div>

            <?php echo $error_message; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label class="form-label" for="username">Nama Pengguna (Username)</label>
                    <input type="text" class="form-control" name="username" id="username" placeholder="Masukkan nama pengguna" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Kata Laluan</label>
                    <div class="input-wrapper">
                        <input type="password" class="form-control" name="password" id="password" placeholder="Masukkan kata laluan" required>
                        <button type="button" class="password-toggle-btn" id="togglePassword">
                            <i class="bi bi-eye-fill"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="neo-btn">
                    Log Masuk <i class="bi bi-arrow-right-circle-fill ms-1"></i>
                </button>
            </form>

            <div class="register-prompt">
                Belum mempunyai akaun? <br>
                <a href="choose_role.php" style="color: #0055ff; font-weight: 900; text-decoration: underline;">Daftar Akaun Baharu Di Sini!</a>
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