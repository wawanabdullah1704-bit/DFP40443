<?php
/**
 * Penjana Dokumen Reka Bentuk Rata (Flat Design) SCRS PMU
 * Ciri-ciri:
 * - Gaya 2D Flat Vector / Minimalist & Kemas (Bukan 3D/realistik)
 * - Tera Air (Watermark) CONTOH di bahagian tengah
 * - Disimpan secara kekal di database/assets/documents/
 */

$dir = __DIR__ . '/assets/documents';
if (!is_dir($dir)) mkdir($dir, 0777, true);

$font = 'C:/Windows/Fonts/arialbd.ttf';
$fontReg = 'C:/Windows/Fonts/arial.ttf';
if (!file_exists($font)) $font = null;
if (!file_exists($fontReg)) $fontReg = null;

function renderText($im, $size, $angle, $x, $y, $col, $txt, $bold = true) {
    global $font, $fontReg;
    $f = $bold ? $font : $fontReg;
    if ($f && function_exists('imagettftext')) {
        imagettftext($im, $size, $angle, $x, $y, $col, $f, $txt);
    } else {
        $gd = ($size > 13) ? 5 : (($size > 10) ? 4 : 2);
        imagestring($im, $gd, $x, $y - 12, $txt, $col);
    }
}

function drawWatermark($im, $w, $h) {
    global $font;
    $redWatermark = imagecolorallocatealpha($im, 239, 68, 68, 76); // #ef4444 semi-transparent
    $centerX = (int)($w / 2);
    $centerY = (int)($h / 2);

    if ($font && function_exists('imagettftext')) {
        imagettftext($im, 64, -22, $centerX - 210, $centerY + 30, $redWatermark, $font, "C O N T O H");
        imagettftext($im, 14, -22, $centerX - 200, $centerY + 68, $redWatermark, $font, "DOKUMEN CONTOH / SPECIMEN USE ONLY");
    } else {
        imagestring($im, 5, $centerX - 60, $centerY - 10, "C O N T O H", $redWatermark);
    }
}

// -------------------------------------------------------------
// 1. FLAT KAD MATRIK PELAJAR (STUDENT ID)
// -------------------------------------------------------------
function makeStudentId($filepath, $name, $matric, $dept) {
    $w = 800; $h = 500;
    $im = imagecreatetruecolor($w, $h);
    imagealphablending($im, true);
    imagesavealpha($im, true);

    $bg = imagecolorallocate($im, 248, 250, 252);
    $white = imagecolorallocate($im, 255, 255, 255);
    $navy = imagecolorallocate($im, 30, 58, 138); // #1e3a8a
    $amber = imagecolorallocate($im, 245, 158, 11); // #f59e0b
    $dark = imagecolorallocate($im, 15, 23, 42); // #0f172a
    $grey = imagecolorallocate($im, 100, 116, 139); // #64748b
    $border = imagecolorallocate($im, 203, 213, 225); // #cbd5e1
    $blueBadge = imagecolorallocate($im, 2, 132, 199);

    imagefilledrectangle($im, 0, 0, $w, $h, $bg);

    // Header Rata
    imagefilledrectangle($im, 0, 0, $w, 95, $navy);
    imagefilledrectangle($im, 0, 90, $w, 95, $amber);

    renderText($im, 17, 0, 45, 48, $white, "POLITEKNIK MUKAH SARAWAK", true);
    renderText($im, 10, 0, 45, 74, $amber, "KAD PENGENALAN PELAJAR / STUDENT IDENTIFICATION CARD", true);

    // Foto Pelajar (Flat Shape)
    imagefilledrectangle($im, 45, 130, 215, 350, imagecolorallocate($im, 241, 245, 249));
    imagerectangle($im, 45, 130, 215, 350, $border);
    imagefilledellipse($im, 130, 205, 75, 75, imagecolorallocate($im, 148, 163, 184));
    imagefilledellipse($im, 130, 295, 115, 85, imagecolorallocate($im, 148, 163, 184));
    renderText($im, 9, 0, 85, 375, $grey, "FOTO PELAJAR", true);

    // Maklumat Pelajar
    $fields = [
        ["NAMA PELAJAR / FULL NAME", strtoupper($name), $dark, 14],
        ["NO. PENDAFTARAN / MATRIC NO", $matric, $navy, 16],
        ["JABATAN / DEPARTMENT", strtoupper($dept), $dark, 11],
        ["SESI PENGAJIAN / STATUS", "SESI 2024/2026 • STATUS: AKTIF (DISAHKAN)", $blueBadge, 11]
    ];

    $y = 150;
    foreach ($fields as $f) {
        renderText($im, 9, 0, 250, $y, $grey, $f[0], true);
        renderText($im, $f[3], 0, 250, $y + 24, $f[2], $f[1], true);
        imageline($im, 250, $y + 36, $w - 45, $y + 36, $border);
        $y += 56;
    }

    // Barcode Bawah Rata
    imagefilledrectangle($im, 250, 395, 330, 450, $amber);
    imagerectangle($im, 250, 395, 330, 450, $dark);
    imageline($im, 250, 422, 330, 422, $dark);
    imageline($im, 290, 395, 290, 450, $dark);

    for ($b = 355; $b < $w - 45; $b += 8) {
        imagefilledrectangle($im, $b, 400, $b + 3, 445, $dark);
    }

    // Watermark
    drawWatermark($im, $w, $h);

    // Bingkai Luar Kad Rata
    imagesetthickness($im, 2);
    imagerectangle($im, 1, 1, $w - 2, $h - 2, $border);

    imagepng($im, $filepath);
    imagedestroy($im);
}

