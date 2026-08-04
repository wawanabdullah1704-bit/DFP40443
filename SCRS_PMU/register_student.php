<?php
// Calling the db.php file to connect the system to the database.
require 'db.php';

// Prepare an empty variable to store success/error messages.
$message = "";

// Check whether the form has been submitted (the Submit button pressed).
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. RECEIVING AND CLEANING DATA
    // htmlspecialchars() used to filter out malicious code (to prevent XSS attacks)
    $username = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);
    $fullName = htmlspecialchars($_POST['fullName']);
    $phoneNo = htmlspecialchars($_POST['phoneNo']);
    $noIC = htmlspecialchars($_POST['noIC']);
    $noPendaftaran = htmlspecialchars($_POST['noPendaftaran']);
    $userPassword = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    // 2. Password Check
    // Ensure the entered and confirmed passwords are the same.
    if ($userPassword !== $confirmPassword) {
        $message = '<div class="alert alert-danger">Error: Passwords do not match!</div>';
    } else {
        
        // 3. PASSWORD SECURITY
        // Convert the password into random text (hash) so that it cannot be read in the database.
        $hashedPassword = password_hash($userPassword, PASSWORD_DEFAULT);

        // 4. Upload Folder Management
        $targetDir = "uploads/";

        // If the 'uploads' folder does not yet exist, the system will create a new folder.
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // 5. GETTING THE FILE NAME
        $studentIdName = basename($_FILES["studentId"]["name"]);
        $drivingLicenseName = basename($_FILES["drivingLicense"]["name"]);

        // Adding the registration number and time to the file name
        // Purpose: To ensure the file name is unique and not overwritten if someone uploads a file with the same name.
        $newStudentIdName = $noPendaftaran . "_ID_" . time() . "_" . $studentIdName;
        $newLicenseName = $noPendaftaran . "_License_" . time() . "_" . $drivingLicenseName;

        // The full file path will be saved. (E.g.: uploads/20DIT_ID_169000.jpg)
        $targetStudentId = $targetDir . $newStudentIdName;
        $targetLicense = $targetDir . $newLicenseName;

        // 6. File Transfer Process
        // move_uploaded_file() will move the file from the computer to the 'uploads' folder
        if (
            move_uploaded_file($_FILES["studentId"]["tmp_name"], $targetStudentId) &&
            move_uploaded_file($_FILES["drivingLicense"]["tmp_name"], $targetLicense)
        ) {
            
            // 7. SAVE DATA INTO DATABASE (DATABASE)
            //Using a question mark (?) as a security measure (Prepared Statement) to prevent SQL injection.
            $sql = "INSERT INTO students (username, email, full_name, phone_no, no_ic, no_pendaftaran, password, student_id_file, driving_license_file) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            // Ready to enter data
            $stmt = $conn->prepare($sql);
            
            //Binding data from the form to the question marks (?) above.
            // "sssssssss" means there are 9 String data
            $stmt->bind_param("sssssssss", $username, $email, $fullName, $phoneNo, $noIC, $noPendaftaran, $hashedPassword, $targetStudentId, $targetLicense);

            // Execute SQL commands
            if ($stmt->execute()) {
                // UPDATE: If successful, redirect the user to the pending.php page.
                header("Location: pending.php");
                exit(); // Ensure the code stops here after the redirect.
            } else {
                // If it fails to save to the database, display an error
                $message = '<div class="alert alert-danger">Database Error: ' . $stmt->error . '</div>';
            }
            // Close statement
            $stmt->close();
            
        } else {
            // Jika sistem gagal pindahkan fail ke folder uploads
            $message = '<div class="alert alert-danger">Error: Failed to upload documents</div>';
        }
    }
}
// Close the database connection after all tasks are completed.
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Student</title>
    <style>
        /* Set the body to flex so that the footer can be placed at the bottom. */
        body { 
            background-color: #f8f9fa; 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar-brand { font-weight: bold; color: #0d6efd !important; }
        .car-card img { height: 180px; object-fit: cover; }
        
        /* Make the central area flexible so that the footer stays at the bottom.*/
        .main-content {
            flex-grow: 1;
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
            <h5 class="offcanvas-title text-primary fw-bold" id="sidebarMenuLabel">Main Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="#">
                        <i class="bi bi-person-circle text-primary me-3 fs-4"></i> My Profile
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="choose_role.php">
                        <i class="bi bi-house-door text-secondary me-3 fs-4"></i> Choose role
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Use this div wrapper so that the page expands correctly. -->
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">

                <div class="col-md-8 py-3">

                    <?php echo $message; ?>

                    <div class="mb-4 mt-3">
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
                            <button type="button" onclick="window.location.href='index.php';" class="btn btn-primary bg-opacity-75 text-white w-50">Cancel</button>
                            <button type="submit" class="btn btn-primary w-50">Submit</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- COPYRIGHT FOOTER/WATERMARK -->
    <footer class="text-center py-3 mt-auto text-secondary">
        <small>&copy; <?php echo date("Y"); ?> SCRS PMU. All Rights Reserved.</small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>