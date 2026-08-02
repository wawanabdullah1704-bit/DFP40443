<?php
session_start();
require 'db.php';

// Pastikan pengguna adalah pelajar
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$message = "";

// Semak jika ID tempahan ada di URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$booking_id = (int)$_GET['id'];
$student_id = $_SESSION['student_id'];

// Ambil maklumat harga dari tempahan ini
$sql_booking = "SELECT total_price, receipt_file FROM bookings WHERE id = ? AND student_id = ?";
$stmt = $conn->prepare($sql_booking);
$stmt->bind_param("ii", $booking_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Jika ID tempahan salah atau bukan milik pelajar ini
    header("Location: dashboard.php");
    exit();
}

$booking_data = $result->fetch_assoc();
$total_price = $booking_data['total_price'];

// Jika resit sudah pernah dimuat naik sebelum ini
if (!empty($booking_data['receipt_file'])) {
    $message = "<div class='alert alert-info text-center'>You have already submitted the payment receipt for this booking. Waiting for provider approval.</div>";
}

// PROSES MUAT NAIK RESIT
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['receipt'])) {
    
    $targetDir = "uploads/receipts/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $receiptName = basename($_FILES["receipt"]["name"]);
    $newReceiptName = time() . "_Receipt_" . $booking_id . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $receiptName);
    $targetPath = $targetDir . $newReceiptName;

    if (move_uploaded_file($_FILES["receipt"]["tmp_name"], $targetPath)) {
        
        // Kemaskini database dengan fail resit
        $sql_update = "UPDATE bookings SET receipt_file = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $targetPath, $booking_id);

        if ($stmt_update->execute()) {
            $message = "<div class='alert alert-success text-center fw-bold'>Payment receipt successfully submitted! Please wait for the provider to verify. <br><br><a href='dashboard.php' class='btn btn-sm btn-outline-success mt-2'>Back to Dashboard</a></div>";
        } else {
            $message = "<div class='alert alert-danger'>Database error: " . $stmt_update->error . "</div>";
        }
        $stmt_update->close();
        
    } else {
        $message = "<div class='alert alert-danger'>Failed to upload receipt file. Please try again.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - SCRS PMU</title>
    <style>
        body { 
            background-color: #f4f7f9; 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar-brand { font-weight: bold; color: #000 !important; }
        .logo-circle {
            width: 35px;
            height: 35px;
            background-color: #3b82f6;
            border-radius: 50%;
        }
        .qr-placeholder {
            width: 150px;
            height: 150px;
            background-color: #ef4444; /* Warna merah seperti gambar */
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.1rem;
            margin: 0 auto;
        }
        .btn-success-custom {
            background-color: #00c853;
            border-color: #00c853;
            color: white;
        }
        .btn-success-custom:hover {
            background-color: #00a844;
        }
    </style>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

    <!-- NAVBAR RINGKAS -->
    <nav class="navbar navbar-light bg-white shadow-sm sticky-top">
        <div class="container-fluid px-3">
            <div class="d-flex align-items-center py-1">
                <div class="logo-circle me-3"></div>
                <span class="navbar-brand mb-0 h4 fw-bold">SCRS PMU</span>
            </div>
        </div>
    </nav>

    <!-- KANDUNGAN TENGAH (PAYMENT CARD) -->
    <div class="container flex-grow-1 d-flex justify-content-center align-items-center py-5">
        
        <div class="card shadow-sm border-0 rounded-4 p-4 p-md-5" style="max-width: 450px; width: 100%;">
            
            <?php echo $message; ?>

            <!-- Sembunyikan borang jika resit sudah dihantar -->
            <?php if (empty($message) || strpos($message, 'alert-danger') !== false): ?>
                
                <h4 class="text-center fw-bold mb-4">Manual Payment Upload</h4>
                
                <!-- Ruang QR Code -->
                <div class="mb-4">
                    <div class="qr-placeholder">
                        QR Code
                    </div>
                    <!-- Nota: Jika anda ada gambar QR code sebenar, gantikan div di atas dengan: -->
                    <!-- <img src="assets/qr_code_sebenar.png" class="mx-auto d-block" width="150" alt="QR Code"> -->
                </div>

                <h4 class="text-center fw-bold mb-4">Total: RM<?php echo number_format($total_price, 0); ?></h4>

                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="form-label text-secondary mb-1" style="font-size: 0.9rem;">Upload Receipt</label>
                        <input class="form-control bg-light" type="file" name="receipt" accept=".jpg, .jpeg, .png, .pdf" required>
                    </div>

                    <button type="submit" class="btn btn-success-custom w-100 fw-bold py-2 rounded-3">Submit Receipt</button>
                </form>

            <?php endif; ?>

        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>