// -------------------------------------------------------------
// 2. FLAT LESEN MEMANDU MALAYSIA (DRIVING LICENSE)
// -------------------------------------------------------------
function makeLicense($filepath, $name, $ic) {
    $w = 800; $h = 500;
    $im = imagecreatetruecolor($w, $h);
    imagealphablending($im, true);
    imagesavealpha($im, true);

    $bg = imagecolorallocate($im, 248, 250, 252);
    $white = imagecolorallocate($im, 255, 255, 255);
    $green = imagecolorallocate($im, 16, 149, 93); // #10b981
    $gold = imagecolorallocate($im, 245, 158, 11);
    $dark = imagecolorallocate($im, 15, 23, 42);
    $grey = imagecolorallocate($im, 100, 116, 139);
    $border = imagecolorallocate($im, 203, 213, 225);
    $redBadge = imagecolorallocate($im, 220, 38, 38);

    imagefilledrectangle($im, 0, 0, $w, $h, $bg);

    // Header Rata
    imagefilledrectangle($im, 0, 0, $w, 90, $green);
    imagefilledrectangle($im, 0, 85, $w, 90, $gold);

    renderText($im, 16, 0, 45, 46, $white, "MALAYSIA — LESEN MEMANDU KOMPETEN (CDL)", true);
    renderText($im, 10, 0, 45, 72, imagecolorallocate($im, 240, 253, 244), "ROAD TRANSPORT DEPARTMENT MALAYSIA • DRIVING LICENSE", true);

    $fields = [
        ["NO. KAD PENGENALAN / IC NO", $ic, $dark, 16],
        ["NAMA PEMEGANG / NAME", strtoupper($name), $green, 13],
        ["KELAS MEMANDU / VEHICLE CLASS", "D & DA (KERETA MOTOR / TRANSMISI AUTOMATIK)", $redBadge, 14],
        ["TEMPOH SAH LESEN / VALIDITY", "01/01/2024 HINGGA 31/12/2029 (5 TAHUN - SAH)", $dark, 11]
    ];

    $y = 145;
    foreach ($fields as $f) {
        renderText($im, 9, 0, 50, $y, $grey, $f[0], true);
        renderText($im, $f[3], 0, 50, $y + 24, $f[2], $f[1], true);
        imageline($im, 50, $y + 36, $w - 240, $y + 36, $border);
        $y += 56;
    }

    // Foto Kanan Rata
    imagefilledrectangle($im, $w - 205, 125, $w - 45, 345, imagecolorallocate($im, 241, 245, 249));
    imagerectangle($im, $w - 205, 125, $w - 45, 345, $border);
    imagefilledellipse($im, $w - 125, 200, 75, 75, imagecolorallocate($im, 148, 163, 184));
    imagefilledellipse($im, $w - 125, 290, 115, 85, imagecolorallocate($im, 148, 163, 184));
    renderText($im, 9, 0, $w - 165, 370, $grey, "FOTO PEMEGANG", true);

    // Footer
    imagefilledrectangle($im, 0, 435, $w, 500, $white);
    imageline($im, 0, 435, $w, 435, $border);
    renderText($im, 10, 0, 50, 470, $grey, "JABATAN PENGANGKUTAN JALAN (JPJ) • CAWANGAN MUKAH SARAWAK", false);

    drawWatermark($im, $w, $h);

    imagesetthickness($im, 2);
    imagerectangle($im, 1, 1, $w - 2, $h - 2, $border);

    imagepng($im, $filepath);
    imagedestroy($im);
}

