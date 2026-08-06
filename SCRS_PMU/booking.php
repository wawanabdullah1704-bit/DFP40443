<?php
session_start();
require 'db.php';

// Semak jika pengguna telah log masuk dan merupakan seorang pelajar
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['username'];
$message = "";

// 1. PROSES PENGHANTARAN TEMPAHAN & RESIT (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_booking'])) {
    $car_id = (int)$_POST['car_id'];
    $rent_type = htmlspecialchars($_POST['rent_type']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $total_price = (float)$_POST['total_price'];
    $status = 'Pending';

    // Pengurusan muat naik resit
    $targetDir = "uploads/receipts/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $receiptName = basename($_FILES["payment_receipt"]["name"]);
    $newReceiptName = "Resit_" . $student_id . "_" . time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $receiptName);
    $targetPath = $targetDir . $newReceiptName;

    if (move_uploaded_file($_FILES["payment_receipt"]["tmp_name"], $targetPath)) {
        
        $sql_book = "INSERT INTO bookings (student_id, car_id, rent_type, start_date, end_date, total_price, payment_receipt, status) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_book = $conn->prepare($sql_book);
        
        // Bind 8 parameter
        $stmt_book->bind_param("iisssdss", $student_id, $car_id, $rent_type, $start_date, $end_date, $total_price, $targetPath, $status);

        if ($stmt_book->execute()) {
            $message = "<div class='alert alert-success fw-bold'><i class='bi bi-check-circle-fill me-2'></i>Tempahan dan resit berjaya dihantar! Sila tunggu pengesahan daripada Penyedia Kereta.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Ralat pangkalan data: " . $stmt_book->error . "</div>";
        }
        $stmt_book->close();

    } else {
        $message = "<div class='alert alert-danger'>Ralat: Gagal memuat naik resit pembayaran.</div>";
    }
}

// 2. PROSES CARIAN & PENAPISAN (GET)
$search_start = $_GET['start_date'] ?? '';
$search_end = $_GET['end_date'] ?? '';
$rent_type = $_GET['rent_type'] ?? '';
$result_cars = null;
$search_error = "";
$duration = 0;

