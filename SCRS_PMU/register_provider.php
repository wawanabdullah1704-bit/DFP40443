<?php
// Panggil fail sambungan pangkalan data
require 'db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari borang
    $username = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);
    $fullName = htmlspecialchars($_POST['fullName']);
    $phoneNo = htmlspecialchars($_POST['phoneNo']);
    $noIC = htmlspecialchars($_POST['noIC']);
    $userPassword = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    // Semakan kata laluan
    if ($userPassword !== $confirmPassword) {
        $message = '<div class="alert alert-danger">Error: Password not match!</div>';
    } else {
        $hashedPassword = password_hash($userPassword, PASSWORD_DEFAULT);

        // Pastikan folder uploads wujud
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Ambil nama asal kesemua 5 fail
        $icName = basename($_FILES["ic_file"]["name"]);
        $licenceName = basename($_FILES["licence_file"]["name"]);
        $insuranceName = basename($_FILES["insurance_file"]["name"]);
        $greencardName = basename($_FILES["greencard_file"]["name"]);
        $roadtaxName = basename($_FILES["roadtax_file"]["name"]);

        $time = time(); // Gunakan masa yang sama untuk semua fail

        // Nama fail unik untuk mengelakkan tertindih
        $newIc = $noIC . "_IC_" . $time . "_" . $icName;
        $newLicence = $noIC . "_Licence_" . $time . "_" . $licenceName;
        $newInsurance = $noIC . "_Ins_" . $time . "_" . $insuranceName;
        $newGreencard = $noIC . "_GC_" . $time . "_" . $greencardName;
        $newRoadtax = $noIC . "_RT_" . $time . "_" . $roadtaxName;

        // Laluan penuh fail
        $targetIc = $targetDir . $newIc;
        $targetLicence = $targetDir . $newLicence;
        $targetInsurance = $targetDir . $newInsurance;
        $targetGreencard = $targetDir . $newGreencard;
        $targetRoadtax = $targetDir . $newRoadtax;

        // Proses muat naik kelima-lima fail serentak
        if (
            move_uploaded_file($_FILES["ic_file"]["tmp_name"], $targetIc) &&
            move_uploaded_file($_FILES["licence_file"]["tmp_name"], $targetLicence) &&
            move_uploaded_file($_FILES["insurance_file"]["tmp_name"], $targetInsurance) &&
            move_uploaded_file($_FILES["greencard_file"]["tmp_name"], $targetGreencard) &&
            move_uploaded_file($_FILES["roadtax_file"]["tmp_name"], $targetRoadtax)
        ) {
            // Masukkan data ke dalam table providers
            $sql = "INSERT INTO providers (username, email, full_name, phone_no, no_ic, password, ic_file, licence_file, insurance_file, greencard_file, roadtax_file) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            // 11 data (String) = "sssssssssss"
            $stmt->bind_param("sssssssssss", $username, $email, $fullName, $phoneNo, $noIC, $hashedPassword, $targetIc, $targetLicence, $targetInsurance, $targetGreencard, $targetRoadtax);

            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Success: Provider account for <strong>' . $fullName . '</strong> successfully registered!</div>';
            } else {
                $message = '<div class="alert alert-danger">Database error: ' . $stmt->error . '</div>';
            }
            $stmt->close();
        } else {
            $message = '<div class="alert alert-danger">Fail to upload one or more documents.</div>';
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Car Provider</title>
    <style>
        body { background-color: #f8f9fa; }
        .navbar-brand { font-weight: bold; color: #0d6efd !important; }
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

    <!-- Offcanvas Menu -->
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
                    <h2>Create your account - Car Provider</h2>
                </div>

                <form action="" method="POST" enctype="multipart/form-data" class="bg-white p-4 shadow-sm rounded border">
                    <h5 class="text-secondary mb-3">PERSONAL DETAILS</h5>

                    <div class="mb-3">
                        <label for="username" class="form-label fw-bold">Username</label>
                        <input type="text" class="form-control" id="username" name="username" placeholder="e.g. provider123" required>
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

                    <div class="mb-3">
                        <label for="noIC" class="form-label fw-bold">No. IC</label>
                        <input type="text" class="form-control" id="noIC" name="noIC" placeholder="000000-00-0000" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-bold">Password</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Maximum 8 characters" maxlength="8" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label fw-bold">Confirm Password</label>
                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" placeholder="Maximum 8 characters" maxlength="8" required>
                    </div>

                    <hr class="mb-4">

                    <h5 class="text-secondary mb-3">DOCUMENT VERIFICATION (PROVIDER)</h5>

                    <!-- 5 File Uploads -->
                    <div class="mb-3">
                        <label for="ic_file" class="form-label fw-bold">IDENTITY CARD (IC)</label>
                        <input class="form-control border-secondary text-primary" style="border-style: dashed;" type="file" id="ic_file" name="ic_file" accept=".jpg, .jpeg, .png, .pdf" required>
                    </div>

                    <div class="mb-3">
                        <label for="licence_file" class="form-label fw-bold">DRIVING LICENCE</label>
                        <input class="form-control border-secondary text-primary" style="border-style: dashed;" type="file" id="licence_file" name="licence_file" accept=".jpg, .jpeg, .png, .pdf" required>
                    </div>

                    <div class="mb-3">
                        <label for="insurance_file" class="form-label fw-bold">CAR INSURANCE COVER NOTE</label>
                        <input class="form-control border-secondary text-primary" style="border-style: dashed;" type="file" id="insurance_file" name="insurance_file" accept=".jpg, .jpeg, .png, .pdf" required>
                    </div>

                    <div class="mb-3">
                        <label for="greencard_file" class="form-label fw-bold">VEHICLE GREEN CARD (GERAN KERETA)</label>
                        <input class="form-control border-secondary text-primary" style="border-style: dashed;" type="file" id="greencard_file" name="greencard_file" accept=".jpg, .jpeg, .png, .pdf" required>
                    </div>

                    <div class="mb-4">
                        <label for="roadtax_file" class="form-label fw-bold">ROADTAX COPY</label>
                        <input class="form-control border-secondary text-primary" style="border-style: dashed;" type="file" id="roadtax_file" name="roadtax_file" accept=".jpg, .jpeg, .png, .pdf" required>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="termsCheck" required>
                        <label class="form-check-label" for="termsCheck">
                            I agree to <a href="#" class="text-decoration-none">Terms and Conditions</a>
                        </label>
                    </div>

                    <div class="d-flex gap-3">
                        <!-- Tukar pautan Cancel ke muka surat pemilihan role atau muka surat sebelum ini -->
                        <button type="button" onclick="window.location.href='index.php';" class="btn btn-primary bg-opacity-75 text-white w-50">Cancel</button>
                        <button type="submit" class="btn btn-primary w-50">Submit</button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>