<?php
/**
 * SISTEM SEWAAN KERETA POLITEKNIK MUKAH (SCRS PMU)
 * FAIL SEEDER BAHARU (New Database Seeder & Asset Generator)
 * 
 * Ciri-ciri:
 * - 3 Pengguna bagi setiap Peranan: Admin, JHEPP, Provider, Student (12 pengguna)
 * - 3 Kenderaan bagi setiap Provider (9 kenderaan dengan nombor plat & spesifikasi Sarawak)
 * - Penjanaan automatik semua aset imej (Kereta, Kod QR, Dokumen Pengenalan, Geran & Avatar)
 * - Boleh dijalankan melalui CLI (php database/seed_new.php) atau Pelayar Web (http://localhost/.../database/seed_new.php)
 * - Kata Laluan Seragam untuk Semua Akaun: Password123!
 */

require_once __DIR__ . '/../includes/db.php';

// Auto-generate flat document templates if not already generated
if (!file_exists(__DIR__ . '/assets/documents/student_1_ID.png') || !file_exists(__DIR__ . '/assets/documents/receipt_booking_1.png')) {
    require_once __DIR__ . '/create_flat_documents.php';
}

/**
 * Helper Avatar Profil Rata & Bersih (Flat Initial Avatar)
 */
function seed_create_avatar($filepath, $name, $role, $bgColorHex = '#1e3a8a') {
    $dir = dirname($filepath);
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    $w = 300; $h = 300;
    $im = imagecreatetruecolor($w, $h);

    $hex = ltrim($bgColorHex, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    $bg = imagecolorallocate($im, $r, $g, $b);
    $white = imagecolorallocate($im, 255, 255, 255);
    $subtitleColor = imagecolorallocate($im, 240, 240, 240);
    imagefilledrectangle($im, 0, 0, $w, $h, $bg);

    $words = preg_split('/\s+/', trim($name));
    $initials = '';
    foreach ($words as $wrd) {
        $wrd = trim($wrd);
        if ($wrd !== '' && !in_array(strtolower($wrd), ['bin', 'binti', 'anak', 'a/l', 'a/p', 'dr.', 'ts.', 'en.', 'pn.'])) {
            $initials .= strtoupper($wrd[0]);
        }
        if (strlen($initials) >= 2) break;
    }
    if ($initials === '') $initials = strtoupper(substr($name, 0, 2));

    $font = 'C:/Windows/Fonts/arialbd.ttf';
    if (!file_exists($font)) $font = null;

    if ($font && function_exists('imagettftext')) {
        imagettftext($im, 56, 0, 95, 165, $white, $font, $initials);
        imagettftext($im, 12, 0, 85, 225, $subtitleColor, $font, strtoupper($role));
    } else {
        imagestring($im, 5, 125, 130, $initials, $white);
        imagestring($im, 4, 105, 190, strtoupper($role), $subtitleColor);
    }

    imagepng($im, $filepath);
    imagedestroy($im);
}

$is_cli = (php_sapi_name() === 'cli');

function log_status($message, $type = 'info') {
    global $is_cli;
    if ($is_cli) {
        $tag = ($type === 'success') ? "\033[32m[OK]\033[0m " : (($type === 'error') ? "\033[31m[RALAT]\033[0m " : "\033[36m[INFO]\033[0m ");
        echo $tag . $message . PHP_EOL;
    } else {
        $color = ($type === 'success') ? '#10b981' : (($type === 'error') ? '#ef4444' : '#3b82f6');
        $bg = ($type === 'success') ? '#ecfdf5' : (($type === 'error') ? '#fef2f2' : '#eff6ff');
        echo "<div style='margin-bottom:6px; padding:8px 12px; background:{$bg}; border-left:4px solid {$color}; font-family:monospace; font-size:13px;'><strong>" . strtoupper($type) . ":</strong> " . htmlspecialchars($message) . "</div>";
    }
}

if (!$is_cli) {
    echo "<!DOCTYPE html>
    <html lang='ms'>
    <head>
        <meta charset='UTF-8'>
        <title>Seeder Baharu - SCRS PMU</title>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css' rel='stylesheet'>
        <style>
            :root {
                --black: #000000;
                --yellow: #ffde59;
                --primary: #4f46e5;
                --bg: #f5f5f0;
            }
            body {
                background: var(--bg);
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
                padding: 30px 15px;
                color: #111;
            }
            .container {
                max-width: 960px;
                margin: 0 auto;
                background: #fff;
                border: 3px solid var(--black);
                box-shadow: 8px 8px 0px var(--black);
                padding: 30px;
            }
            .header-badge {
                background: var(--yellow);
                display: inline-block;
                padding: 6px 14px;
                font-weight: 900;
                border: 2px solid var(--black);
                box-shadow: 3px 3px 0px var(--black);
                text-transform: uppercase;
                margin-bottom: 10px;
            }
            h1 { margin-top: 0; font-size: 26px; font-weight: 900; }
            .section-title {
                font-size: 18px;
                font-weight: 800;
                border-bottom: 2px solid var(--black);
                padding-bottom: 8px;
                margin-top: 30px;
                margin-bottom: 15px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
                margin-bottom: 20px;
            }
            th, td {
                border: 2px solid var(--black);
                padding: 10px;
                text-align: left;
                font-size: 13px;
            }
            th { background: #f0f0e8; font-weight: 800; }
            .btn-home {
                display: inline-block;
                background: var(--yellow);
                color: var(--black);
                text-decoration: none;
                font-weight: 900;
                padding: 12px 24px;
                border: 3px solid var(--black);
                box-shadow: 4px 4px 0px var(--black);
                font-size: 15px;
                margin-top: 20px;
                transition: transform 0.1s;
            }
            .btn-home:hover {
                transform: translate(-2px, -2px);
                box-shadow: 6px 6px 0px var(--black);
            }
            .thumb-preview {
                width: 60px;
                height: 40px;
                object-fit: cover;
                border: 1px solid #000;
            }
        </style>
    </head>
    <body>
    <div class='container'>
        <div class='header-badge'>SCRS PMU Database Seeder v2.0</div>
        <h1>Penjanaan Semula Pangkalan Data & Aset Imej Baharu</h1>
        <p style='color:#555;'>Proses ini memadamkan data lama, membina 3 akaun bagi setiap peranan, 3 kenderaan bagi setiap penyedia, serta menjana fail imej sebenar (kereta, QR, avatar, & dokumen).</p>
        <hr style='border:1px solid #ddd; margin: 20px 0;'>";
}

log_status("Memulakan proses pangkalan data SCRS PMU...", "info");

// Pastikan direktori muat naik wujud
$directories = [
    __DIR__ . '/../uploads',
    __DIR__ . '/../uploads/cars',
    __DIR__ . '/../uploads/cars/documents',
    __DIR__ . '/../uploads/documents',
    __DIR__ . '/../uploads/qr_codes',
    __DIR__ . '/../uploads/profiles',
    __DIR__ . '/../uploads/receipts',
    __DIR__ . '/../uploads/returns'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}
log_status("Struktur direktori uploads disahkan sedia.", "success");

// Matikan pemeriksaan foreign key sementara untuk truncate/delete
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

$tables = ['notifications', 'bookings', 'cars', 'students', 'providers', 'admins', 'jhepp'];
foreach ($tables as $t) {
    $conn->query("DELETE FROM `{$t}`");
    $conn->query("ALTER TABLE `{$t}` AUTO_INCREMENT = 1");
}
log_status("Semua data lama telah dipadam dan indeks AUTO_INCREMENT telah diset semula ke 1.", "success");

$defaultPasswordHash = password_hash('Password123!', PASSWORD_BCRYPT);

// =========================================================================
// 1. SEED ADMINS (3 User)
// =========================================================================
log_status("Menjana 3 akaun Pentadbir (Admin)...", "info");

$admins = [
    [
        'id' => 1,
        'username' => 'admin1',
        'email' => 'haikal.admin@pmu.edu.my',
        'full_name' => 'Haikal bin Zakaria',
        'color' => '#1e3a8a'
    ],
    [
        'id' => 2,
        'username' => 'admin2',
        'email' => 'fazira.admin@pmu.edu.my',
        'full_name' => 'Fazira binti Shamsudin',
        'color' => '#831843'
    ],
    [
        'id' => 3,
        'username' => 'admin3',
        'email' => 'ariff.admin@pmu.edu.my',
        'full_name' => 'Ariff bin Danial',
        'color' => '#134e4a'
    ]
];

$stmt_adm = $conn->prepare("INSERT INTO admins (id, username, email, full_name, password, profile_picture, created_at) VALUES (?, ?, ?, ?, ?, NULL, NOW())");
foreach ($admins as $adm) {
    $stmt_adm->bind_param("issss", $adm['id'], $adm['username'], $adm['email'], $adm['full_name'], $defaultPasswordHash);
    $stmt_adm->execute();
}
$stmt_adm->close();
log_status("3 Pentadbir berjaya dimasukkan.", "success");

// =========================================================================
// 2. SEED JHEPP (3 User)
// =========================================================================
log_status("Menjana 3 akaun Pegawai JHEPP...", "info");

$jhepp_users = [
    [
        'id' => 1,
        'username' => 'jhepp1',
        'email' => 'khairul.jhepp@pmu.edu.my',
        'full_name' => 'Ts. Dr. Khairul bin Anuar',
        'color' => '#065f46'
    ],
    [
        'id' => 2,
        'username' => 'jhepp2',
        'email' => 'huda.jhepp@pmu.edu.my',
        'full_name' => 'Pn. Nurul Huda binti Salleh',
        'color' => '#701a75'
    ],
    [
        'id' => 3,
        'username' => 'jhepp3',
        'email' => 'azlan.jhepp@pmu.edu.my',
        'full_name' => 'En. Azlan bin Mustapha',
        'color' => '#1e293b'
    ]
];

$stmt_jhp = $conn->prepare("INSERT INTO jhepp (id, username, email, full_name, password, profile_picture, created_at) VALUES (?, ?, ?, ?, ?, NULL, NOW())");
foreach ($jhepp_users as $j) {
    $stmt_jhp->bind_param("issss", $j['id'], $j['username'], $j['email'], $j['full_name'], $defaultPasswordHash);
    $stmt_jhp->execute();
}
$stmt_jhp->close();
log_status("3 Pegawai JHEPP berjaya dimasukkan.", "success");

// =========================================================================
// 3. SEED PROVIDERS (3 User)
// =========================================================================
log_status("Menjana 3 akaun Penyedia Kenderaan (Provider)...", "info");

$providers = [
    [
        'id' => 1,
        'username' => 'provider1',
        'email' => 'safwan.borneo@gmail.com',
        'full_name' => 'Safwan bin Ramli',
        'company' => 'Borneo Mobility Enterprise',
        'phone_no' => '0128761234',
        'no_ic' => '880512-13-5543',
        'color' => '#1e40af',
        'bank' => 'Maybank / DuitNow',
        'account' => '012-8761234'
    ],
    [
        'id' => 2,
        'username' => 'provider2',
        'email' => 'rozita.mukah@gmail.com',
        'full_name' => 'Dayang Rozita binti Abang',
        'company' => 'Mukah Auto Rental Services',
        'phone_no' => '0198123456',
        'no_ic' => '901124-13-6028',
        'color' => '#b45309',
        'bank' => 'CIMB Bank / DuitNow',
        'account' => '019-8123456'
    ],
    [
        'id' => 3,
        'username' => 'provider3',
        'email' => 'alexander.king@gmail.com',
        'full_name' => 'Alexander Anak Jimmy',
        'company' => 'King Car Rental Mukah',
        'phone_no' => '01133445566',
        'no_ic' => '930403-13-7189',
        'color' => '#15803d',
        'bank' => 'Bank Islam / DuitNow',
        'account' => '011-33445566'
    ]
];

$stmt_prov = $conn->prepare("INSERT INTO providers (id, username, email, full_name, phone_no, no_ic, password, ic_file, licence_file, insurance_file, greencard_file, roadtax_file, qr_code_image, status, profile_picture, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved', NULL, NOW())");

foreach ($providers as $p) {
    // Jana Dokumen & Fail Penyedia
    $ic_clean = str_replace('-', '', $p['no_ic']);
    $ic_file = "uploads/documents/{$ic_clean}_IC.png";
    $licence_file = "uploads/documents/{$ic_clean}_Licence.png";
    $ins_file = "uploads/documents/{$ic_clean}_Ins.png";
    $gc_file = "uploads/documents/{$ic_clean}_GC.png";
    $rt_file = "uploads/documents/{$ic_clean}_RT.png";
    $qr_file = "uploads/qr_codes/qr_provider_{$p['id']}.jpg";

    // Salin dokumen rasmi 2D flat kemas bertera air CONTOH
    copy(__DIR__ . "/assets/documents/{$ic_clean}_IC.png", __DIR__ . '/../' . $ic_file);
    copy(__DIR__ . "/assets/documents/{$ic_clean}_Licence.png", __DIR__ . '/../' . $licence_file);
    copy(__DIR__ . "/assets/documents/{$ic_clean}_Ins.png", __DIR__ . '/../' . $ins_file);
    copy(__DIR__ . "/assets/documents/{$ic_clean}_GC.png", __DIR__ . '/../' . $gc_file);
    copy(__DIR__ . "/assets/documents/{$ic_clean}_RT.png", __DIR__ . '/../' . $rt_file);

    $real_qr_asset = __DIR__ . "/assets/qr/qr_provider_{$p['id']}.jpg";
    if (file_exists($real_qr_asset)) {
        copy($real_qr_asset, __DIR__ . '/../' . $qr_file);
    }

    $stmt_prov->bind_param(
        "issssssssssss",
        $p['id'],
        $p['username'],
        $p['email'],
        $p['full_name'],
        $p['phone_no'],
        $p['no_ic'],
        $defaultPasswordHash,
        $ic_file,
        $licence_file,
        $ins_file,
        $gc_file,
        $rt_file,
        $qr_file
    );
    $stmt_prov->execute();
}
$stmt_prov->close();
log_status("3 Penyedia (Provider) berjaya dimasukkan beserta dokumen rasmi & kod QR DuitNow.", "success");

// =========================================================================
// 4. SEED STUDENTS (3 User)
// =========================================================================
log_status("Menjana 3 akaun Pelajar PMU (Student)...", "info");

$students = [
    [
        'id' => 1,
        'username' => 'student1',
        'email' => 'danish.pmu@gmail.com',
        'full_name' => 'Muhammad Danish bin Harun',
        'phone_no' => '0145566778',
        'no_ic' => '040506-13-5671',
        'no_pendaftaran' => '20DIT24F1010',
        'jabatan' => 'Jabatan Teknologi Maklumat & Komunikasi',
        'color' => '#2563eb'
    ],
    [
        'id' => 2,
        'username' => 'student2',
        'email' => 'aina.pmu@gmail.com',
        'full_name' => 'Nur Aina Mardhiah binti Rosli',
        'phone_no' => '0134455667',
        'no_ic' => '040812-13-8902',
        'no_pendaftaran' => '20DAT24F1022',
        'jabatan' => 'Jabatan Perdagangan (Perakaunan)',
        'color' => '#db2777'
    ],
    [
        'id' => 3,
        'username' => 'student3',
        'email' => 'aaron.pmu@gmail.com',
        'full_name' => 'Aaron Lee Jun Kit',
        'phone_no' => '0179988112',
        'no_ic' => '041123-13-3344',
        'no_pendaftaran' => '20DEE24F1055',
        'jabatan' => 'Jabatan Kejuruteraan Elektrik',
        'color' => '#059669'
    ]
];

$stmt_stud = $conn->prepare("INSERT INTO students (id, username, email, full_name, phone_no, no_ic, no_pendaftaran, password, student_id_file, driving_license_file, status, email_verified, profile_picture, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved', 1, NULL, NOW())");

foreach ($students as $s) {
    $matrik = $s['no_pendaftaran'];
    $stud_id_file = "uploads/documents/{$matrik}_ID.png";
    $lic_file = "uploads/documents/{$matrik}_License.png";

    // Salin dokumen kad matrik & lesen flat kemas bertera air CONTOH
    copy(__DIR__ . "/assets/documents/student_{$s['id']}_ID.png", __DIR__ . '/../' . $stud_id_file);
    copy(__DIR__ . "/assets/documents/student_{$s['id']}_License.png", __DIR__ . '/../' . $lic_file);

    $stmt_stud->bind_param(
        "isssssssss",
        $s['id'],
        $s['username'],
        $s['email'],
        $s['full_name'],
        $s['phone_no'],
        $s['no_ic'],
        $s['no_pendaftaran'],
        $defaultPasswordHash,
        $stud_id_file,
        $lic_file
    );
    $stmt_stud->execute();
}
$stmt_stud->close();
log_status("3 Pelajar (Student) PMU berjaya didaftarkan dan disahkan (Approved & Verified).", "success");

// =========================================================================
// 5. SEED CARS (3 Kenderaan Setiap Provider = 9 Kenderaan)
// =========================================================================
log_status("Menjana 9 kenderaan (3 kenderaan bagi setiap provider)...", "info");

$cars = [
    // Provider 1: Borneo Mobility Enterprise
    [
        'id' => 1,
        'provider_id' => 1,
        'brand' => 'Perodua',
        'model' => 'Myvi 1.5 Advance',
        'plate' => 'QAA 7821 C',
        'trans' => 'Auto',
        'seats' => 5,
        'day' => 110.00,
        'hour' => 15.00,
        'color' => '#1e40af', // Electric Blue
        'type' => 'hatchback'
    ],
    [
        'id' => 2,
        'provider_id' => 1,
        'brand' => 'Proton',
        'model' => 'Saga 1.3 Premium',
        'plate' => 'QAA 3419 D',
        'trans' => 'Auto',
        'seats' => 5,
        'day' => 95.00,
        'hour' => 13.00,
        'color' => '#991b1b', // Ruby Red
        'type' => 'sedan'
    ],
    [
        'id' => 3,
        'provider_id' => 1,
        'brand' => 'Honda',
        'model' => 'City 1.5 V',
        'plate' => 'QAA 8902 E',
        'trans' => 'Auto',
        'seats' => 5,
        'day' => 160.00,
        'hour' => 22.00,
        'color' => '#475569', // Platinum Slate
        'type' => 'sedan'
    ],

    // Provider 2: Mukah Auto Rental Services
    [
        'id' => 4,
        'provider_id' => 2,
        'brand' => 'Perodua',
        'model' => 'Axia 1.0 AV',
        'plate' => 'QMK 2314 B',
        'trans' => 'Auto',
        'seats' => 5,
        'day' => 80.00,
        'hour' => 12.00,
        'color' => '#dc2626', // Lava Red
        'type' => 'hatchback'
    ],
    [
        'id' => 5,
        'provider_id' => 2,
        'brand' => 'Perodua',
        'model' => 'Bezza 1.3 Premium X',
        'plate' => 'QMK 6578 A',
        'trans' => 'Auto',
        'seats' => 5,
        'day' => 90.00,
        'hour' => 13.00,
        'color' => '#0284c7', // Ocean Blue
        'type' => 'sedan'
    ],
    [
        'id' => 6,
        'provider_id' => 2,
        'brand' => 'Toyota',
        'model' => 'Vios 1.5 G',
        'plate' => 'QMK 1109 C',
        'trans' => 'Auto',
        'seats' => 5,
        'day' => 150.00,
        'hour' => 20.00,
        'color' => '#64748b', // Silver Metallic
        'type' => 'sedan'
    ],

    // Provider 3: King Car Rental Mukah
    [
        'id' => 7,
        'provider_id' => 3,
        'brand' => 'Perodua',
        'model' => 'Alza 1.5 AV',
        'plate' => 'QSK 4455 K',
        'trans' => 'Auto',
        'seats' => 7,
        'day' => 180.00,
        'hour' => 25.00,
        'color' => '#78350f', // Vintage Brown
        'type' => 'mpv'
    ],
    [
        'id' => 8,
        'provider_id' => 3,
        'brand' => 'Proton',
        'model' => 'Persona 1.6 Premium',
        'plate' => 'QSK 8823 L',
        'trans' => 'Auto',
        'seats' => 5,
        'day' => 120.00,
        'hour' => 16.00,
        'color' => '#334155', // Snow Graphite
        'type' => 'sedan'
    ],
    [
        'id' => 9,
        'provider_id' => 3,
        'brand' => 'Proton',
        'model' => 'X50 1.5 TGDi Flagship',
        'plate' => 'QSK 9901 M',
        'trans' => 'Auto',
        'seats' => 5,
        'day' => 220.00,
        'hour' => 30.00,
        'color' => '#b91c1c', // Passion Crimson
        'type' => 'suv'
    ]
];

$stmt_car = $conn->prepare("INSERT INTO cars (id, provider_id, car_brand, car_model, car_plate, transmission, seat_capacity, price_per_day, price_per_hour, car_image, grant_file, roadtax_file, insurance_file, roadtax_expiry, insurance_expiry, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '2027-12-31', '2027-12-31', 'Available', NOW())");

foreach ($cars as $c) {
    $cp = preg_replace('/[^a-zA-Z0-9]/', '', $c['plate']);
    $car_img = "uploads/cars/car_{$c['id']}.jpg";
    $grant_img = "uploads/documents/Car_{$cp}_Grant.jpg";
    $rt_img = "uploads/documents/Car_{$cp}_RT.jpg";
    $ins_img = "uploads/documents/Car_{$cp}_Ins.jpg";

    $real_car_asset = __DIR__ . "/assets/cars/car_{$c['id']}.jpg";
    if (file_exists($real_car_asset)) {
        copy($real_car_asset, __DIR__ . '/../' . $car_img);
    } else {
        copy(__DIR__ . '/../uploads/cars/default.png', __DIR__ . '/../' . $car_img);
    }

    $stmt_car->bind_param(
        "iissssiiddssss",
        $c['id'],
        $c['provider_id'],
        $c['brand'],
        $c['model'],
        $c['plate'],
        $c['trans'],
        $c['seats'],
        $c['day'],
        $c['hour'],
        $car_img,
        $grant_img,
        $rt_img,
        $ins_img
    );
    $stmt_car->execute();
}
$stmt_car->close();
log_status("9 buah kenderaan (3 bagi setiap provider) telah berjaya dijana dan dimasukkan ke pangkalan data.", "success");

// =========================================================================
// 6. SEED BOOKINGS (Tempahan Contoh Realistik)
// =========================================================================
log_status("Menjana rekod tempahan contoh (Bookings)...", "info");

// Salin gambar resit bayaran & bukti pemulangan flat kemas bertera air CONTOH
$receipt_1 = "uploads/receipts/receipt_booking_1.png";
$return_3 = "uploads/returns/return_booking_3.png";
copy(__DIR__ . '/assets/documents/receipt_booking_1.png', __DIR__ . '/../' . $receipt_1);
copy(__DIR__ . '/assets/documents/return_booking_3.png', __DIR__ . '/../' . $return_3);

$bookings = [
    [
        'id' => 1,
        'student_id' => 1, // Danish
        'car_id' => 1,     // Myvi
        'rent_type' => 'Daily',
        'start_date' => date('Y-m-d 09:00:00', strtotime('+1 day')),
        'end_date' => date('Y-m-d 09:00:00', strtotime('+2 days')),
        'total_price' => 110.00,
        'payment_receipt' => $receipt_1,
        'receipt_file' => $receipt_1,
        'status' => 'Approved',
        'return_image' => null
    ],
    [
        'id' => 2,
        'student_id' => 2, // Aina
        'car_id' => 5,     // Bezza
        'rent_type' => 'Daily',
        'start_date' => date('Y-m-d 10:00:00', strtotime('+3 days')),
        'end_date' => date('Y-m-d 10:00:00', strtotime('+4 days')),
        'total_price' => 90.00,
        'payment_receipt' => null,
        'receipt_file' => null,
        'status' => 'Pending',
        'return_image' => null
    ],
    [
        'id' => 3,
        'student_id' => 3, // Aaron
        'car_id' => 8,     // Persona
        'rent_type' => 'Daily',
        'start_date' => date('Y-m-d 08:00:00', strtotime('-3 days')),
        'end_date' => date('Y-m-d 08:00:00', strtotime('-2 days')),
        'total_price' => 120.00,
        'payment_receipt' => $return_3,
        'receipt_file' => $return_3,
        'status' => 'Completed',
        'return_image' => $return_3
    ]
];

$stmt_bk = $conn->prepare("INSERT INTO bookings (id, student_id, car_id, rent_type, start_date, end_date, total_price, payment_receipt, receipt_file, status, return_image, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
foreach ($bookings as $b) {
    $stmt_bk->bind_param(
        "iiisssdssss",
        $b['id'],
        $b['student_id'],
        $b['car_id'],
        $b['rent_type'],
        $b['start_date'],
        $b['end_date'],
        $b['total_price'],
        $b['payment_receipt'],
        $b['receipt_file'],
        $b['status'],
        $b['return_image']
    );
    $stmt_bk->execute();
}
$stmt_bk->close();
log_status("Rekod tempahan contoh berjaya dimasukkan.", "success");

// =========================================================================
// 7. SEED NOTIFICATIONS (Notifikasi Sistem)
// =========================================================================
log_status("Menjana notifikasi sistem...", "info");

$notifications = [
    [
        'user_type' => 'student',
        'user_id' => 1,
        'title' => 'Tempahan Diluluskan!',
        'message' => 'Permohonan tempahan kenderaan Perodua Myvi 1.5 Advance anda telah diluluskan oleh penyedia.',
        'link' => 'student/my_bookings.php',
        'type' => 'success'
    ],
    [
        'user_type' => 'student',
        'user_id' => 2,
        'title' => 'Tempahan Dihantar',
        'message' => 'Tempahan Perodua Bezza 1.3 anda telah dihantar dan sedang menunggu semakan penyedia.',
        'link' => 'student/my_bookings.php',
        'type' => 'info'
    ],
    [
        'user_type' => 'provider',
        'user_id' => 1,
        'title' => 'Bayaran Telah Disahkan',
        'message' => 'Resit pembayaran bagi tempahan #1 (Perodua Myvi) telah dimuat naik dan disahkan.',
        'link' => 'provider/provider_bookings.php',
        'type' => 'success'
    ],
    [
        'user_type' => 'provider',
        'user_id' => 2,
        'title' => 'Tempahan Baharu!',
        'message' => 'Pelajar Nur Aina Mardhiah telah membuat tempahan baharu untuk Perodua Bezza.',
        'link' => 'provider/provider_bookings.php',
        'type' => 'warning'
    ],
    [
        'user_type' => 'jhepp',
        'user_id' => 1,
        'title' => 'Sistem Diperbaharui',
        'message' => 'Pangkalan data dan rekod kelulusan kenderaan semester baharu telah sedia.',
        'link' => 'jhepp/jhepp_approved.php',
        'type' => 'info'
    ],
    [
        'user_type' => 'admin',
        'user_id' => 1,
        'title' => 'Seeder Baharu Selesai',
        'message' => 'Semua peranan pengguna dan 9 buah kenderaan penyedia telah berjaya didaftarkan.',
        'link' => 'admin/admin_dashboard.php',
        'type' => 'success'
    ]
];

$stmt_notif = $conn->prepare("INSERT INTO notifications (user_type, user_id, title, message, link, type, is_read, created_at) VALUES (?, ?, ?, ?, ?, ?, 0, NOW())");
foreach ($notifications as $n) {
    $stmt_notif->bind_param("sissss", $n['user_type'], $n['user_id'], $n['title'], $n['message'], $n['link'], $n['type']);
    $stmt_notif->execute();
}
$stmt_notif->close();
log_status("Notifikasi contoh berjaya dimasukkan.", "success");

// Hidupkan semula semakan foreign key
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// -------------------------------------------------------------------------
// PEMBERSIHAN FAIL LAPUK / TIDAK DIGUNAKAN DALAM UPLOADS
// -------------------------------------------------------------------------
log_status("Menyusun dan membersihkan fail uploads yang tidak lagi digunakan...", "info");

$active_files = [
    'uploads/cars/default.png' => true
];

$collect_active = function($res) use (&$active_files) {
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            foreach ($row as $val) {
                if (!empty($val) && is_string($val) && strpos($val, 'uploads') !== false) {
                    $k = strtolower(str_replace(DIRECTORY_SEPARATOR, '/', trim($val)));
                    $active_files[$k] = true;
                }
            }
        }
    }
};

$collect_active($conn->query('SELECT profile_picture FROM admins'));
$collect_active($conn->query('SELECT profile_picture FROM jhepp'));
$collect_active($conn->query('SELECT ic_file, licence_file, insurance_file, greencard_file, roadtax_file, qr_code_image, profile_picture FROM providers'));
$collect_active($conn->query('SELECT student_id_file, driving_license_file, profile_picture FROM students'));
$collect_active($conn->query('SELECT car_image, grant_file, roadtax_file, insurance_file FROM cars'));
$collect_active($conn->query('SELECT payment_receipt, receipt_file, return_image FROM bookings'));

$uploads_iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../uploads'));
$cleaned_count = 0;
foreach ($uploads_iter as $file) {
    if ($file->isDir()) continue;
    $norm = strtolower(str_replace(DIRECTORY_SEPARATOR, '/', $file->getPathname()));
    $norm_rel = substr($norm, strpos($norm, 'uploads/'));
    if (!isset($active_files[$norm_rel])) {
        if (@unlink($file->getPathname())) {
            $cleaned_count++;
        }
    }
}
log_status("Pembersihan selesai: {$cleaned_count} fail lama/tidak digunakan telah dipadamkan.", "success");

log_status("PROSES SEEDER SELESAI DENGAN JAYANYA!", "success");

// =========================================================================
// PAPARAN JADUAL RINGKASAN
// =========================================================================
if ($is_cli) {
    echo PHP_EOL . "============================================================" . PHP_EOL;
    echo "SENARAI AKAUN PENGGUNA BAHARU (SCRS PMU)" . PHP_EOL;
    echo "KATA LALUAN UNTUK SEMUA AKAUN: Password123!" . PHP_EOL;
    echo "============================================================" . PHP_EOL;
    echo "1. PENTADBIR (ADMIN):" . PHP_EOL;
    foreach ($admins as $a) echo "   - {$a['username']} | {$a['full_name']} | {$a['email']}" . PHP_EOL;
    echo PHP_EOL . "2. PEGAWAI JHEPP:" . PHP_EOL;
    foreach ($jhepp_users as $j) echo "   - {$j['username']} | {$j['full_name']} | {$j['email']}" . PHP_EOL;
    echo PHP_EOL . "3. PENYEDIA KERETA (PROVIDER):" . PHP_EOL;
    foreach ($providers as $p) echo "   - {$p['username']} | {$p['company']} ({$p['full_name']}) | Tel: {$p['phone_no']}" . PHP_EOL;
    echo PHP_EOL . "4. PELAJAR PMU (STUDENT):" . PHP_EOL;
    foreach ($students as $s) echo "   - {$s['username']} | {$s['full_name']} ({$s['no_pendaftaran']})" . PHP_EOL;
    echo PHP_EOL . "============================================================" . PHP_EOL;
    echo "SENARAI 9 KENDERAAN (3 SETIAP PROVIDER)" . PHP_EOL;
    echo "============================================================" . PHP_EOL;
    foreach ($cars as $c) {
        $prov_name = $providers[$c['provider_id'] - 1]['company'];
        echo "   [{$c['plate']}] {$c['brand']} {$c['model']} | Provider: {$prov_name} | RM{$c['day']}/hari, RM{$c['hour']}/jam" . PHP_EOL;
    }
    echo "============================================================" . PHP_EOL;
} else {
    echo "<div class='section-title'><i class='bi bi-people-fill'></i> Senarai Akaun Pengguna Baharu (Kata Laluan: <code>Password123!</code>)</div>";
    echo "<table>
        <thead>
            <tr>
                <th>Peranan</th>
                <th>Username</th>
                <th>Nama / Syarikat</th>
                <th>Emel / ID Pendaftaran</th>
                <th>Avatar</th>
            </tr>
        </thead>
        <tbody>";
    
    foreach ($admins as $a) {
        echo "<tr><td><span style='background:#1e3a8a; color:#fff; padding:2px 8px; font-weight:bold;'>ADMIN</span></td><td><strong>{$a['username']}</strong></td><td>{$a['full_name']}</td><td>{$a['email']}</td><td><img src='../uploads/profiles/admin_{$a['id']}.png' class='thumb-preview' style='width:35px; height:35px; border-radius:50%;'></td></tr>";
    }
    foreach ($jhepp_users as $j) {
        echo "<tr><td><span style='background:#065f46; color:#fff; padding:2px 8px; font-weight:bold;'>JHEPP</span></td><td><strong>{$j['username']}</strong></td><td>{$j['full_name']}</td><td>{$j['email']}</td><td><img src='../uploads/profiles/jhepp_{$j['id']}.png' class='thumb-preview' style='width:35px; height:35px; border-radius:50%;'></td></tr>";
    }
    foreach ($providers as $p) {
        echo "<tr><td><span style='background:#b45309; color:#fff; padding:2px 8px; font-weight:bold;'>PROVIDER</span></td><td><strong>{$p['username']}</strong></td><td>{$p['company']}<br><small>{$p['full_name']}</small></td><td>{$p['email']}<br><small>Tel: {$p['phone_no']}</small></td><td><img src='../uploads/profiles/provider_{$p['id']}.png' class='thumb-preview' style='width:35px; height:35px; border-radius:50%;'></td></tr>";
    }
    foreach ($students as $s) {
        echo "<tr><td><span style='background:#2563eb; color:#fff; padding:2px 8px; font-weight:bold;'>STUDENT</span></td><td><strong>{$s['username']}</strong></td><td>{$s['full_name']}</td><td>{$s['email']}<br><code>{$s['no_pendaftaran']}</code></td><td><img src='../uploads/profiles/student_{$s['id']}.png' class='thumb-preview' style='width:35px; height:35px; border-radius:50%;'></td></tr>";
    }
    echo "</tbody></table>";

    echo "<div class='section-title'><i class='bi bi-car-front-fill'></i> Senarai 9 Kenderaan (3 bagi Setiap Provider)</div>";
    echo "<table>
        <thead>
            <tr>
                <th>Imej Kereta</th>
                <th>Plat No.</th>
                <th>Model Kenderaan</th>
                <th>Penyedia</th>
                <th>Kadar Sewaan</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>";
    
    foreach ($cars as $c) {
        $p = $providers[$c['provider_id'] - 1];
        echo "<tr>
            <td><img src='../uploads/cars/car_{$c['id']}.jpg' class='thumb-preview' style='width:75px; height:50px;'></td>
            <td><strong>{$c['plate']}</strong></td>
            <td>{$c['brand']} {$c['model']}<br><small>{$c['trans']} • {$c['seats']} Tempat Duduk</small></td>
            <td>{$p['company']}</td>
            <td><strong>RM" . number_format($c['day'], 2) . "</strong>/hari<br><small>RM" . number_format($c['hour'], 2) . "/jam</small></td>
            <td><span style='background:#10b981; color:#fff; padding:2px 6px; font-size:11px; font-weight:bold;'>Available</span></td>
        </tr>";
    }
    echo "</tbody></table>";

    echo "<p><a href='../index.php' class='btn-home'><i class='bi bi-arrow-right-circle'></i> Buka Laman Log Masuk (index.php)</a></p>";
    echo "</div></body></html>";
}
?>
