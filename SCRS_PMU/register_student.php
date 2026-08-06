<?php
// Memanggil fail db.php untuk menyambungkan sistem ke pangkalan data
require 'db.php';

// Menyediakan pembolehubah kosong untuk menyimpan mesej berjaya/ralat
$message = "";

// Semak sama ada borang telah dihantar (butang Hantar ditekan)
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

    // 2. SEMAKAN KATA LALUAN (Lebih daripada 8 aksara)
    if (strlen($userPassword) <= 8) {
        $message = '<div class="alert alert-danger">Ralat: Kata laluan mestilah lebih daripada 8 aksara!</div>';
    } 
    // Pastikan kata laluan yang diisi dan disahkan adalah sama
    else if ($userPassword !== $confirmPassword) {
        $message = '<div class="alert alert-danger">Ralat: Kata laluan tidak sepadan!</div>';
    } 
    else {
        
        // 3. SEMAKAN DATA UNIK (Username dan Email tidak boleh sama)
        $check_sql = "SELECT id FROM students WHERE username = ? OR email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $message = '<div class="alert alert-danger">Ralat: Nama Pengguna (Username) atau E-mel ini telah digunakan. Sila gunakan yang lain.</div>';
        } else {
            // 4. KESELAMATAN KATA LALUAN
            // Tukar kata laluan kepada teks rawak (hash) supaya tidak boleh dibaca di database
            $hashedPassword = password_hash($userPassword, PASSWORD_DEFAULT);

            // 5. PENGURUSAN FOLDER MUAT NAIK
            $targetDir = "uploads/";

            // Jika folder 'uploads' belum wujud, sistem akan ciptakan satu folder baharu
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            // 6. MENDAPATKAN NAMA FAIL
            $studentIdName = basename($_FILES["studentId"]["name"]);
            $drivingLicenseName = basename($_FILES["drivingLicense"]["name"]);

            // Menambah No Pendaftaran dan masa (time) pada nama fail
            // Tujuan: Supaya nama fail jadi unik dan tak tertindih jika ada orang upload fail nama sama
            $newStudentIdName = $noPendaftaran . "_ID_" . time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $studentIdName);
            $newLicenseName = $noPendaftaran . "_License_" . time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $drivingLicenseName);

            // Laluan penuh fail akan disimpan (contoh: uploads/20DIT_ID_169000_gambar.jpg)
            $targetStudentId = $targetDir . $newStudentIdName;
            $targetLicense = $targetDir . $newLicenseName;

            // 7. PROSES MEMINDAHKAN FAIL
            // move_uploaded_file() akan memindahkan fail dari komputer ke folder 'uploads'
            if (
                move_uploaded_file($_FILES["studentId"]["tmp_name"], $targetStudentId) &&
                move_uploaded_file($_FILES["drivingLicense"]["tmp_name"], $targetLicense)
            ) {
                
                // 8. SIMPAN DATA KE DALAM PANGKALAN DATA (DATABASE)
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
                    // UPDATE: Jika berjaya, bawa pengguna ke muka surat pending.php
                    header("Location: pending.php");
                    exit(); // Pastikan kod berhenti di sini selepas redirect
                } else {
                    // Jika gagal simpan ke database, papar ralat
                    $message = '<div class="alert alert-danger">Ralat Pangkalan Data: ' . $stmt->error . '</div>';
                }
                // Tutup statement
                $stmt->close();
                
            } else {
                // Jika sistem gagal pindahkan fail ke folder uploads
                $message = '<div class="alert alert-danger">Ralat: Gagal memuat naik dokumen. Sila cuba lagi.</div>';
            }
        }
        $check_stmt->close();
    }
}
// Tutup sambungan pangkalan data selepas selesai semua tugas
$conn->close();
?>