if ($search_start && $search_end && $rent_type) {
    $start_ts = strtotime($search_start);
    $end_ts = strtotime($search_end);

    if ($start_ts >= $end_ts) {
        $search_error = "Tarikh/Masa pemulangan mestilah selepas tarikh/masa pengambilan.";
    } else {
        // Kira tempoh masa
        if ($rent_type === 'Hourly') {
            $duration = ceil(($end_ts - $start_ts) / 3600); // Jam
        } else {
            $duration = ceil(($end_ts - $start_ts) / 86400); // Hari
            if ($duration < 1) $duration = 1;
        }

        // Cari kereta tersedia dan gabung maklumat QR Provider
        $sql_cars = "SELECT c.*, p.qr_code_image, p.full_name AS provider_name 
                     FROM cars c 
                     JOIN providers p ON c.provider_id = p.id
                     WHERE c.status = 'Available' 
                     AND c.id NOT IN (
                         SELECT car_id FROM bookings 
                         WHERE status IN ('Pending', 'Approved') 
                         AND (start_date < ? AND end_date > ?)
                     )
                     ORDER BY c.created_at DESC";
                     
        $stmt_cars = $conn->prepare($sql_cars);
        $stmt_cars->bind_param("ss", $search_end, $search_start);
        $stmt_cars->execute();
        $result_cars = $stmt_cars->get_result();
    }
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tempahan Kereta - SCRS PMU</title>
    <style>
        body { background-color: #f8f9fa; min-height: 100vh; display: flex; flex-direction: column; }
        .navbar-brand { font-weight: bold; color: #0d6efd !important; }
        .main-content { flex-grow: 1; }
        .car-image { 
            height: 200px; object-fit: cover; 
            border-top-left-radius: calc(.25rem - 1px);
            border-top-right-radius: calc(.25rem - 1px);
        }
        .search-card { background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    </style>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container-fluid">
            <button class="btn btn-light border-0 me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
                <i class="bi bi-list fs-4"></i>
            </button>
            <a class="navbar-brand me-auto" href="#">SCRS PMU</a>
            
            <span class="navbar-text d-none d-md-block fw-bold text-primary">
                Hai, <?php echo htmlspecialchars($student_name); ?>
            </span>
        </div>
    </nav>

    <!-- OFFCANVAS MENU -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarMenu">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title text-primary fw-bold">Menu Pelajar</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <!-- Link dikemaskini ke booking.php -->
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="booking.php">
                        <i class="bi bi-search text-primary me-3 fs-4"></i> Cari & Tempah Kereta
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="#">
                        <i class="bi bi-card-checklist text-success me-3 fs-4"></i> Tempahan Saya
                    </a>
                </li>
                <li class="nav-item mb-2 mt-auto">
                    <a class="nav-link text-dark fs-5 d-flex align-items-center" href="login.php">
                        <i class="bi bi-box-arrow-left text-danger me-3 fs-4"></i> Log Keluar
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- KANDUNGAN UTAMA -->
    <div class="main-content">
        <div class="container mt-4">
            
            <?php echo $message; ?>
            <?php if ($search_error) echo "<div class='alert alert-danger'>$search_error</div>"; ?>

            <!-- LANGKAH 1: BORANG CARIAN -->
            <div class="search-card p-4 border mb-5">
                <h4 class="fw-bold mb-3"><i class="bi bi-calendar-check text-primary me-2"></i>Langkah 1: Pilih Tarikh & Masa</h4>
                <form action="booking.php" method="GET">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Jenis Sewaan</label>
                            <select class="form-select" name="rent_type" required>
                                <option value="" disabled <?php echo empty($rent_type) ? 'selected' : ''; ?>>Pilih...</option>
                                <option value="Daily" <?php echo ($rent_type === 'Daily') ? 'selected' : ''; ?>>Harian (Daily)</option>
                                <option value="Hourly" <?php echo ($rent_type === 'Hourly') ? 'selected' : ''; ?>>Jam (Hourly)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Tarikh & Masa Ambil</label>
                            <input type="datetime-local" class="form-control" name="start_date" value="<?php echo htmlspecialchars($search_start); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Tarikh & Masa Pulang</label>
                            <input type="datetime-local" class="form-control" name="end_date" value="<?php echo htmlspecialchars($search_end); ?>" required>
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-primary fw-bold py-2"><i class="bi bi-search me-1"></i> Cari</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- LANGKAH 2: PAPARAN KERETA TERSEDIA -->
            <?php if ($result_cars !== null): ?>
                
                <h4 class="fw-bold mb-4"><i class="bi bi-car-front-fill text-success me-2"></i>Langkah 2: Pilih Kereta & Pembayaran</h4>
                <p class="text-secondary">Menampilkan kereta yang tersedia untuk tempoh <strong><?php echo $duration; ?> <?php echo ($rent_type === 'Daily') ? 'Hari' : 'Jam'; ?></strong>.</p>
                
                <div class="row g-4 mb-5">
                    <?php 
                    if ($result_cars->num_rows > 0) {
                        while ($car = $result_cars->fetch_assoc()) { 
                            // Kira jumlah harga
                            $price_rate = ($rent_type === 'Daily') ? $car['price_per_day'] : $car['price_per_hour'];
                            $total_calc_price = $duration * $price_rate;
                            
                            // Semak gambar QR Code
                            $qr_image = !empty($car['qr_code_image']) ? $car['qr_code_image'] : '';
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card shadow-sm border-0 h-100 rounded-4">
                            <img src="<?php echo htmlspecialchars($car['car_image']); ?>" class="card-img-top car-image" alt="Gambar Kereta">
                            <div class="card-body d-flex flex-column">
                                
                                <h5 class="card-title fw-bold mb-1"><?php echo htmlspecialchars($car['car_model']); ?></h5>
                                <p class="text-secondary mb-3"><i class="bi bi-123 me-2"></i><?php echo htmlspecialchars($car['car_plate']); ?></p>
                                
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <span class="badge bg-light text-dark border"><i class="bi bi-gear-fill me-1"></i> <?php echo htmlspecialchars($car['transmission']); ?></span>
                                    <span class="badge bg-light text-dark border"><i class="bi bi-people-fill me-1"></i> <?php echo htmlspecialchars($car['seat_capacity']); ?> Tempat Duduk</span>
                                </div>
                                
                                <div class="p-3 bg-light rounded-3 mb-3 border mt-auto">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-secondary" style="font-size: 0.85rem;">Kadar (<?php echo ($rent_type === 'Daily') ? 'Harian' : 'Jam'; ?>)</span>
                                        <span class="fw-bold">RM <?php echo number_format($price_rate, 2); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2">
                                        <span class="text-secondary fw-bold">Jumlah Harga:</span>
                                        <span class="text-primary fw-bold fs-5">RM <?php echo number_format($total_calc_price, 2); ?></span>
                                    </div>
                                </div>

                                <!-- Butang Buka Modal Popup Payment -->
                                <button type="button" class="btn btn-success w-100 fw-bold py-2 rounded-3" data-bs-toggle="modal" data-bs-target="#paymentModal<?php echo $car['id']; ?>">
                                    Tempah Kereta Ini
                                </button>

                            </div>
                        </div>
                    </div>

                    <!-- MODAL POPUP PAYMENT & UPLOAD RESIT -->
                    <div class="modal fade" id="paymentModal<?php echo $car['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content rounded-4 border-0 shadow">
                                <div class="modal-header border-bottom-0 bg-light rounded-top-4">
                                    <h5 class="modal-title fw-bold text-dark">Pengesahan & Pembayaran</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                </div>
                                <div class="modal-body p-4">
                                    
                                    <form action="booking.php" method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <!-- Bahagian Kiri: Ringkasan -->
                                            <div class="col-md-6 border-end mb-4 mb-md-0">
                                                <h5 class="fw-bold text-primary mb-3"><?php echo htmlspecialchars($car['car_model']); ?></h5>
                                                <ul class="list-group list-group-flush mb-4">
                                                    <li class="list-group-item d-flex justify-content-between px-0">
                                                        <span class="text-secondary">Ambil:</span> 
                                                        <strong><?php echo date('d M Y, h:i A', strtotime($search_start)); ?></strong>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between px-0">
                                                        <span class="text-secondary">Pulang:</span> 
                                                        <strong><?php echo date('d M Y, h:i A', strtotime($search_end)); ?></strong>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between px-0">
                                                        <span class="text-secondary">Tempoh:</span> 
                                                        <strong><?php echo $duration; ?> <?php echo ($rent_type === 'Daily') ? 'Hari' : 'Jam'; ?></strong>
                                                    </li>
                                                </ul>
                                                <div class="p-3 bg-light rounded-3 border border-success border-opacity-25">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="fw-bold text-dark">Jumlah Bayaran:</span>
                                                        <span class="fw-bold fs-3 text-success">RM <?php echo number_format($total_calc_price, 2); ?></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Bahagian Kanan: QR & Muat Naik -->
                                            <div class="col-md-6 text-center d-flex flex-column justify-content-center align-items-center">
                                                <p class="fw-bold text-dark mb-1">Imbas DuitNow QR Penyedia</p>
                                                <p class="text-muted small mb-3">Penyedia: <?php echo htmlspecialchars($car['provider_name']); ?></p>
                                                
                                                <?php if (!empty($qr_image) && file_exists($qr_image)): ?>
                                                    <img src="<?php echo htmlspecialchars($qr_image); ?>" alt="QR DuitNow" class="img-fluid rounded border mb-3 shadow-sm" style="max-height: 180px; object-fit: contain;">
                                                <?php else: ?>
                                                    <div class="p-3 bg-light border border-danger text-danger rounded mb-3 w-100">
                                                        <i class="bi bi-exclamation-octagon fs-2 d-block mb-1"></i>
                                                        <small>Tiada Kod QR dimuat naik. Sila hubungi penyedia.</small>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="w-100 text-start mt-2">
                                                    <label class="form-label fw-bold small text-primary">Muat Naik Resit (Wajib)</label>
                                                    <input class="form-control border-primary text-primary" style="border-style: dashed;" type="file" name="payment_receipt" accept=".jpg, .jpeg, .png, .pdf" required>
                                                    <small class="text-muted" style="font-size: 0.75rem;">Format: JPG, PNG, PDF.</small>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Input Tersembunyi -->
                                        <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                        <input type="hidden" name="rent_type" value="<?php echo htmlspecialchars($rent_type); ?>">
                                        <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($search_start); ?>">
                                        <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($search_end); ?>">
                                        <input type="hidden" name="total_price" value="<?php echo $total_calc_price; ?>">

                                        <div class="mt-4 pt-3 border-top text-end d-flex gap-2 justify-content-end">
                                            <button type="button" class="btn btn-light border text-secondary fw-bold px-4" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" name="submit_booking" class="btn btn-primary fw-bold px-4">
                                                <i class="bi bi-cloud-arrow-up me-1"></i> Hantar Tempahan & Resit
                                            </button>
                                        </div>
                                    </form>

                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- TAMAT MODAL -->

                    <?php 
                        } 
                    } else {
                        echo '<div class="col-12"><div class="alert alert-warning border shadow-sm text-center py-4"><i class="bi bi-emoji-frown fs-1 d-block mb-2"></i>Maaf, tiada kereta yang tersedia untuk tarikh/masa yang dipilih. Sila cuba tarikh atau masa lain.</div></div>';
                    }
                    ?>
                </div>
            <?php elseif (empty($search_error)): ?>
                <!-- Keadaan asal sebelum carian dibuat -->
                <div class="text-center py-5 mt-4 text-secondary">
                    <i class="bi bi-search fs-1 mb-3 d-block text-muted opacity-50"></i>
                    <h4>Sila pilih tarikh dan masa di atas untuk mencari kereta yang tersedia.</h4>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- FOOTER HAK CIPTA -->
    <footer class="text-center py-3 mt-auto text-secondary border-top bg-white">
        <small>&copy; <?php echo date("Y"); ?> SCRS PMU. Hak Cipta Terpelihara.</small>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>