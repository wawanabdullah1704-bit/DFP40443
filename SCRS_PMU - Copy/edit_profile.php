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

    // Handle profile picture upload
    $pic_update_sql = "";
    $pic_update_val = null;
    if (!empty($_FILES['profile_picture']['name'])) {
        $targetDir = "uploads/profiles/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $picName = basename($_FILES["profile_picture"]["name"]);
        $newPicName = $role . "_" . $user_id . "_pic_" . time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $picName);
        $targetPic = $targetDir . $newPicName;

        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetPic)) {
            $pic_update_val = $targetPic;
        } else {
            $message = "<div class='neo-alert alert-danger'>Ralat: Gagal memuat naik gambar profil.</div>";
        }
    }

    if (!empty($new_password)) {
        if (strlen($new_password) <= 8) {
            $message = "<div class='neo-alert alert-danger'>Ralat: Kata laluan mestilah lebih daripada 8 aksara!</div>";
        } else if (!preg_match('/[A-Z]/', $new_password) || !preg_match('/[a-z]/', $new_password) || !preg_match('/[0-9]/', $new_password) || !preg_match('/[^A-Za-z0-9]/', $new_password)) {
            $message = "<div class='neo-alert alert-danger'>Ralat: Kata laluan mesti mengandungi huruf besar, huruf kecil, nombor, dan simbol!</div>";
        } else if ($new_password !== $confirm_password) {
            $message = "<div class='neo-alert alert-danger'>Ralat: Pengesahan kata laluan tidak sepadan!</div>";
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            if ($pic_update_val) {
                $sql_up = "UPDATE $table SET full_name = ?, phone_no = ?, password = ?, profile_picture = ? WHERE id = ?";
                $stmt_up = $conn->prepare($sql_up);
                $stmt_up->bind_param("ssssi", $full_name, $phone_no, $hashed, $pic_update_val, $user_id);
            } else {
                $sql_up = "UPDATE $table SET full_name = ?, phone_no = ?, password = ? WHERE id = ?";
                $stmt_up = $conn->prepare($sql_up);
                $stmt_up->bind_param("sssi", $full_name, $phone_no, $hashed, $user_id);
            }
            if ($stmt_up->execute()) {
                $message = "<div class='neo-alert alert-success'>Berjaya: Maklumat profil dan kata laluan telah dikemaskini!</div>";
            } else {
                $message = "<div class='neo-alert alert-danger'>Ralat pangkalan data: " . $stmt_up->error . "</div>";
            }
            $stmt_up->close();
        }
    } else {
        if ($pic_update_val) {
            $sql_up = "UPDATE $table SET full_name = ?, phone_no = ?, profile_picture = ? WHERE id = ?";
            $stmt_up = $conn->prepare($sql_up);
            $stmt_up->bind_param("sssi", $full_name, $phone_no, $pic_update_val, $user_id);
        } else {
            $sql_up = "UPDATE $table SET full_name = ?, phone_no = ? WHERE id = ?";
            $stmt_up = $conn->prepare($sql_up);
            $stmt_up->bind_param("ssi", $full_name, $phone_no, $user_id);
        }
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

$current_pic = $user_data['profile_picture'] ?? '';
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

        /* PROFILE PIC SECTION */
        .profile-pic-area {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
            padding: 20px;
            border: 3px dashed var(--black);
            background: var(--bg-color);
            cursor: pointer;
            transition: var(--transition);
        }
        .profile-pic-area:hover { background: #ebebeb; }

        .profile-avatar {
            width: 110px;
            height: 110px;
            border: 4px solid var(--black);
            box-shadow: 4px 4px 0px var(--black);
            object-fit: cover;
            display: block;
        }
        .profile-avatar-placeholder {
            width: 110px;
            height: 110px;
            border: 4px solid var(--black);
            box-shadow: 4px 4px 0px var(--black);
            background: var(--yellow);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
        }
        .profile-pic-label {
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.8rem;
            color: #555;
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

        .input-wrapper { position: relative; display: flex; align-items: center; }
        .password-toggle-btn {
            position: absolute; right: 12px; cursor: pointer;
            font-size: 1.2rem; color: var(--black); background: none; border: none;
        }

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
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
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

            <form action="" method="POST" enctype="multipart/form-data">

                <!-- PROFILE PICTURE SECTION -->
                <label for="profile_picture" style="display:block; cursor:pointer;">
                    <div class="profile-pic-area" id="picArea">
                        <?php if (!empty($current_pic) && file_exists($current_pic)): ?>
                            <img src="<?php echo htmlspecialchars($current_pic); ?>" class="profile-avatar" id="avatarPreview" alt="Gambar Profil">
                        <?php else: ?>
                            <div class="profile-avatar-placeholder" id="avatarPlaceholder">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <img src="" class="profile-avatar" id="avatarPreview" alt="Gambar Profil" style="display:none;">
                        <?php endif; ?>
                        <span class="profile-pic-label"><i class="bi bi-camera-fill me-1"></i> Klik untuk tukar gambar profil</span>
                    </div>
                </label>
                <input type="file" id="profile_picture" name="profile_picture" accept=".jpg,.jpeg,.png,.gif" style="display:none;">

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
                        <div class="input-wrapper">
                            <input type="password" class="form-control" name="new_password" id="new_password" placeholder="Lebih daripada 8 aksara">
                            <button type="button" class="password-toggle-btn" id="toggleNewPw">
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

    <script>
        // Profile Picture Live Preview
        const picInput = document.getElementById('profile_picture');
        const avatarPreview = document.getElementById('avatarPreview');
        const avatarPlaceholder = document.getElementById('avatarPlaceholder');

        if (picInput) {
            picInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        avatarPreview.src = e.target.result;
                        avatarPreview.style.display = 'block';
                        if (avatarPlaceholder) avatarPlaceholder.style.display = 'none';
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }

        // Password Toggle
        const toggleNewPw = document.getElementById('toggleNewPw');
        const newPwInput  = document.getElementById('new_password');

        if (toggleNewPw && newPwInput) {
            toggleNewPw.addEventListener('click', function() {
                const type = newPwInput.getAttribute('type') === 'password' ? 'text' : 'password';
                newPwInput.setAttribute('type', type);
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

        if (newPwInput) {
            newPwInput.addEventListener('input', function() {
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
    </script>
</body>
</html>