<!DOCTYPE html>
<html lang="ms">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cipta Akaun - Pelajar</title>
    <style>
        /* Tetapkan body sebagai flex supaya footer boleh diletakkan di bawah */
        body { 
            background-color: #f8f9fa; 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar-brand { font-weight: bold; color: #0d6efd !important; }
        .car-card img { height: 180px; object-fit: cover; }
        
        /* Jadikan ruang tengah fleksibel supaya footer kekal di bawah */
        .main-content {
            flex-grow: 1;
        }
        .password-toggle {
            cursor: pointer;
        }
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
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="#">
                        <i class="bi bi-person-circle text-primary me-3 fs-4"></i> Profil Saya
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="choose_role.php">
                        <i class="bi bi-house-door text-secondary me-3 fs-4"></i> Pilih Peranan
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Gunakan div wrapper ini supaya halaman mengembang dengan betul -->
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">

                <div class="col-md-8 py-3">

                    <?php echo $message; ?>

                    <div class="mb-4 mt-3">
                        <h2>Cipta Akaun Anda - Pelajar</h2>
                    </div>

                    <form action="" method="POST" enctype="multipart/form-data" class="bg-white p-4 shadow-sm rounded border">
                        <h5 class="text-secondary mb-3">MAKLUMAT PERIBADI</h5>

                        <div class="mb-3">
                            <label for="username" class="form-label fw-bold">Nama Pengguna (Username)</label>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Cth: Ali123" required>
                        </div>

                        <div class="mb-3">
                            <label for="fullName" class="form-label fw-bold">Nama Penuh</label>
                            <input type="text" class="form-control" id="fullName" name="fullName" placeholder="Ali bin Abu" required>
                        </div>

                        <div class="mb-3">
                            <label for="phoneNo" class="form-label fw-bold">No. Telefon</label>
                            <input type="text" class="form-control" id="phoneNo" name="phoneNo" placeholder="0123456789" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold">E-mel</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="contoh@email.com" required>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="noIC" class="form-label fw-bold">No. Kad Pengenalan</label>
                                <input type="text" class="form-control" id="noIC" name="noIC" placeholder="000000-00-0000" required>
                            </div>
                            <div class="col-md-6">
                                <label for="noPendaftaran" class="form-label fw-bold">No. Pendaftaran (Matrik)</label>
                                <input type="text" class="form-control" id="noPendaftaran" name="noPendaftaran" placeholder="20DIT24F0000" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-bold">Kata Laluan <small class="text-danger fw-normal">(Mesti lebih 8 aksara)</small></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" minlength="9" placeholder="Masukkan kata laluan" required>
                                <span class="input-group-text password-toggle bg-white" onclick="togglePassword('password', 'icon1')">
                                    <i class="bi bi-eye" id="icon1"></i>
                                </span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="confirmPassword" class="form-label fw-bold">Sahkan Kata Laluan</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" minlength="9" placeholder="Sahkan kata laluan" required>
                                <span class="input-group-text password-toggle bg-white" onclick="togglePassword('confirmPassword', 'icon2')">
                                    <i class="bi bi-eye" id="icon2"></i>
                                </span>
                            </div>
                        </div>

                        <hr class="mb-4">

                        <h5 class="text-secondary mb-3">PENGESAHAN DOKUMEN</h5>

                        <div class="mb-3">
                            <label for="studentId" class="form-label fw-bold">KAD MATRIK PELAJAR</label>
                            <input class="form-control border-secondary text-primary" style="border-style: dashed;" type="file" id="studentId" name="studentId" accept=".jpg, .jpeg, .png, .pdf" required>
                        </div>

                        <div class="mb-4">
                            <label for="drivingLicense" class="form-label fw-bold">LESEN MEMANDU</label>
                            <input class="form-control border-secondary text-primary" style="border-style: dashed;" type="file" id="drivingLicense" name="drivingLicense" accept=".jpg, .jpeg, .png, .pdf" required>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="termsCheck" required>
                            <label class="form-check-label" for="termsCheck">
                                Saya bersetuju dengan <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal" class="text-decoration-none">Terma dan Syarat</a>
                            </label>
                        </div>

                        <div class="d-flex gap-3">
                            <button type="button" onclick="window.location.href='index.php';" class="btn btn-primary bg-opacity-75 text-white w-50">Batal</button>
                            <button type="submit" class="btn btn-primary w-50">Hantar</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL TERMA DAN SYARAT KHUSUS PELAJAR (POP-UP) -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-primary" id="termsModalLabel">Terma dan Syarat / Terms and Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <!-- Versi Bahasa Melayu -->
                    <h6 class="fw-bold text-dark border-bottom pb-2">Terma dan Syarat (Pelajar / Penyewa)</h6>
                    <ol class="mb-4">
                        <li><strong>Kelayakan:</strong> Penyewa mestilah pelajar yang berdaftar dan wajib memiliki Lesen Memandu Malaysia yang masih sah tempoh.</li>
                        <li><strong>Tanggungjawab Penjagaan:</strong> Penyewa bertanggungjawab sepenuhnya ke atas keselamatan, kebersihan, dan penjagaan kenderaan sepanjang tempoh sewaan.</li>
                        <li><strong>Saman dan Kesalahan Trafik:</strong> Sebarang saman lalu lintas, kompaun, atau denda yang dikenakan semasa tempoh sewaan adalah tanggungjawab penyewa sepenuhnya.</li>
                        <li><strong>Larangan Menyewa Semula:</strong> Penyewa dilarang sama sekali menyewakan semula (sublet) kenderaan tersebut kepada pihak ketiga atau rakan lain.</li>
                        <li><strong>Kerosakan dan Kemalangan:</strong> Sebarang kerosakan kenderaan akibat kecuaian penyewa perlu dilaporkan segera kepada penyedia kereta. Kos pembaikan adalah di bawah tanggungjawab penyewa.</li>
                        <li><strong>Pemulangan Kenderaan:</strong> Kenderaan hendaklah dipulangkan pada masa dan tarikh yang telah dipersetujui beserta tahap minyak yang sama seperti sebelum disewa. Kelewatan boleh menyebabkan caj tambahan dikenakan.</li>
                    </ol>

                    <!-- Versi Bahasa Inggeris -->
                    <h6 class="fw-bold text-dark border-bottom pb-2">Terms and Conditions (Student / Renter)</h6>
                    <ol>
                        <li><strong>Eligibility:</strong> The renter must be a registered student and possess a valid Malaysian Driving License.</li>
                        <li><strong>Care Responsibility:</strong> The renter is fully responsible for the safety, cleanliness, and care of the vehicle throughout the rental period.</li>
                        <li><strong>Summons and Traffic Offences:</strong> Any traffic summons, compounds, or fines incurred during the rental period are the sole responsibility of the renter.</li>
                        <li><strong>Prohibition of Subletting:</strong> The renter is strictly prohibited from subletting the vehicle to a third party or other friends.</li>
                        <li><strong>Damages and Accidents:</strong> Any damage to the vehicle due to the renter's negligence must be reported immediately to the car provider. Repair costs are the renter's responsibility.</li>
                        <li><strong>Vehicle Return:</strong> The vehicle must be returned at the agreed time and date with the same fuel level as before the rental. Delays may incur additional charges.</li>
                    </ol>
                </div>
                <div class="modal-footer d-flex justify-content-center">
                    <button type="button" class="btn btn-primary px-5" data-bs-dismiss="modal">Saya Faham / I Understand</button>
                </div>
            </div>
        </div>
    </div>

    <!-- COPYRIGHT FOOTER/WATERMARK -->
    <footer class="text-center py-3 mt-auto text-secondary">
        <small>&copy; <?php echo date("Y"); ?> SCRS PMU. Hak Cipta Terpelihara.</small>
    </footer>

    <!-- JAVASCRIPT UNTUK SHOW/HIDE PASSWORD -->
    <script>
        function togglePassword(inputId, iconId) {
            var inputField = document.getElementById(inputId);
            var icon = document.getElementById(iconId);
            
            if (inputField.type === "password") {
                inputField.type = "text";
                icon.classList.remove("bi-eye");
                icon.classList.add("bi-eye-slash");
            } else {
                inputField.type = "password";
                icon.classList.remove("bi-eye-slash");
                icon.classList.add("bi-eye");
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>