// -------------------------------------------------------------
// 3. FLAT KAD PENGENALAN (MYKAD)
// -------------------------------------------------------------
function makeMyKad($filepath, $name, $ic) {
    $w = 800; $h = 500;
    $im = imagecreatetruecolor($w, $h);
    imagealphablending($im, true);
    imagesavealpha($im, true);

    $bg = imagecolorallocate($im, 240, 249, 255); // #f0f9ff
    $white = imagecolorallocate($im, 255, 255, 255);
    $navy = imagecolorallocate($im, 30, 58, 138);
    $red = imagecolorallocate($im, 220, 38, 38);
    $gold = imagecolorallocate($im, 245, 158, 11);
    $dark = imagecolorallocate($im, 15, 23, 42);
    $grey = imagecolorallocate($im, 100, 116, 139);
    $border = imagecolorallocate($im, 186, 230, 253);

    imagefilledrectangle($im, 0, 0, $w, $h, $bg);

    // Jalur Kebangsaan Flat
    imagefilledrectangle($im, 0, 0, $w, 35, $red);
    imagefilledrectangle($im, 0, 35, $w, 55, $navy);
    imagefilledrectangle($im, 0, 55, $w, 60, $gold);

    renderText($im, 15, 0, 45, 42, $white, "MALAYSIA", true);
    renderText($im, 11, 0, 150, 42, imagecolorallocate($im, 254, 240, 138), "KAD PENGENALAN • IDENTITY CARD (MYKAD)", true);

    // Cip Pintar MyKad Emas Rata
    imagefilledrectangle($im, 50, 130, 135, 195, $gold);
    imagerectangle($im, 50, 130, 135, 195, $dark);
    imageline($im, 50, 162, 135, 162, $dark);
    imageline($im, 92, 130, 92, 195, $dark);

    // Maklumat MyKad
    $fields = [
        ["NO. KAD PENGENALAN / IC NO", $ic, $dark, 18],
        ["NAMA / FULL NAME", strtoupper($name), $navy, 13],
        ["WARGANEGARA / CITIZENSHIP", "WARGANEGARA MALAYSIA", $dark, 11],
        ["ALAMAT KEDIAMAN", "MUKAH, BAHAGIAN MUKAH, 96400 SARAWAK", $grey, 10]
    ];

    $y = 145;
    foreach ($fields as $f) {
        renderText($im, 9, 0, 165, $y, $grey, $f[0], true);
        renderText($im, $f[3], 0, 165, $y + 24, $f[2], $f[1], true);
        $y += 56;
    }

    // Foto Kanan
    imagefilledrectangle($im, $w - 200, 120, $w - 45, 340, imagecolorallocate($im, 241, 245, 249));
    imagerectangle($im, $w - 200, 120, $w - 45, 340, $border);
    imagefilledellipse($im, $w - 122, 195, 75, 75, imagecolorallocate($im, 148, 163, 184));
    imagefilledellipse($im, $w - 122, 285, 115, 85, imagecolorallocate($im, 148, 163, 184));
    renderText($im, 9, 0, $w - 160, 365, $grey, "FOTO BIOMETRIK", true);

    // Footer Bar
    imagefilledrectangle($im, 0, 440, $w, 500, $white);
    imageline($im, 0, 440, $w, 440, $border);
    renderText($im, 10, 0, 45, 472, $grey, "JABATAN PENDAFTARAN NEGARA MALAYSIA (JPN)", false);

    drawWatermark($im, $w, $h);

    imagesetthickness($im, 2);
    imagerectangle($im, 1, 1, $w - 2, $h - 2, $border);

    imagepng($im, $filepath);
    imagedestroy($im);
}

