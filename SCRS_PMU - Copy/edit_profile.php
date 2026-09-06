<?php
session_start();
require 'db.php';

if (!isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}

$role = $_SESSION['role'];
$username = $_SESSION['username'];
$message = "";

// Ambil maklumat pengguna mengikut peranan
if ($role === 'student') {
    $user_id = $_SESSION['student_id'];
    $table = 'students';
    $return_url = 'dashboard.php';
} else if ($role === 'provider') {
    $user_id = $_SESSION['provider_id'];
    $table = 'providers';
    $return_url = 'provider_dashboard.php';
} else if ($role === 'admin') {
    $user_id = $_SESSION['admin_id'];
    $table = 'admins';
    $return_url = 'admin_dashboard.php';
} else if ($role === 'jhepp') {
    $user_id = $_SESSION['jhepp_id'];
    $table = 'jhepp';
    $return_url = 'jhepp_dashboard.php';
} else {
    header("Location: index.php");
    exit();
}

// Prosess Kemaskini
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $full_name = htmlspecialchars($_POST['full_name']);
    $phone_no = isset($_POST['phone_no']) ? htmlspecialchars($_POST['phone_no']) : null;
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Handle profile picture upload (if supported)
    $pic_update_val = null;
    if (($role === 'student' || $role === 'provider') && !empty($_FILES['profile_picture']['name'])) {
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
        if (strlen($new_password) < 8) {
            $message = "<div class='neo-alert alert-danger'>Ralat: Kata laluan mestilah sekurang-kurangnya 8 aksara (8 aksara atau lebih)!</div>";
        } else if (!preg_match('/[A-Z]/', $new_password) || !preg_match('/[a-z]/', $new_password) || !preg_match('/[0-9]/', $new_password) || !preg_match('/[^A-Za-z0-9]/', $new_password)) {
            $message = "<div class='neo-alert alert-danger'>Ralat: Kata laluan mesti mengandungi huruf besar, huruf kecil, nombor, dan simbol!</div>";
        } else if ($new_password !== $confirm_password) {
            $message = "<div class='neo-alert alert-danger'>Ralat: Pengesahan kata laluan tidak sepadan!</div>";
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            if ($role === 'admin' || $role === 'jhepp') {
                $sql_up = "UPDATE $table SET full_name = ?, password = ? WHERE id = ?";
                $stmt_up = $conn->prepare($sql_up);
                $stmt_up->bind_param("ssi", $full_name, $hashed, $user_id);
            } else {
                if ($pic_update_val) {
                    $sql_up = "UPDATE $table SET full_name = ?, phone_no = ?, password = ?, profile_picture = ? WHERE id = ?";
                    $stmt_up = $conn->prepare($sql_up);
                    $stmt_up->bind_param("ssssi", $full_name, $phone_no, $hashed, $pic_update_val, $user_id);
                } else {
                    $sql_up = "UPDATE $table SET full_name = ?, phone_no = ?, password = ? WHERE id = ?";
                    $stmt_up = $conn->prepare($sql_up);
                    $stmt_up->bind_param("sssi", $full_name, $phone_no, $hashed, $user_id);
                }
            }
            if ($stmt_up->execute()) {
                $_SESSION['full_name'] = $full_name;
                $message = "<div class='neo-alert alert-success'>Berjaya: Maklumat profil dan kata laluan telah dikemaskini!</div>";
            } else {
                $message = "<div class='neo-alert alert-danger'>Ralat pangkalan data: " . $stmt_up->error . "</div>";
            }
            $stmt_up->close();
        }
    } else {
        if ($role === 'admin' || $role === 'jhepp') {
            $sql_up = "UPDATE $table SET full_name = ? WHERE id = ?";
            $stmt_up = $conn->prepare($sql_up);
            $stmt_up->bind_param("si", $full_name, $user_id);
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
        }
        if ($stmt_up->execute()) {
            $_SESSION['full_name'] = $full_name;
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
    <!-- Master Neo-Brutalism CSS -->
    <link rel="stylesheet" href="neo-style.css">

    <style>
        .main-content {
            flex: 1;
            padding: 2rem 16px;
            max-width: 620px;
            margin: 0 auto;
            width: 100%;
        }

        .card-header-title {
            font-size: 1.35rem;
            font-weight: 900;
            text-transform: uppercase;
            border-bottom: 2.5px solid var(--black);
            padding-bottom: 10px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* PROFILE PIC SECTION */
        .profile-pic-area {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            margin-bottom: 22px;
            padding: 18px;
            border: 2.5px dashed var(--black);
            border-radius: var(--radius-lg);
            background: var(--bg-color);
            cursor: pointer;
            transition: var(--transition);
        }
        .profile-pic-area:hover { background: #eee; }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border: var(--border-thick);
            border-radius: var(--radius-full);
            box-shadow: var(--shadow-sm);
            object-fit: cover;
            display: block;
        }
        .profile-avatar-placeholder {
            width: 100px;
            height: 100px;
            border: var(--border-thick);
            border-radius: var(--radius-full);
            box-shadow: var(--shadow-sm);
            background: var(--yellow);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.6rem;
        }
        .profile-pic-label {
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.775rem;
            color: #555;
        }

        .input-wrapper { position: relative; display: flex; align-items: center; }
        .password-toggle-btn {
            position: absolute; right: 12px; cursor: pointer;
            font-size: 1.2rem; color: var(--black); background: none; border: none;
            padding: 4px;
        }

        @media (max-width: 768px) {
            .main-content { padding: 1rem 12px; }
        }
    </style>
</head>
<body>

    <header class="neo-navbar">
        <a href="<?php echo $return_url; ?>" class="neo-brand"><i class="bi bi-car-front-fill me-1"></i>SCRS <span>PMU</span></a>
        <a href="<?php echo $return_url; ?>" class="neo-btn btn-sm btn-yellow" style="width: auto;">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </header>

    <!-- KANDUNGAN UTAMA -->
    <main class="main-content">
        <?php echo $message; ?>

        <div class="neo-card">
            <div class="card-header-title">
                <i class="bi bi-person-gear text-primary"></i> Kemaskini Profil Pengguna (<?php echo strtoupper($role); ?>)
            </div>
            <p style="font-weight: 700; color: #555; font-size: 0.9rem; margin-bottom: 20px; text-align: center; border-bottom: 2px dashed #ddd; padding-bottom: 12px; line-height: 1.4;">
                <strong>Panduan:</strong> Kemaskini maklumat peribadi anda di bawah dan tukar kata laluan baharu jika perlu.
            </p>

            <form action="" method="POST" enctype="multipart/form-data">

                <?php if ($role === 'student' || $role === 'provider'): ?>
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
                <?php endif; ?>

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

                <?php if (isset($user_data['phone_no'])): ?>
                <div class="form-group">
                    <label class="form-label">Nombor Telefon</label>
                    <input type="text" class="form-control" name="phone_no" value="<?php echo htmlspecialchars($user_data['phone_no']); ?>" required>
                </div>
                <?php endif; ?>

                <div style="border-top: 2px dashed var(--black); margin: 25px 0 20px 0; padding-top: 15px;">
                    <p style="font-weight: 900; text-transform: uppercase; margin-bottom: 15px; font-size: 0.9rem; color: #333;">
                        Tukar Kata Laluan (Isi Jika Mahu Tukar)
                    </p>
                    
                    <div class="form-group">
                        <label class="form-label">Kata Laluan Baharu</label>
                        <div class="input-wrapper">
                            <input type="password" class="form-control" name="new_password" id="new_password" placeholder="8 aksara atau lebih" minlength="8">
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
                        <label class="form-label">Sahkan Kata Laluan Baharu</label>
                        <input type="password" class="form-control" name="confirm_password" placeholder="Ulang kata laluan baharu" minlength="8">
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

        if (newPwInput) {
            newPwInput.addEventListener('input', function() {
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
    </script>
</body>
</html>
