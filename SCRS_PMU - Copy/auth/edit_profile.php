<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/notifications.php';

if (!isset($_SESSION['role'])) {
    header("Location: ../index.php");
    exit();
}

$role = $_SESSION['role'];
$username = $_SESSION['username'];
$message = "";

// Ambil maklumat pengguna mengikut peranan
if ($role === 'student') {
    $user_id = $_SESSION['student_id'];
    $table = 'students';
    $return_url = '../student/dashboard.php';
} else if ($role === 'provider') {
    $user_id = $_SESSION['provider_id'];
    $table = 'providers';
    $return_url = '../provider/provider_dashboard.php';
} else if ($role === 'admin') {
    $user_id = $_SESSION['admin_id'];
    $table = 'admins';
    $return_url = '../admin/admin_dashboard.php';
} else if ($role === 'jhepp') {
    $user_id = $_SESSION['jhepp_id'];
    $table = 'jhepp';
    $return_url = '../jhepp/jhepp_dashboard.php';
} else {
    header("Location: ../index.php");
    exit();
}

// Ambil data profil sedia ada sebelum proses borang
$sql_cur = "SELECT * FROM $table WHERE id = ?";
$stmt_cur = $conn->prepare($sql_cur);
$stmt_cur->bind_param("i", $user_id);
$stmt_cur->execute();
$current_db_row = $stmt_cur->get_result()->fetch_assoc();
$stmt_cur->close();
$old_pic_path = $current_db_row['profile_picture'] ?? null;

// --- 1. PROSES PADAM GAMBAR PROFIL SAHAJA ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_profile_picture'])) {
    if (!empty($old_pic_path) && file_exists(__DIR__ . '/../' . $old_pic_path)) {
        @unlink(__DIR__ . '/../' . $old_pic_path);
    }
    $sql_del = "UPDATE $table SET profile_picture = NULL WHERE id = ?";
    $stmt_del = $conn->prepare($sql_del);
    $stmt_del->bind_param("i", $user_id);
    if ($stmt_del->execute()) {
        $message = "<div class='neo-alert alert-success'><i class='bi bi-check-circle-fill me-2'></i>Gambar profil berjaya dipadamkan! Profil anda kini menggunakan inisial nama.</div>";
    } else {
        $message = "<div class='neo-alert alert-danger'>Ralat pangkalan data: " . $stmt_del->error . "</div>";
    }
    $stmt_del->close();
}

