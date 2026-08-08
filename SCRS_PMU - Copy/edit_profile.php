<?php
session_start();
require 'db.php';

if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];
$username = $_SESSION['username'];
$message = "";

// Ambil maklumat pengguna mengikut peranan
if ($role === 'student') {
    $user_id = $_SESSION['student_id'];
    $table = 'students';
} else if ($role === 'provider') {
    $user_id = $_SESSION['provider_id'];
    $table = 'providers';
} else {
    header("Location: login.php");
    exit();
}

// Prosess Kemaskini
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $full_name = htmlspecialchars($_POST['full_name']);
    $phone_no = htmlspecialchars($_POST['phone_no']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!empty($new_password)) {
        if (strlen($new_password) <= 8) {
            $message = "<div class='neo-alert alert-danger'>Ralat: Kata laluan mestilah lebih daripada 8 aksara!</div>";
        } else if ($new_password !== $confirm_password) {
            $message = "<div class='neo-alert alert-danger'>Ralat: Pengesahan kata laluan tidak sepadan!</div>";
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_up = "UPDATE $table SET full_name = ?, phone_no = ?, password = ? WHERE id = ?";
            $stmt_up = $conn->prepare($sql_up);
            $stmt_up->bind_param("sssi", $full_name, $phone_no, $hashed, $user_id);
            if ($stmt_up->execute()) {
                $message = "<div class='neo-alert alert-success'>Berjaya: Maklumat profil dan kata laluan telah dikemaskini!</div>";
            } else {
                $message = "<div class='neo-alert alert-danger'>Ralat pangkalan data: " . $stmt_up->error . "</div>";
            }
            $stmt_up->close();
        }
    } else {
        $sql_up = "UPDATE $table SET full_name = ?, phone_no = ? WHERE id = ?";
        $stmt_up = $conn->prepare($sql_up);
        $stmt_up->bind_param("ssi", $full_name, $phone_no, $user_id);
        if ($stmt_up->execute()) {
            $message = "<div class='neo-alert alert-success'>Berjaya: Maklumat profil anda telah dikemaskini!</div>";
        } else {
            $message = "<div class='neo-alert alert-danger'>Ralat pangkalan data: " . $stmt_up->error . "</div>";
        }
        $stmt_up->close();
    }
}

// Ambil data profil terkini
$sql_get = "SELECT * FROM $table WHERE id = ?";
$stmt_get = $conn->prepare($sql_get);
$stmt_get->bind_param("i", $user_id);
$stmt_get->execute();
$user_data = $stmt_get->get_result()->fetch_assoc();
$stmt_get->close();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Edit Profil - SCRS PMU</title>
    
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
        .neo-brand { font-size: 1.5rem; font-weight: 900; letter-spacing: 2px; text-transform: uppercase; }

        .main-content {
            flex: 1;
            padding: 2rem 20px;
            max-width: 600px;
            margin: 0 auto;
            width: 100%;
        }

        .neo-card {
            background-color: var(--white);
            border: var(--border-thick);
            box-shadow: var(--shadow-solid);
            padding: 30px;
        }

        .card-header-title {
            font-size: 1.5rem;
            font-weight: 900;
            text-transform: uppercase;
            border-bottom: 3px solid var(--black);
            padding-bottom: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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

        .form-control {
            border: 3px solid var(--black);
            padding: 12px;
            font-weight: 700;
            background-color: var(--bg-color);
            outline: none;
            width: 100%;
        }
        .form-control:focus { background-color: var(--white); box-shadow: 3px 3px 0px var(--black); }

        .neo-btn {
            background-color: var(--yellow);
            border: 3px solid var(--black);
            box-shadow: 4px 4px 0px var(--black);
            font-weight: 900;
            text-transform: uppercase;
            padding: 12px 20px;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            text-align: center;
        }
        .neo-btn:hover { transform: translate(-2px, -2px); box-shadow: 6px 6px 0px var(--black); }
        .neo-btn:active { transform: translate(4px, 4px); box-shadow: var(--shadow-active); }
        .btn-green { background-color: var(--green); }

        .neo-alert {
            border: var(--border-thick); box-shadow: 4px 4px 0px var(--black);
            padding: 15px 20px; font-weight: 800; margin-bottom: 25px; text-transform: uppercase;
        }
        .alert-success { background-color: var(--green); }
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

        @media (max-width: 768px) {
            .main-content { padding: 1rem 10px; }
            .neo-card { padding: 20px 15px; }
            .neo-brand { font-size: 1.2rem; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <header class="neo-navbar">
        <div class="neo-brand">SCRS PMU</div>
        <a href="<?php echo ($role === 'provider') ? 'provider_dashboard.php' : 'dashboard.php'; ?>" class="neo-btn" style="width: auto; padding: 6px 14px; font-size: 0.85rem;">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </header>

    <!-- KANDUNGAN UTAMA -->
    <main class="main-content">
        <?php echo $message; ?>

        <div class="neo-card">
            <div class="card-header-title">
                <i class="bi bi-gear-fill text-primary"></i> Kemaskini Profil
            </div>

            <form action="" method="POST">
                <div class="form-group">
                    <label class="form-label">Nama Pengguna (Username)</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['username']); ?>" disabled style="background-color: #eee; cursor: not-allowed;">
                </div>

                <div class="form-group">
                    <label class="form-label">E-mel</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user_data['email']); ?>" disabled style="background-color: #eee; cursor: not-allowed;">
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Penuh</label>
                    <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Nombor Telefon</label>
                    <input type="text" class="form-control" name="phone_no" value="<?php echo htmlspecialchars($user_data['phone_no']); ?>" required>
                </div>

                <div style="border-top: 2px dashed var(--black); margin: 25px 0 20px 0; padding-top: 15px;">
                    <p style="font-weight: 900; text-transform: uppercase; margin-bottom: 15px; font-size: 0.9rem; color: #333;">
                        Tukar Kata Laluan (Isi Jika Mahu Tukar)
                    </p>
                    
                    <div class="form-group">
                        <label class="form-label">Kata Laluan Baharu</label>
                        <input type="password" class="form-control" name="new_password" placeholder="Lebih daripada 8 aksara">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Sahkan Kata Laluan Baharu</label>
                        <input type="password" class="form-control" name="confirm_password" placeholder="Ulang kata laluan baharu">
                    </div>
                </div>

                <button type="submit" name="update_profile" class="neo-btn btn-green" style="margin-top: 10px;">
                    <i class="bi bi-check-circle-fill me-1"></i> Simpan Perubahan Profil
                </button>
            </form>
        </div>
    </main>

    <!-- FOOTER -->
    <footer>
        &copy; <?php echo date("Y"); ?> SCRS PMU. SISTEM SEWAAN KERETA.
    </footer>

</body>
</html>