// -------------------------------------------------------------
// 4. FLAT GERAN JPJ / ROADTAX / INSURANS / KAD HIJAU
// -------------------------------------------------------------
function makeDocCertificate($filepath, $title, $name, $id, $extra, $badgeText = "SAH & AKTIF") {
    $w = 800; $h = 500;
    $im = imagecreatetruecolor($w, $h);
    imagealphablending($im, true);
    imagesavealpha($im, true);

    $bg = imagecolorallocate($im, 255, 255, 255);
    $navy = imagecolorallocate($im, 30, 58, 138);
    $gold = imagecolorallocate($im, 245, 158, 11);
    $dark = imagecolorallocate($im, 15, 23, 42);
    $grey = imagecolorallocate($im, 100, 116, 139);
    $border = imagecolorallocate($im, 203, 213, 225);
    $green = imagecolorallocate($im, 16, 149, 93);

    imagefilledrectangle($im, 0, 0, $w, $h, $bg);

    // Header Rata
    imagefilledrectangle($im, 0, 0, $w, 90, $navy);
    imagefilledrectangle($im, 0, 85, $w, 90, $gold);

    renderText($im, 16, 0, 45, 46, imagecolorallocate($im, 255, 255, 255), "JABATAN PENGANGKUTAN JALAN MALAYSIA (JPJ)", true);
    renderText($im, 10, 0, 45, 72, $gold, strtoupper($title), true);

    $fields = [
        ["NO. RUJUKAN / REGISTRATION NO", $id, $dark, 16],
        ["NAMA PEMILIK / SYARIKAT", strtoupper($name), $navy, 13],
        ["KETERANGAN / PARTICULARS", $extra, $dark, 11],
        ["STATUS KELAYAKAN", $badgeText, $green, 12]
    ];

    $y = 145;
    foreach ($fields as $f) {
        renderText($im, 9, 0, 50, $y, $grey, $f[0], true);
        renderText($im, $f[3], 0, 50, $y + 24, $f[2], $f[1], true);
        imageline($im, 50, $y + 36, $w - 230, $y + 36, $border);
        $y += 56;
    }

    // Mohor Cop Flat di Kanan
    imagefilledellipse($im, $w - 130, 245, 140, 140, imagecolorallocate($im, 241, 245, 249));
    imageellipse($im, $w - 130, 245, 140, 140, $navy);
    imageellipse($im, $w - 130, 245, 120, 120, $gold);
    renderText($im, 9, 0, $w - 175, 240, $navy, "JPJ SARAWAK", true);
    renderText($im, 8, 0, $w - 170, 260, $gold, "CAWANGAN MUKAH", true);

    // Footer
    imagefilledrectangle($im, 0, 435, $w, 500, imagecolorallocate($im, 248, 250, 252));
    imageline($im, 0, 435, $w, 435, $border);
    renderText($im, 10, 0, 50, 470, $grey, "PENGESAHAN DOKUMEN SISTEM SCRS PMU • SISTEM SEWAAN KERETA", false);

    drawWatermark($im, $w, $h);

    imagesetthickness($im, 2);
    imagerectangle($im, 1, 1, $w - 2, $h - 2, $border);

    imagepng($im, $filepath);
    imagedestroy($im);
}

echo "Memulakan penjanaan semua dokumen flat template...\n";

// 1. Dokumen Pelajar (Student 1, 2, 3)
makeStudentId("$dir/student_1_ID.png", "Muhammad Danish bin Harun", "20DIT24F1010", "Jabatan Teknologi Maklumat & Komunikasi");
makeLicense("$dir/student_1_License.png", "Muhammad Danish bin Harun", "040506-13-5671");

makeStudentId("$dir/student_2_ID.png", "Nur Aina Mardhiah binti Rosli", "20DAT24F1022", "Jabatan Perdagangan (Perakaunan)");
makeLicense("$dir/student_2_License.png", "Nur Aina Mardhiah binti Rosli", "040812-13-8902");

makeStudentId("$dir/student_3_ID.png", "Aaron Lee Jun Kit", "20DEE24F1055", "Jabatan Kejuruteraan Elektrik");
makeLicense("$dir/student_3_License.png", "Aaron Lee Jun Kit", "041123-13-3344");

// 2. Dokumen Provider (Provider 1, 2, 3)
$provs = [
    1 => ["name" => "Safwan bin Ramli", "ic" => "880512-13-5543", "clean_ic" => "880512135543", "company" => "Borneo Mobility Enterprise"],
    2 => ["name" => "Dayang Rozita binti Abang", "ic" => "901124-13-6028", "clean_ic" => "901124136028", "company" => "Mukah Auto Rental Services"],
    3 => ["name" => "Alexander Anak Jimmy", "ic" => "930403-13-7189", "clean_ic" => "930403137189", "company" => "King Car Rental Mukah"]
];

foreach ($provs as $p) {
    $c_ic = $p['clean_ic'];
    makeMyKad("$dir/{$c_ic}_IC.png", $p['name'], $p['ic']);
    makeLicense("$dir/{$c_ic}_Licence.png", $p['name'], $p['ic']);
    makeDocCertificate("$dir/{$c_ic}_Ins.png", "SIJIL POLISI INSURANS KOMPREHENSIF KENDERAAN", $p['company'], "INS-SARAWAK-{$c_ic}", "PERLINDUNGAN INSURANS KENDERAAN SEWAAN");
    makeDocCertificate("$dir/{$c_ic}_GC.png", "KAD HIJAU LESEN OPERASI PERNIAGAAN", $p['company'], "GC-PMU-{$c_ic}", "LESEN PENGENDALI KENDERAAN SEWA PMU");
    makeDocCertificate("$dir/{$c_ic}_RT.png", "LESEN KENDERAAN MOTOR (CUKAI JALAN / ROADTAX)", $p['company'], "LKM-{$c_ic}", "CUKAI JALAN SAH SEHINGGA 31/12/2027");
}