// --- 2. PROSES KEMASKINI MAKLUMAT PROFIL ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $full_name = htmlspecialchars($_POST['full_name']);
    $phone_no = isset($_POST['phone_no']) ? htmlspecialchars($_POST['phone_no']) : null;
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $remove_pic = isset($_POST['remove_picture']) && $_POST['remove_picture'] === '1';

    // Handle profile picture (Crop Base64 atau fail terus)
    $pic_update_val = null;
    $pic_mode = 'keep'; // 'new', 'delete', 'keep'

    // 1. Jika pengguna telah memotong gambar melalui Crop Modal (Base64)
    if (!empty($_POST['cropped_image_data']) && strpos($_POST['cropped_image_data'], 'data:image') === 0) {
        $targetDir = "../uploads/profiles/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $base64_str = $_POST['cropped_image_data'];
        $parts = explode(',', $base64_str);
        if (isset($parts[1])) {
            $img_data = base64_decode($parts[1]);
            $newPicName = $role . "_" . $user_id . "_pic_" . time() . ".png";
            $targetPic = $targetDir . $newPicName;

            if (file_put_contents($targetPic, $img_data)) {
                $pic_update_val = "uploads/profiles/" . $newPicName;
                $pic_mode = 'new';
                if (!empty($old_pic_path) && file_exists(__DIR__ . '/../' . $old_pic_path)) {
                    @unlink(__DIR__ . '/../' . $old_pic_path);
                }
            } else {
                $message = "<div class='neo-alert alert-danger'>Ralat: Gagal menyimpan gambar profil yang dipotong.</div>";
            }
        }
    } 
    // 2. Fallback muat naik fail standard
    elseif (!empty($_FILES['profile_picture']['name'])) {
        $targetDir = "../uploads/profiles/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $picName = basename($_FILES["profile_picture"]["name"]);
        $newPicName = $role . "_" . $user_id . "_pic_" . time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $picName);
        $targetPic = $targetDir . $newPicName;

        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetPic)) {
            $pic_update_val = "uploads/profiles/" . $newPicName;
            $pic_mode = 'new';
            if (!empty($old_pic_path) && file_exists(__DIR__ . '/../' . $old_pic_path)) {
                @unlink(__DIR__ . '/../' . $old_pic_path);
            }
        } else {
            $message = "<div class='neo-alert alert-danger'>Ralat: Gagal memuat naik gambar profil.</div>";
        }
    } 
    // 3. Permintaan padam gambar
    elseif ($remove_pic) {
        $pic_mode = 'delete';
        if (!empty($old_pic_path) && file_exists(__DIR__ . '/../' . $old_pic_path)) {
            @unlink(__DIR__ . '/../' . $old_pic_path);
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
                if ($pic_mode === 'new') {
                    $sql_up = "UPDATE $table SET full_name = ?, password = ?, profile_picture = ? WHERE id = ?";
                    $stmt_up = $conn->prepare($sql_up);
                    $stmt_up->bind_param("sssi", $full_name, $hashed, $pic_update_val, $user_id);
                } elseif ($pic_mode === 'delete') {
                    $sql_up = "UPDATE $table SET full_name = ?, password = ?, profile_picture = NULL WHERE id = ?";
                    $stmt_up = $conn->prepare($sql_up);
                    $stmt_up->bind_param("ssi", $full_name, $hashed, $user_id);
                } else {
                    $sql_up = "UPDATE $table SET full_name = ?, password = ? WHERE id = ?";
                    $stmt_up = $conn->prepare($sql_up);
                    $stmt_up->bind_param("ssi", $full_name, $hashed, $user_id);
                }
            } else {
                if ($pic_mode === 'new') {
                    $sql_up = "UPDATE $table SET full_name = ?, phone_no = ?, password = ?, profile_picture = ? WHERE id = ?";
                    $stmt_up = $conn->prepare($sql_up);
                    $stmt_up->bind_param("ssssi", $full_name, $phone_no, $hashed, $pic_update_val, $user_id);
                } elseif ($pic_mode === 'delete') {
                    $sql_up = "UPDATE $table SET full_name = ?, phone_no = ?, password = ?, profile_picture = NULL WHERE id = ?";
                    $stmt_up = $conn->prepare($sql_up);
                    $stmt_up->bind_param("sssi", $full_name, $phone_no, $hashed, $user_id);
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
            if ($pic_mode === 'new') {
                $sql_up = "UPDATE $table SET full_name = ?, profile_picture = ? WHERE id = ?";
                $stmt_up = $conn->prepare($sql_up);
                $stmt_up->bind_param("ssi", $full_name, $pic_update_val, $user_id);
            } elseif ($pic_mode === 'delete') {
                $sql_up = "UPDATE $table SET full_name = ?, profile_picture = NULL WHERE id = ?";
                $stmt_up = $conn->prepare($sql_up);
                $stmt_up->bind_param("si", $full_name, $user_id);
            } else {
                $sql_up = "UPDATE $table SET full_name = ? WHERE id = ?";
                $stmt_up = $conn->prepare($sql_up);
                $stmt_up->bind_param("si", $full_name, $user_id);
            }
        } else {
            if ($pic_mode === 'new') {
                $sql_up = "UPDATE $table SET full_name = ?, phone_no = ?, profile_picture = ? WHERE id = ?";
                $stmt_up = $conn->prepare($sql_up);
                $stmt_up->bind_param("sssi", $full_name, $phone_no, $pic_update_val, $user_id);
            } elseif ($pic_mode === 'delete') {
                $sql_up = "UPDATE $table SET full_name = ?, phone_no = ?, profile_picture = NULL WHERE id = ?";
                $stmt_up = $conn->prepare($sql_up);
                $stmt_up->bind_param("ssi", $full_name, $phone_no, $user_id);
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

// Ambil data profil terkini semula selepas sebarang kemaskini
$sql_get = "SELECT * FROM $table WHERE id = ?";
$stmt_get = $conn->prepare($sql_get);
$stmt_get->bind_param("i", $user_id);
$stmt_get->execute();
$user_data = $stmt_get->get_result()->fetch_assoc();
$stmt_get->close();

$current_pic = $user_data['profile_picture'] ?? '';
$has_active_pic = (!empty($current_pic) && file_exists(__DIR__ . '/../' . $current_pic));
$pic_src = $has_active_pic ? ('../' . ltrim($current_pic, '/')) : '';
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
    <link rel="stylesheet" href="../assets/css/neo-style.css">

    <!-- Cropper.js CSS & JS -->
    <link rel="stylesheet" href="../assets/vendor/cropperjs/cropper.min.css">
    <script src="../assets/vendor/cropperjs/cropper.min.js"></script>

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

        /* AVATAR MODERN CLEAN NEO-BRUTALIST */
        .avatar-section-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 2px dashed #ddd;
        }

        .avatar-circle-wrapper {
            position: relative;
            width: 120px;
            height: 120px;
            margin-bottom: 14px;
            cursor: pointer;
            transition: var(--transition);
        }
        .avatar-circle-wrapper:hover {
            transform: scale(1.03);
        }

        .avatar-main-img {
            width: 120px;
            height: 120px;
            border: 3.5px solid var(--black);
            border-radius: 50%;
            box-shadow: 4px 4px 0px var(--black);
            object-fit: cover;
            display: block;
            background-color: var(--white);
        }

        .avatar-main-placeholder {
            width: 120px;
            height: 120px;
            border: 3.5px solid var(--black);
            border-radius: 50%;
            box-shadow: 4px 4px 0px var(--black);
            background-color: var(--yellow);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.8rem;
            font-weight: 900;
            color: var(--black);
            text-transform: uppercase;
            user-select: none;
        }

        .avatar-camera-badge {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: var(--white);
            border: 2.5px solid var(--black);
            box-shadow: 2px 2px 0px var(--black);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.05rem;
            color: var(--black);
            cursor: pointer;
            transition: var(--transition);
        }
        .avatar-circle-wrapper:hover .avatar-camera-badge {
            background-color: var(--yellow);
            transform: scale(1.1);
        }

        .avatar-btns-group {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Cropper modal circle mask guide */
        .cropper-view-box,
        .cropper-face {
            border-radius: 50%;
        }
        .cropper-view-box {
            outline: 2px solid var(--yellow);
            outline-color: rgba(255, 222, 89, 0.95);
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
        <a href="<?php echo $return_url; ?>" class="neo-brand">SCRS PMU</a>
        <?php render_navbar_actions($conn, $role, $user_id, $username, '../'); ?>
    </header>

    <!-- KANDUNGAN UTAMA -->
    <main class="main-content">
        <div style="margin-bottom: 14px;">
            <a href="<?php echo $return_url; ?>" class="neo-btn btn-yellow btn-arrow-back" title="Kembali" aria-label="Kembali">
                <i class="bi bi-arrow-left"></i>
            </a>
        </div>
        <?php echo $message; ?>

        <div class="neo-card">
            <div class="card-header-title">
                <i class="bi bi-person-gear text-primary"></i> Kemaskini Profil Pengguna (<?php echo strtoupper($role); ?>)
            </div>
            <p style="font-weight: 700; color: #555; font-size: 0.9rem; margin-bottom: 20px; text-align: center; border-bottom: 2px dashed #ddd; padding-bottom: 12px; line-height: 1.4;">
                <strong>Panduan:</strong> Kemaskini maklumat peribadi anda di bawah dan tukar kata laluan baharu jika perlu.
            </p>

            <form action="" method="POST" enctype="multipart/form-data">

                <!-- PROFILE PICTURE SECTION (KEMAS & KONSISTEN) -->
                <div class="avatar-section-wrapper">
                    <div class="avatar-circle-wrapper" id="avatarClickTrigger" onclick="openFileSelector()" title="Klik untuk pilih & potong gambar profil">
                        <?php if ($has_active_pic): ?>
                            <img src="<?php echo htmlspecialchars($pic_src); ?>" class="avatar-main-img" id="avatarPreview" alt="Gambar Profil">
                            <div class="avatar-main-placeholder" id="avatarPlaceholder" style="display:none;">
                                <?php echo strtoupper(substr($username, 0, 1)); ?>
                            </div>
                        <?php else: ?>
                            <div class="avatar-main-placeholder" id="avatarPlaceholder">
                                <?php echo strtoupper(substr($username, 0, 1)); ?>
                            </div>
                            <img src="" class="avatar-main-img" id="avatarPreview" alt="Gambar Profil" style="display:none;">
                        <?php endif; ?>
                        
                        <div class="avatar-camera-badge" title="Pilih & Potong Gambar">
                            <i class="bi bi-camera-fill"></i>
                        </div>
                    </div>

                    <!-- Hidden file input & hidden cropped base64 data input -->
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/webp,image/jpg" style="display:none;">
                    <input type="hidden" name="cropped_image_data" id="croppedImageData" value="">

                    <!-- Butang Tindakan -->
                    <div class="avatar-btns-group">
                        <button type="button" class="neo-btn btn-blue" onclick="openFileSelector()" style="padding: 7px 16px; font-size: 0.82rem;">
                            <i class="bi bi-crop me-1"></i> <?php echo $has_active_pic ? 'Tukar & Potong' : 'Pilih & Potong Gambar'; ?>
                        </button>

                        <?php if ($has_active_pic): ?>
                            <button type="submit" name="delete_profile_picture" value="1" formnovalidate class="neo-btn btn-pink" style="padding: 7px 16px; font-size: 0.82rem;" onclick="return confirm('Adakah anda pasti mahu memadamkan gambar profil ini dan kembali menggunakan inisial nama?');">
                                <i class="bi bi-trash3-fill me-1"></i> Padam Gambar
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Notis gambar baru sedia disimpan -->
                    <div id="croppedAlertBadge" style="display:none; margin-top: 10px;">
                        <span style="display: inline-flex; align-items: center; gap: 6px; background: var(--green); color: var(--black); font-size: 0.78rem; padding: 5px 12px; border: 2px solid var(--black); border-radius: var(--radius-sm); font-weight: 800; box-shadow: 2px 2px 0px var(--black);">
                            <i class="bi bi-check-circle-fill"></i> Gambar siap dipotong! Sila tekan "Simpan Perubahan Profil" di bawah.
                        </span>
                    </div>

                    <div style="font-size: 0.75rem; font-weight: 700; color: #666; margin-top: 8px;">
                        Format: JPG, PNG, WEBP (Boleh dipotong & dilaras kepada bentuk bulat)
                    </div>
                </div>

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

    <!-- MODAL CROP GAMBAR PROFIL (MODERN NEO-BRUTALIST) -->
    <div class="neo-modal-overlay" id="cropModalOverlay" style="z-index: 3500; display: none;" onclick="closeCropModalOutside(event)">
        <div class="neo-modal" style="max-width: 520px; width: 95%; max-height: 94vh; overflow-y: auto;" onclick="event.stopPropagation()">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid var(--black); padding-bottom: 12px; margin-bottom: 15px;">
                <h3 class="modal-title" style="font-weight: 900; text-transform: uppercase; font-size: 1.15rem; margin: 0;">
                    <i class="bi bi-crop text-primary me-2"></i> Potong Gambar Profil
                </h3>
                <button type="button" class="close-btn" onclick="closeCropModal()">&times;</button>
            </div>
            
            <div class="modal-body" style="padding: 0;">
                <p style="font-size: 0.85rem; font-weight: 700; color: #555; margin-bottom: 12px;">
                    Laraskan saiz dan kedudukan gambar dalam bulatan sebelum digunakan:
                </p>

                <!-- Container untuk Cropper Image -->
                <div style="width: 100%; height: 320px; background: #1a1a1a; border: 2.5px solid var(--black); border-radius: var(--radius-sm); overflow: hidden; margin-bottom: 14px; position: relative;">
                    <img id="imageToCrop" src="" alt="Gambar untuk dipotong" style="max-width: 100%; display: block;">
                </div>

                <!-- Toolbar Kawalan (Zoom, Rotate, Reset) -->
                <div style="display: flex; gap: 6px; justify-content: center; flex-wrap: wrap; margin-bottom: 16px;">
                    <button type="button" class="neo-btn" style="padding: 5px 10px; font-size: 0.8rem;" onclick="cropperZoom(0.1)" title="Zoom Masuk">
                        <i class="bi bi-zoom-in"></i> Zoom +
                    </button>
                    <button type="button" class="neo-btn" style="padding: 5px 10px; font-size: 0.8rem;" onclick="cropperZoom(-0.1)" title="Zoom Keluar">
                        <i class="bi bi-zoom-out"></i> Zoom -
                    </button>
                    <button type="button" class="neo-btn" style="padding: 5px 10px; font-size: 0.8rem;" onclick="cropperRotate(-90)" title="Pusing Kiri 90°">
                        <i class="bi bi-arrow-counterclockwise"></i> 90°
                    </button>
                    <button type="button" class="neo-btn" style="padding: 5px 10px; font-size: 0.8rem;" onclick="cropperRotate(90)" title="Pusing Kanan 90°">
                        <i class="bi bi-arrow-clockwise"></i> 90°
                    </button>
                    <button type="button" class="neo-btn" style="padding: 5px 10px; font-size: 0.8rem;" onclick="cropperReset()" title="Reset Potongan">
                        <i class="bi bi-arrow-repeat"></i> Reset
                    </button>
                </div>

                <!-- Butang Batal & Gunakan -->
                <div style="display: flex; gap: 10px; justify-content: flex-end; border-top: 2px dashed #ddd; padding-top: 14px;">
                    <button type="button" class="neo-btn" style="background: #e2e8f0;" onclick="closeCropModal()">
                        Batal
                    </button>
                    <button type="button" class="neo-btn btn-green" id="btnApplyCrop" onclick="applyCrop()">
                        <i class="bi bi-check2-circle me-1"></i> Potong & Gunakan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        &copy; <?php echo date("Y"); ?> SCRS PMU. SISTEM SEWAAN KERETA.
    </footer>

    <script>
        // PENGURUSAN CROP IMAGE & AVATAR
        let cropper = null;
        const cropModal = document.getElementById('cropModalOverlay');
        const imageToCrop = document.getElementById('imageToCrop');
        const fileInput = document.getElementById('profile_picture');
        const croppedInput = document.getElementById('croppedImageData');
        const avatarPreview = document.getElementById('avatarPreview');
        const avatarPlaceholder = document.getElementById('avatarPlaceholder');
        const croppedAlertBadge = document.getElementById('croppedAlertBadge');

        function openFileSelector() {
            if (fileInput) fileInput.click();
        }

        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                const files = e.target.files;
                if (files && files.length > 0) {
                    const file = files[0];
                    if (!file.type.startsWith('image/')) {
                        alert('Sila pilih fail gambar (JPG, PNG, atau WEBP).');
                        fileInput.value = '';
                        return;
                    }
                    const reader = new FileReader();
                    reader.onload = function(evt) {
                        imageToCrop.src = evt.target.result;
                        openCropModal();
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        function openCropModal() {
            if (!cropModal) return;
            cropModal.style.display = 'flex';
            cropModal.classList.add('show');
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            setTimeout(() => {
                if (typeof Cropper !== 'undefined') {
                    cropper = new Cropper(imageToCrop, {
                        aspectRatio: 1,
                        viewMode: 1,
                        dragMode: 'move',
                        autoCropArea: 0.9,
                        restore: false,
                        guides: true,
                        center: true,
                        highlight: false,
                        cropBoxMovable: true,
                        cropBoxResizable: true,
                        toggleDragModeOnDblclick: false,
                    });
                }
            }, 180);
        }

        function closeCropModal() {
            if (!cropModal) return;
            cropModal.style.display = 'none';
            cropModal.classList.remove('show');
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            // Jika pengguna belum crop gambar, reset input file
            if (!croppedInput || !croppedInput.value) {
                if (fileInput) fileInput.value = '';
            }
        }

        function closeCropModalOutside(e) {
            if (e.target.id === 'cropModalOverlay') {
                closeCropModal();
            }
        }

        function cropperZoom(delta) {
            if (cropper) cropper.zoom(delta);
        }

        function cropperRotate(deg) {
            if (cropper) cropper.rotate(deg);
        }

        function cropperReset() {
            if (cropper) cropper.reset();
        }

        function applyCrop() {
            if (!cropper) return;
            const canvas = cropper.getCroppedCanvas({
                width: 400,
                height: 400,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });

            if (canvas) {
                const dataUrl = canvas.toDataURL('image/png', 0.92);
                if (croppedInput) croppedInput.value = dataUrl;
                
                // Kemaskini pratonton avatar serta-merta
                if (avatarPreview) {
                    avatarPreview.src = dataUrl;
                    avatarPreview.style.display = 'block';
                }
                if (avatarPlaceholder) avatarPlaceholder.style.display = 'none';
                if (croppedAlertBadge) croppedAlertBadge.style.display = 'block';

                closeCropModal();
            }
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
