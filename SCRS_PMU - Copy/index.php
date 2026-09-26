<?php
session_start();
require_once __DIR__ . '/includes/db.php';

$error_message = "";

// Semak jika borang Log Masuk dihantar
if (isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] == "POST") {
    
    $username = htmlspecialchars($_POST['username']);
    $password = $_POST['password'];
    $user_found = false;

    // 1. SEMAKAN JADUAL ADMINS (PENTADBIR SISTEM)
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
            $_SESSION['full_name'] = $row['full_name'];
            $_SESSION['role'] = 'admin';
            
            header("Location: admin/admin_dashboard.php"); 
            exit();
        } else {
            $error_message = '<div class="neo-alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Ralat: Kata laluan salah!</div>';
        }
    }
    $stmt->close();

    // 2. SEMAKAN JADUAL JHEPP (PEGAWAI JHEPP - PENGESAHAN SAHAJA)
    if (!$user_found) {
        $sql_jhepp = "SELECT id, username, password, full_name FROM jhepp WHERE username = ?";
        $stmt_j = $conn->prepare($sql_jhepp);
        $stmt_j->bind_param("s", $username);
        $stmt_j->execute();
        $res_jhepp = $stmt_j->get_result();

        if ($res_jhepp->num_rows > 0) {
            $user_found = true;
            $row = $res_jhepp->fetch_assoc();
            
            if (password_verify($password, $row['password'])) {
                $_SESSION['jhepp_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['full_name'] = $row['full_name'];
                $_SESSION['role'] = 'jhepp';
                
                header("Location: jhepp/jhepp_dashboard.php"); 
                exit();
            } else {
                $error_message = '<div class="neo-alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Ralat: Kata laluan salah!</div>';
            }
        }
        $stmt_j->close();
    }

    // 2. SEMAKAN JADUAL STUDENTS
    if (!$user_found) {
        $sql_student = "SELECT id, username, password, full_name, status, email_verified FROM students WHERE username = ?";
        $stmt = $conn->prepare($sql_student);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res_student = $stmt->get_result();

        if ($res_student->num_rows > 0) {
            $user_found = true;
            $row = $res_student->fetch_assoc();
            
            if (password_verify($password, $row['password'])) {
                
                if (isset($row['email_verified']) && (int)$row['email_verified'] === 0) {
                    $error_message = '<div class="neo-alert alert-warning"><i class="bi bi-envelope-exclamation-fill me-2"></i><strong>Pengesahan E-mel Diperlukan:</strong> Sila semak peti masuk e-mel anda dan klik pautan pengesahan terlebih dahulu sebelum akaun anda disemak oleh pihak JHEPP.</div>';
                } else if ($row['status'] === 'pending') {
                    header("Location: auth/pending.php?type=jhepp_review");
                    exit();
                } else if ($row['status'] === 'rejected') {
                    $error_message = '<div class="neo-alert alert-danger"><i class="bi bi-x-circle-fill me-2"></i>Maaf, pendaftaran akaun anda telah ditolak oleh JHEPP.</div>';
                } else if ($row['status'] === 'approved') {
                    $_SESSION['student_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['role'] = 'student';
                    
                    header("Location: student/dashboard.php");
                    exit();
                }
                
            } else {
                $error_message = '<div class="neo-alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Ralat: Kata laluan salah!</div>';
            }
        }
        $stmt->close();
    }

    // 3. SEMAKAN JADUAL PROVIDERS (TERUS DAPAT LOGIN TANPA PENGESAHAN JHEPP)
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
                
                if ($row['status'] === 'rejected') {
                    $error_message = '<div class="neo-alert alert-danger"><i class="bi bi-x-circle-fill me-2"></i>Maaf, akaun Penyedia Kereta anda telah disekat.</div>';
                } else {
                    $_SESSION['provider_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['role'] = 'provider';
                    
                    header("Location: provider/provider_dashboard.php");
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
    <!-- Master Neo-Brutalism CSS -->
    <link rel="stylesheet" href="assets/css/neo-style.css">

    <style>
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 16px;
        }

        .login-card {
            background-color: var(--white);
            border: var(--border-thick);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            padding: 30px;
            width: 100%;
            max-width: 440px;
        }

        .login-title {
            font-size: 1.5rem;
            font-weight: 900;
            text-transform: uppercase;
            text-align: center;
            margin-bottom: 16px;
            background-color: var(--yellow);
            border: var(--border-thin);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .welcome-banner {
            background: var(--white);
            border: var(--border-thin);
            border-radius: var(--radius-md);
            padding: 9px 12px;
            font-weight: 700;
            margin-bottom: 20px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            font-size: 0.85rem;
            line-height: 1.4;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
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
        }

        .register-prompt {
            text-align: center;
            margin-top: 20px;
            font-weight: 700;
            font-size: 0.875rem;
            border-top: 2px dashed var(--black);
            padding-top: 15px;
        }

        @media (max-width: 480px) {
            .login-card { padding: 22px 18px; }
            .welcome-banner {
                font-size: 0.8rem;
                padding: 8px 10px;
                margin-bottom: 16px;
            }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <header class="neo-navbar">
        <div class="neo-nav-left">
            <a href="index.php" class="neo-brand">SCRS PMU</a>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="login-card">
            <div class="login-title">
                <i class="bi bi-shield-lock-fill"></i> Log Masuk
            </div>

            <div class="welcome-banner">
                <i class="bi bi-info-circle-fill text-warning"></i> Sila log masuk ke akaun anda.
            </div>

            <?php if (isset($_GET['registered']) && $_GET['registered'] === 'provider'): ?>
                <div class="neo-alert alert-success">
                    <i class="bi bi-check-circle-fill"></i>
                    <div><strong>Pendaftaran Berjaya!</strong> Akaun Penyedia Kereta anda telah aktif. Sila log masuk.</div>
                </div>
            <?php endif; ?>

            <?php echo $error_message; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label class="form-label" for="username">Nama Pengguna (Username)</label>
                    <input type="text" class="form-control" name="username" id="username" placeholder="Masukkan nama pengguna" required autocomplete="username">
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Kata Laluan</label>
                    <div class="input-wrapper">
                        <input type="password" class="form-control" name="password" id="password" placeholder="Masukkan kata laluan" required autocomplete="current-password">
                        <button type="button" class="password-toggle-btn" id="togglePassword" aria-label="Lihat Kata Laluan">
                             <i class="bi bi-eye-fill"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="neo-btn btn-green btn-block" style="margin-top: 10px;">
                    Log Masuk
                </button>
            </form>

            <div class="register-prompt">
                Belum mempunyai akaun? <br>
                <a href="auth/choose_role.php" style="color: #0055ff; font-weight: 900; text-decoration: underline;">Daftar Akaun Baharu Di Sini!</a>
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
    </script>
</body>
</html>