// -------------------------------------------------------------
// 5. DOKUMEN GERAN KERETA 1 HINGGA 9 (GAMBAR BAHARU)
// -------------------------------------------------------------
// Dokumen kenderaan menggunakan gambar baharu di uploads/documents/ (Car_*_Grant.jpg, RT.jpg, Ins.jpg)


// -------------------------------------------------------------
// 6. FLAT RESIT BAYARAN & BUKTI PEMULANGAN (RECEIPT & RETURN)
// -------------------------------------------------------------
function makeFlatReceipt($filepath, $title, $refNo, $payerName, $providerName, $amount) {
    $w = 600; $h = 750;
    $im = imagecreatetruecolor($w, $h);
    imagealphablending($im, true);
    imagesavealpha($im, true);

    $bg = imagecolorallocate($im, 255, 255, 255);
    $navy = imagecolorallocate($im, 30, 58, 138);
    $dark = imagecolorallocate($im, 15, 23, 42);
    $grey = imagecolorallocate($im, 100, 116, 139);
    $border = imagecolorallocate($im, 203, 213, 225);
    $green = imagecolorallocate($im, 16, 149, 93);
    $lightGreen = imagecolorallocate($im, 236, 253, 245);

    imagefilledrectangle($im, 0, 0, $w, $h, $bg);

    // Header
    imagefilledrectangle($im, 0, 0, $w, 80, $navy);
    renderText($im, 16, 0, 40, 42, imagecolorallocate($im, 255, 255, 255), "RESIT TRANSAKSI DIGITAL PMU", true);
    renderText($im, 10, 0, 40, 64, imagecolorallocate($im, 254, 240, 138), strtoupper($title), true);

    // Status Lulus
    imagefilledrectangle($im, 40, 100, $w - 40, 150, $lightGreen);
    imagerectangle($im, 40, 100, $w - 40, 150, $green);
    renderText($im, 12, 0, 60, 132, $green, "STATUS: TRANSAKSI BERJAYA / SELESAI", true);

    // Jumlah
    renderText($im, 10, 0, 40, 185, $grey, "JUMLAH BAYARAN / TOTAL AMOUNT", true);
    renderText($im, 28, 0, 40, 225, $dark, "RM " . number_format($amount, 2), true);
    imageline($im, 40, 245, $w - 40, 245, $border);

    $fields = [
        ["NO. RUJUKAN RESIT", $refNo],
        ["NAMA PEMBAYAR", strtoupper($payerName)],
        ["PENYEDIA KENDERAAN", strtoupper($providerName)],
        ["TARIKH & MASA", date("d/m/Y H:i:s")],
        ["SALURAN BAYARAN", "DUITNOW QR / TNG DIGITAL"],
        ["STATUS AUDIT SISTEM", "DISAHKAN OLEH SISTEM SCRS PMU"]
    ];

    $y = 275;
    foreach ($fields as $f) {
        renderText($im, 9, 0, 40, $y, $grey, $f[0], true);
        renderText($im, 11, 0, 40, $y + 20, $dark, $f[1], true);
        imageline($im, 40, $y + 32, $w - 40, $y + 32, $border);
        $y += 50;
    }

    // Watermark
    drawWatermark($im, $w, $h);

    // Border
    imagesetthickness($im, 2);
    imagerectangle($im, 1, 1, $w - 2, $h - 2, $border);

    imagepng($im, $filepath);
    imagedestroy($im);
}

// Resit Bayaran & Pemulangan
makeFlatReceipt("$dir/receipt_booking_1.png", "RESIT BAYARAN SEWAAN KERETA", "SCRS-PAY-882190", "Muhammad Danish bin Harun", "Borneo Mobility Enterprise", 110.00);
makeFlatReceipt("$dir/return_booking_3.png", "BUKTI PENERIMAAN PEMULANGAN KENDERAAN", "SCRS-RET-901122", "Aaron Lee Jun Kit", "King Car Rental Mukah", 120.00);

echo "Semua dokumen flat design dengan watermark CONTOH berjaya dijana ke $dir!\n";
