<?php
session_start();
require 'db.php';

$message = "";

// PROSES BUTANG APPROVE ATAU REJECT
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id'];
    $action = $_POST['action']; // Sama ada 'approve' atau 'reject'
    
    $new_status = ($action === 'approve') ? 'approved' : 'rejected';
    
    // Kemaskini status di dalam pangkalan data
    $sql_update = "UPDATE students SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("si", $new_status, $student_id);
    
    if ($stmt->execute()) {
        $alert_type = ($action === 'approve') ? 'alert-success' : 'alert-danger';
        $alert_text = ($action === 'approve') ? 'diluluskan' : 'ditolak';
        $message = "<div class='alert {$alert_type}'>Berjaya: Akaun pelajar telah {$alert_text}!</div>";
    } else {
        $message = "<div class='alert alert-danger'>Ralat pangkalan data: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// AMBIL SENARAI PELAJAR YANG MASIH PENDING
$sql_pending = "SELECT * FROM students WHERE status = 'pending'";
$result = $conn->query($sql_pending);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Account - JHEPP</title>
    <style>
        /* Gaya konsisten dengan muka surat lain */
        body { background-color: #f8f9fa; }
        .navbar-brand { font-weight: bold; color: #0d6efd !important; }
        .document-btn { background-color: #f1f3f5; color: #495057; font-weight: 500; }
        .document-btn:hover { background-color: #e9ecef; }
    </style>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">

    <!-- NAVBAR (Sama seperti sebelumnya) -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container-fluid">
            <button class="btn btn-light border-0 me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu">
                <i class="bi bi-list fs-4"></i>
            </button>
            <a class="navbar-brand me-auto" href="#">SCRS PMU</a>
        </div>
    </nav>

    <!-- OFFCANVAS MENU -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title text-primary fw-bold" id="sidebarMenuLabel">Menu JHEPP</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="#">
                        <i class="bi bi-person-lines-fill text-primary me-3 fs-4"></i> Verify Users
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="#">
                        <i class="bi bi-box-arrow-left text-danger me-3 fs-4"></i> Log Out
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- KANDUNGAN UTAMA -->
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6 col-sm-10">
                
                <h3 class="fw-bold mb-4">List of user registration</h3>
                
                <?php echo $message; ?>

                <?php 
                // Semak jika ada senarai pending
                if ($result->num_rows > 0) {
                    // Loop untuk setiap pelajar yang pending
                    while ($row = $result->fetch_assoc()) { 
                ?>
                
                <!-- Kad Pengesahan Akaun Berdasarkan Gambar -->
                <div class="card shadow-sm border-0 mb-4 rounded-4">
                    <div class="card-body p-4">
                        
                        <!-- Maklumat Pelajar -->
                        <div class="mb-3" style="font-size: 1.1rem;">
                            <div class="mb-1">Name: <strong><?php echo htmlspecialchars($row['full_name']); ?></strong></div>
                            <div class="mb-1">Phone No. : <strong><?php echo htmlspecialchars($row['phone_no']); ?></strong></div>
                            <div class="mb-1">No. IC: <strong><?php echo htmlspecialchars($row['no_ic']); ?></strong></div>
                            <div class="mb-1">No. Pendaftaran: <strong><?php echo htmlspecialchars($row['no_pendaftaran']); ?></strong></div>
                        </div>

                        <!-- Bahagian Dokumen -->
                        <p class="text-secondary fw-bold mb-2 mt-4" style="font-size: 0.85rem; letter-spacing: 0.5px;">DOCUMENT</p>
                        
                        <!-- Membuka fail di tab baharu menggunakan target="_blank" -->
                        <a href="<?php echo $row['student_id_file']; ?>" target="_blank" class="btn document-btn border w-100 mb-2 py-2 rounded-3 text-uppercase">
                            STUDENT ID
                        </a>
                        <a href="<?php echo $row['driving_license_file']; ?>" target="_blank" class="btn document-btn border w-100 mb-4 py-2 rounded-3 text-uppercase">
                            DRIVING LICENCE
                        </a>

                        <!-- Butang Tindakan (Reject / Approve) -->
                        <form action="" method="POST">
                            <!-- Menghantar ID pelajar secara tersembunyi -->
                            <input type="hidden" name="student_id" value="<?php echo $row['id']; ?>">
                            
                            <div class="d-flex gap-2">
                                <button type="submit" name="action" value="reject" class="btn btn-danger w-50 fw-bold py-2 rounded-3" style="background-color: #c90000; border: none;">
                                    REJECT
                                </button>
                                <button type="submit" name="action" value="approve" class="btn btn-success w-50 fw-bold py-2 rounded-3" style="background-color: #00b33c; border: none;">
                                    APPROVE
                                </button>
                            </div>
                        </form>

                    </div>
                </div>

                <?php 
                    } 
                } else {
                    // Jika tiada akaun yang pending
                    echo '<div class="alert alert-info shadow-sm border-0">Tiada pendaftaran baharu pada masa ini.</div>';
                }
                ?>

            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>