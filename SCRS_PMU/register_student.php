<?php
// Memanggil fail db.php untuk menyambungkan sistem ke pangkalan data
require 'db.php';

// Menyediakan pembolehubah kosong untuk menyimpan mesej berjaya/ralat
$message = "";

// Semak sama ada borang telah dihantar (butang Submit ditekan)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. MENGAMBIL DAN MEMBERSIHKAN DATA
    // htmlspecialchars() digunakan untuk menapis kod bahaya (elak serangan XSS)
    $username = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);
    $fullName = htmlspecialchars($_POST['fullName']);
    $phoneNo = htmlspecialchars($_POST['phoneNo']);
    $noIC = htmlspecialchars($_POST['noIC']);
    $noPendaftaran = htmlspecialchars($_POST['noPendaftaran']);
    $userPassword = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    // 2. SEMAKAN KATA LALUAN
    // Pastikan kata laluan yang diisi dan disahkan adalah sama
    if ($userPassword !== $confirmPassword) {
        $message = '<div class="alert alert-danger">Ralat: Kata laluan tidak sepadan!</div>';
    } else {
        
        // 3. KESELAMATAN KATA LALUAN
        // Tukar kata laluan kepada teks rawak (hash) supaya tidak boleh dibaca di database
        $hashedPassword = password_hash($userPassword, PASSWORD_DEFAULT);

        // 4. PENGURUSAN FOLDER MUAT NAIK
        $targetDir = "uploads/";

        // Jika folder 'uploads' belum wujud, sistem akan ciptakan satu folder baharu
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // 5. MENDAPATKAN NAMA FAIL
        $studentIdName = basename($_FILES["studentId"]["name"]);
        $drivingLicenseName = basename($_FILES["drivingLicense"]["name"]);

        // Menambah No Pendaftaran dan masa (time) pada nama fail
        // Tujuan: Supaya nama fail jadi unik dan tak tertindih jika ada orang upload fail nama sama
        $newStudentIdName = $noPendaftaran . "_ID_" . time() . "_" . $studentIdName;
        $newLicenseName = $noPendaftaran . "_License_" . time() . "_" . $drivingLicenseName;

        // Laluan penuh fail akan disimpan (contoh: uploads/20DIT_ID_169000_gambar.jpg)
        $targetStudentId = $targetDir . $newStudentIdName;
        $targetLicense = $targetDir . $newLicenseName;

        // 6. PROSES MEMINDAHKAN FAIL
        // move_uploaded_file() akan memindahkan fail dari komputer ke folder 'uploads'
        if (
            move_uploaded_file($_FILES["studentId"]["tmp_name"], $targetStudentId) &&
            move_uploaded_file($_FILES["drivingLicense"]["tmp_name"], $targetLicense)
        ) {
            
            // 7. SIMPAN DATA KE DALAM PANGKALAN DATA (DATABASE)
            // Menggunakan tanda soal (?) sebagai langkah keselamatan (Prepared Statement) untuk elak SQL Injection
            $sql = "INSERT INTO students (username, email, full_name, phone_no, no_ic, no_pendaftaran, password, student_id_file, driving_license_file) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            // Bersedia untuk memasukkan data
            $stmt = $conn->prepare($sql);
            
            // Mengikat (bind) data dari borang ke dalam tanda soal (?) di atas
            // "sssssssss" bermaksud ada 9 data berbentuk String
            $stmt->bind_param("sssssssss", $username, $email, $fullName, $phoneNo, $noIC, $noPendaftaran, $hashedPassword, $targetStudentId, $targetLicense);

            // Jalankan arahan SQL
            if ($stmt->execute()) {
                // Jika berjaya, papar mesej ini
                $message = '<div class="alert alert-success">Berjaya: Akaun pelajar untuk <strong>' . $fullName . '</strong> telah didaftarkan!</div>';
            } else {
                // Jika gagal simpan ke database, papar ralat
                $message = '<div class="alert alert-danger">Ralat Database: ' . $stmt->error . '</div>';
            }
            // Tutup statement
            $stmt->close();
            
        } else {
            // Jika sistem gagal pindahkan fail ke folder uploads
            $message = '<div class="alert alert-danger">Ralat: Gagal memuat naik dokumen.</div>';
        }
    }
}
// Tutup sambungan pangkalan data selepas selesai semua tugas
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Student</title>
    <style>
        body { background-color: #f8f9fa; }
        .navbar-brand { font-weight: bold; color: #0d6efd !important; }
        .car-card img { height: 180px; object-fit: cover; }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>

<body class="bg-light">
    
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container-fluid">
            <button class="btn btn-light border-0 me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu">
                <i class="bi bi-list fs-4"></i>
            </button>

            <a class="navbar-brand me-auto" href="#">SCRS PMU</a>
        </div>
    </nav>

    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title text-primary fw-bold" id="sidebarMenuLabel">Menu Utama</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="#">
                        <i class="bi bi-person-circle text-primary me-3 fs-4"></i> Profile Saya
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="#">
                        <i class="bi bi-house-door text-secondary me-3 fs-4"></i> Laman Utama
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="container">
        <div class="row justify-content-center">

            <div class="col-md-8 py-3">

                <?php echo $message; ?>

                <div class="mb-4">
                    <h2>Create your account - Student</h2>
                </div>

                <form action="" method="POST" enctype="multipart/form-data" class="bg-white p-4 shadow-sm rounded border">
                    <h5 class="text-secondary mb-3">PERSONAL DETAILS</h5>

                    <div class="mb-3">
                        <label for="username" class="form-label fw-bold">Username</label>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Ali123" required>
                    </div>

                    <div class="mb-3">
                        <label for="fullName" class="form-label fw-bold">Full Name</label>
                        <input type="text" class="form-control" id="fullName" name="fullName" placeholder="Ali bin Abu" required>
                    </div>

                    <div class="mb-3">
                        <label for="phoneNo" class="form-label fw-bold">Phone No.</label>
                        <input type="text" class="form-control" id="phoneNo" name="phoneNo" placeholder="0123456789" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-bold">Email</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="example@email.com" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="noIC" class="form-label fw-bold">No. IC</label>
                            <input type="text" class="form-control" id="noIC" name="noIC" placeholder="000000-00-0000" required>
                        </div>
                        <div class="col-md-6">
                            <label for="noPendaftaran" class="form-label fw-bold">No. Pendaftaran</label>
                            <input type="text" class="form-control" id="noPendaftaran" name="noPendaftaran" placeholder="20DIT24F0000" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-bold">Password</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Maximum 8 characters" maxlength="8" required>
                    </div>

                    <div class="mb-4">
                        <label for="confirmPassword" class="form-label fw-bold">Confirm Password</label>
                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" placeholder="Maximum 8 characters" maxlength="8" required>
                    </div>

                    <hr class="mb-4">

                    <h5 class="text-secondary mb-3">DOCUMENT VERIFICATION</h5>

                    <div class="mb-3">
                        <label for="studentId" class="form-label fw-bold">STUDENT ID CARD</label>
                        <input class="form-control border-secondary text-primary" style="border-style: dashed;" type="file" id="studentId" name="studentId" accept=".jpg, .jpeg, .png, .pdf" required>
                    </div>

                    <div class="mb-4">
                        <label for="drivingLicense" class="form-label fw-bold">DRIVING LICENSE</label>
                        <input class="form-control border-secondary text-primary" style="border-style: dashed;" type="file" id="drivingLicense" name="drivingLicense" accept=".jpg, .jpeg, .png, .pdf" required>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="termsCheck" required>
                        <label class="form-check-label" for="termsCheck">
                            I agree to <a href="#" class="text-decoration-none">Terms and Conditions</a>
                        </label>
                    </div>

                    <div class="d-flex gap-3">
                        <button type="button" onclick="window.location.href='login.php';" class="btn btn-primary bg-opacity-75 text-white w-50">Cancel</button>
                        <button type="submit" class="btn btn-primary w-50">Submit</button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>